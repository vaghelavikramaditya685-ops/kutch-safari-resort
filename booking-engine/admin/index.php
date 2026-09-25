<?php
/* Bookings list — the screen reservations staff live in. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
$user = require_login();
check_csrf();

/* --- Actions ----------------------------------------------------------- */
$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $id = (int) ($_POST['booking_id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'confirm_upi':
            $r = upi_mark_received((int) $_POST['payment_id'], $user['name'], $_POST['bank_ref'] ?? '');
            $flash = $r['ok'] ? 'Payment marked as received and the booking confirmed.' : $r['error'];
            break;
        case 'record_payment':
            record_offline_payment($id, (float) $_POST['amount'], $_POST['method'] ?? 'cash',
                                   $user['name'], $_POST['note'] ?? '');
            $flash = 'Payment recorded.';
            break;
        case 'cancel':
            $r = cancel_booking($id, $_POST['reason'] ?? 'Cancelled by staff', $user['name']);
            $flash = $r['ok'] ? 'Booking cancelled. ' . $r['note'] : $r['error'];
            break;
        case 'resync':
            $r = channel_push_booking($id);
            $flash = $r['ok'] ? 'Sent to Stayflexi.' : 'Stayflexi refused it: ' . ($r['reason'] ?? '');
            break;
        case 'mark_status':
            update('bookings', $id, ['status' => $_POST['status'], 'updated_at' => now()]);
            audit('status_changed', 'booking', $id, ['to' => $_POST['status']], $user['name']);
            $flash = 'Status updated.';
            break;
    }
}

/* --- Filters ----------------------------------------------------------- */
$status   = $_GET['status'] ?? '';
$property = (int) ($_GET['property'] ?? 0);
$search   = trim($_GET['q'] ?? '');
$from     = $_GET['from'] ?? '';
$to       = $_GET['to'] ?? '';

$where = ['1=1']; $params = [];
if ($status)   { $where[] = 'b.status = ?';      $params[] = $status; }
if ($property) { $where[] = 'b.property_id = ?'; $params[] = $property; }
if ($from)     { $where[] = 'b.check_out >= ?';  $params[] = $from; }
if ($to)       { $where[] = 'b.check_in <= ?';   $params[] = $to; }
if ($search) {
    $where[] = '(b.ref LIKE ? OR b.guest_name LIKE ? OR b.guest_phone LIKE ? OR b.guest_email LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}
$sql = 'SELECT b.*, p.name AS property_name FROM bookings b
        JOIN properties p ON p.id = b.property_id
        WHERE ' . implode(' AND ', $where) . ' ORDER BY b.created_at DESC LIMIT 200';
$bookings = q($sql, $params);

/* --- Today's numbers --------------------------------------------------- */
$today = date('Y-m-d');
$stats = [
    'Arriving today'  => qval("SELECT COUNT(*) FROM bookings WHERE check_in = ? AND status = 'confirmed'", [$today], 0),
    'In house'        => qval("SELECT COUNT(*) FROM bookings WHERE check_in <= ? AND check_out > ? AND status = 'confirmed'", [$today, $today], 0),
    'Awaiting payment'=> qval("SELECT COUNT(*) FROM bookings WHERE status = 'pending'", [], 0),
    'New enquiries'   => qval("SELECT COUNT(*) FROM enquiries WHERE status = 'new'", [], 0),
];
$unsynced = q("SELECT id, ref FROM bookings WHERE sf_sync_error IS NOT NULL AND status <> 'cancelled'");
$pending_upi = q("SELECT pay.*, b.ref, b.guest_name FROM payments pay
                   JOIN bookings b ON b.id = pay.booking_id
                  WHERE pay.provider = 'upi_qr' AND pay.status = 'awaiting_confirmation'
                  ORDER BY pay.id DESC");

admin_head('Bookings', $user);
?>
<?php if ($flash): ?><div class="notice notice--ok"><?= h($flash) ?></div><?php endif; ?>

<div class="stat-row">
  <?php foreach ($stats as $label => $value): ?>
    <div class="stat-card"><b><?= (int) $value ?></b><span><?= h($label) ?></span></div>
  <?php endforeach; ?>
</div>

<?php if ($pending_upi): ?>
<div class="panel" style="border-left-color:var(--warn)">
  <h3 style="font-size:1rem">UPI payments waiting to be checked</h3>
  <p style="font-size:.85rem">These guests scanned the QR code. Check the bank account, then confirm —
     the booking only becomes confirmed once you do.</p>
  <table class="grid" style="margin-top:10px">
    <tr><th>Booking</th><th>Guest</th><th>Amount</th><th>Reference on the statement</th><th>Confirm</th></tr>
    <?php foreach ($pending_upi as $p): ?>
    <tr>
      <td><a href="?q=<?= h($p['ref']) ?>"><?= h($p['ref']) ?></a></td>
      <td><?= h($p['guest_name']) ?></td>
      <td><?= inr($p['amount']) ?></td>
      <td><code><?= h($p['upi_ref']) ?></code></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="confirm_upi">
          <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
          <input name="bank_ref" placeholder="Bank ref (optional)" style="padding:6px 8px;border:1px solid var(--line);border-radius:3px">
          <button class="btn btn--sm" type="submit">Money received</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<?php if ($unsynced): ?>
<div class="notice notice--err">
  <strong><?= count($unsynced) ?> booking(s) did not reach Stayflexi.</strong>
  Those rooms may still be on sale on the OTAs — open each one and press "Send to Stayflexi",
  or enter it in Stayflexi by hand:
  <?php foreach ($unsynced as $u): ?><a href="?q=<?= h($u['ref']) ?>"><?= h($u['ref']) ?></a> <?php endforeach; ?>
</div>
<?php endif; ?>

<form class="filters" method="get">
  <div class="field"><label>Search</label>
    <input name="q" value="<?= h($search) ?>" placeholder="Reference, name or phone"></div>
  <div class="field"><label>Status</label>
    <select name="status">
      <option value="">All</option>
      <?php foreach (['pending','confirmed','cancelled','completed','no_show'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="field"><label>Property</label>
    <select name="property">
      <option value="">Both</option>
      <?php foreach (q("SELECT id, name FROM properties ORDER BY id") as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $property === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="field"><label>Staying between</label><input type="date" name="from" value="<?= h($from) ?>"></div>
  <div class="field"><label>and</label><input type="date" name="to" value="<?= h($to) ?>"></div>
  <button class="btn btn--sm" type="submit">Filter</button>
  <a class="btn btn--plain btn--sm" href="index.php">Clear</a>
  <a class="btn btn--plain btn--sm" href="export.php?<?= h($_SERVER['QUERY_STRING'] ?? '') ?>">Download CSV</a>
</form>

<table class="grid">
  <thead><tr>
    <th>Reference</th><th>Guest</th><th>Property</th><th>Stay</th>
    <th>Rooms</th><th>Total</th><th>Paid</th><th>Status</th><th>Channel</th><th></th>
  </tr></thead>
  <tbody>
  <?php if (!$bookings): ?>
    <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:30px">No bookings match that.</td></tr>
  <?php endif; ?>
  <?php foreach ($bookings as $b):
      $rooms = q("SELECT room_type_name, rooms, rate_plan_name FROM booking_rooms WHERE booking_id = ?", [$b['id']]);
      $balance = (float) $b['total'] - (float) $b['amount_paid']; ?>
    <tr>
      <td><a href="booking.php?id=<?= (int) $b['id'] ?>"><strong><?= h($b['ref']) ?></strong></a><br>
          <small style="color:var(--muted)"><?= date('j M', strtotime($b['created_at'])) ?></small></td>
      <td><?= h($b['guest_name']) ?><br><small style="color:var(--muted)"><?= h($b['guest_phone']) ?></small></td>
      <td><small><?= h($b['property_name']) ?></small></td>
      <td><?= date('j M', strtotime($b['check_in'])) ?> – <?= date('j M Y', strtotime($b['check_out'])) ?><br>
          <small style="color:var(--muted)"><?= (int) $b['nights'] ?> night(s), <?= (int) $b['adults'] ?> guest(s)</small></td>
      <td><?php foreach ($rooms as $r): ?>
            <small><?= (int) $r['rooms'] ?>× <?= h($r['room_type_name']) ?></small><br>
          <?php endforeach; ?></td>
      <td><?= inr($b['total']) ?></td>
      <td><?= inr($b['amount_paid']) ?>
          <?php if ($balance > 0.5 && $b['status'] !== 'cancelled'): ?>
            <br><small style="color:var(--warn)">due <?= inr($balance) ?></small><?php endif; ?></td>
      <td><span class="tag tag--<?= h($b['status']) ?>"><?= h($b['status']) ?></span></td>
      <td><?php if ($b['sf_sync_error']): ?><span class="tag tag--warn">failed</span>
          <?php elseif ($b['sf_synced_at']): ?><small style="color:var(--ok)">synced</small>
          <?php else: ?><small style="color:var(--muted)">—</small><?php endif; ?></td>
      <td><a class="btn btn--plain btn--sm" href="booking.php?id=<?= (int) $b['id'] ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php admin_foot(); ?>
