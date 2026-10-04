<?php
/* Bookings list — the screen reservations staff live in. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
$user = require_login();
check_csrf();

/* --- Actions ----------------------------------------------------------- */
$flash = ''; $flash_bad = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Payments and cancelling are done on the booking's own page; this list only confirms UPI.
    if (($_POST['action'] ?? '') === 'confirm_upi') {
        if (too_long($_POST['bank_ref'] ?? '', 'payment_note')) {
            $flash = 'Please keep the bank reference under ' . LIMITS['payment_note'] . ' characters.'; $flash_bad = true;
        } else {
            $r = upi_mark_received((int) ($_POST['payment_id'] ?? 0), $user['name'], trim((string) ($_POST['bank_ref'] ?? '')));
            $flash = !$r['ok'] ? $r['error'] : ($r['confirmed'] ? 'Payment marked as received and the booking confirmed.'
                : 'Payment recorded. The booking was already paid another way, so this money is owed back — open the booking to record the refund.');
            $flash_bad = !$r['ok'];
        }
    }
}

/* --- Open a booking by its code ("Cancel a booking" box) ------------------ */
if (isset($_GET['open'])) {
    $code = strtoupper(trim((string) $_GET['open']));
    $hit = $code !== '' ? q1("SELECT id FROM bookings WHERE ref = ?", [$code]) : null;
    if ($hit) { header('Location: booking.php?id=' . (int) $hit['id'] . '#cancel'); exit; }
    $open_error = $code === '' ? 'Enter a booking code.' : "No booking has the code $code.";
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
        WHERE ' . implode(' AND ', $where) . ' ORDER BY b.check_in DESC LIMIT 1000';
$bookings = q($sql, $params);

/* --- The list reads like the day at the hotel ----------------------------
 * Staying now → coming up → cancelled → finished. Worked out every time the
 * list opens, so it re-sorts itself each day. Finished and cancelled bookings
 * older than admin.list_clear_days drop off the list (never deleted); "Show
 * older bookings", a search or a filter brings them back.
 */
$today_ymd = date('Y-m-d');
$clear_days = max(1, (int) cfg('admin.list_clear_days', 15));
$clear_before = date('Y-m-d', strtotime("-$clear_days days"));
$show_older = !empty($_GET['older']) || $search !== '' || $status !== '' || $from !== '' || $to !== '';
$groups = ['now' => [], 'upcoming' => [], 'cancelled' => [], 'finished' => []];
$hidden = 0;
foreach ($bookings as $bk) {
    $lapsed = booking_lapsed($bk);
    if ($bk['status'] === 'cancelled' || $bk['status'] === 'no_show' || $lapsed) {
        $g = 'cancelled';
        $when = substr((string) ($bk['cancelled_at'] ?: ($lapsed ? $bk['created_at'] : $bk['updated_at'])), 0, 10);
    } elseif ($bk['check_out'] <= $today_ymd || $bk['status'] === 'completed') {
        $g = 'finished';
        $when = $bk['check_out'];
    } elseif ($bk['check_in'] <= $today_ymd) {
        $g = 'now';
        $when = null;
    } else {
        $g = 'upcoming';
        $when = null;
    }
    if ($when !== null && $when < $clear_before && !$show_older) { $hidden++; continue; }
    $bk['_group'] = $g;
    $groups[$g][] = $bk;
}
usort($groups['now'],       fn($a, $b) => strcmp($a['check_out'], $b['check_out']));
usort($groups['upcoming'],  fn($a, $b) => strcmp($a['check_in'], $b['check_in']));
usort($groups['cancelled'], fn($a, $b) => strcmp((string) ($b['cancelled_at'] ?: $b['updated_at']), (string) ($a['cancelled_at'] ?: $a['updated_at'])));
usort($groups['finished'],  fn($a, $b) => strcmp($b['check_out'], $a['check_out']));
$group_titles = ['now' => 'Staying now', 'upcoming' => 'Coming up', 'cancelled' => 'Cancelled / not paid / no-show', 'finished' => 'Finished'];

/* --- Today's numbers --------------------------------------------------- */
$today = date('Y-m-d');
$stats = [
    'Arriving today'  => qval("SELECT COUNT(*) FROM bookings WHERE check_in = ? AND status = 'confirmed'", [$today], 0),
    'In house'        => qval("SELECT COUNT(*) FROM bookings WHERE check_in <= ? AND check_out > ? AND status = 'confirmed'", [$today, $today], 0),
    'Awaiting payment'=> qval("SELECT COUNT(*) FROM bookings WHERE status = 'pending'", [], 0),
    'New enquiries'   => qval("SELECT COUNT(*) FROM enquiries WHERE status = 'new'", [], 0),
];
$unsynced = q("SELECT id, ref FROM bookings WHERE sf_sync_error IS NOT NULL");
// A cancelled booking's QR is withdrawn, so it is not listed. One already paid another
// way stays listed (flagged): if this money arrived too, it was paid twice and is owed back.
$pending_upi = q("SELECT pay.*, b.ref, b.guest_name, b.status AS booking_status FROM payments pay
                   JOIN bookings b ON b.id = pay.booking_id
                  WHERE pay.provider = 'upi_qr' AND pay.status = 'awaiting_confirmation' AND b.status IN ('pending', 'confirmed')
                  ORDER BY pay.id DESC");
$upi_hours = upi_hold_hours();

admin_head('Bookings', $user);
?>
<?php if ($flash): ?><div class="notice <?= $flash_bad ? 'notice--err' : 'notice--ok' ?>" role="<?= $flash_bad ? 'alert' : 'status' ?>"><?= h($flash) ?></div><?php endif; ?>

<div class="stat-row">
  <?php foreach ($stats as $label => $value): ?>
    <div class="stat-card"><b><?= (int) $value ?></b><span><?= h($label) ?></span></div>
  <?php endforeach; ?>
</div>

<?php if ($pending_upi): ?>
<div class="panel" style="border-left-color:var(--warn)">
  <h2 style="font-size:1rem">UPI payments waiting to be checked</h2>
  <p style="font-size:.85rem">These guests scanned the QR code. Check the bank account, then confirm —
     the booking only becomes confirmed once you do. A QR not confirmed within <?= $upi_hours ?> hours stops holding
     its cottage (it can still be confirmed if the money arrives, as long as the cottage is free).</p>
  <table class="grid" style="margin-top:10px">
    <tr><th>Booking</th><th>Guest</th><th>Amount</th><th>Reference on the statement</th><th>Waiting</th><th>Confirm</th></tr>
    <?php foreach ($pending_upi as $p):
      $age_h = (int) floor((time() - strtotime($p['created_at'])) / 3600); ?>
    <tr>
      <td><a href="?q=<?= h($p['ref']) ?>"><?= h($p['ref']) ?></a></td>
      <td><?= h($p['guest_name']) ?></td>
      <td><?= inr($p['amount']) ?></td>
      <td><code><?= h($p['upi_ref']) ?></code></td>
      <td><?= $age_h < 1 ? 'under an hour' : ($age_h < 48 ? $age_h . ' h' : floor($age_h / 24) . ' days') ?>
          <?php if ($p['booking_status'] === 'confirmed'): ?><br><small style="color:var(--warn)">already paid another way — confirm only if this money also arrived (it is then owed back)</small>
          <?php elseif ($age_h >= $upi_hours): ?><br><small style="color:var(--err)">no longer holding the cottage</small><?php endif; ?></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="confirm_upi">
          <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
          <input name="bank_ref" placeholder="Bank ref (optional)" aria-label="Bank reference for <?= h($p['ref']) ?> (optional)" maxlength="300" style="padding:6px 8px;border:1px solid var(--line);border-radius:3px">
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
  <strong><?= count($unsynced) ?> booking(s) or cancellation(s) have not reached Stayflexi yet.</strong>
  They are retried automatically every hour. If one stays here, enter it in Stayflexi by hand:
  <?php foreach ($unsynced as $u): ?><a href="?q=<?= h($u['ref']) ?>"><?= h($u['ref']) ?></a> <?php endforeach; ?>
</div>
<?php endif; ?>

<form class="panel opencode" method="get">
  <div>
    <h2 style="font-size:1rem;margin:0 0 4px">Cancel a booking</h2>
    <p style="font-size:.82rem;margin:0;color:var(--muted)">Guests cannot cancel online — they call or WhatsApp you.
       Enter their booking code to open it and cancel.</p>
  </div>
  <div class="opencode__row">
    <input name="open" aria-label="Booking code" placeholder="Booking code, e.g. KSR-4XY7PQ" autocomplete="off" autocapitalize="characters"
           style="text-transform:uppercase" value="<?= h($_GET['open'] ?? '') ?>">
    <button class="btn btn--sm" type="submit">Open booking</button>
  </div>
  <?php if (!empty($open_error)): ?><div class="notice notice--err" style="margin:8px 0 0"><?= h($open_error) ?></div><?php endif; ?>
</form>

<form class="filters" method="get">
  <div class="field"><label for="f-q">Search</label>
    <input id="f-q" name="q" value="<?= h($search) ?>" placeholder="Reference, name or phone"></div>
  <div class="field"><label for="f-status">Status</label>
    <select id="f-status" name="status">
      <option value="">All</option>
      <?php foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show', 'completed' => 'Left early'] as $s => $label): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="field"><label for="f-property">Property</label>
    <select id="f-property" name="property">
      <option value="">Both</option>
      <?php foreach (q("SELECT id, name FROM properties WHERE active = 1 ORDER BY id") as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $property === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="field"><label for="f-from">Staying between</label><input type="date" id="f-from" name="from" value="<?= h($from) ?>"></div>
  <div class="field"><label for="f-to">and</label><input type="date" id="f-to" name="to" value="<?= h($to) ?>"></div>
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
  <?php if (!array_filter($groups)): ?>
    <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:30px">No bookings match that.</td></tr>
  <?php endif; ?>
  <?php foreach ($groups as $gkey => $list): if (!$list) continue; ?>
    <tr class="grp grp--<?= $gkey ?>"><td colspan="10"><?= $group_titles[$gkey] ?> <span><?= count($list) ?></span></td></tr>
  <?php foreach ($list as $b):
      $rooms = q("SELECT br.room_type_name, SUM(br.rooms) AS rooms FROM booking_rooms br
                   LEFT JOIN room_types rt ON rt.id = br.room_type_id
                  WHERE br.booking_id = ? GROUP BY br.room_type_id, br.room_type_name
                  ORDER BY MIN(COALESCE(rt.sort_order, 99)), br.room_type_id", [$b['id']]);
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
          <?php // The same split as the booking page: what is due now, and what is due later.
            $mny = booking_money($b);
            if ($b['status'] === 'cancelled'): $cm = cancellation_money($b);
              if ($cm['outstanding'] > 0.5): ?><br><small style="color:var(--ok)">refund <?= inr($cm['outstanding']) ?> owed</small><?php endif;
            elseif (!booking_lapsed($b) && $b['status'] !== 'no_show'):
              if ($mny['due_now'] > 0.5): ?><br><small style="color:var(--warn)"><?= $mny['arrived'] ? 'collect' : 'due now' ?> <?= inr($mny['due_now']) ?></small><?php endif;
              if ($mny['later'] > 0.5): ?><br><small style="color:var(--muted)"><?= inr($mny['later']) ?> before arrival</small><?php endif;
              if ($mny['refund'] > 0.5): ?><br><small style="color:var(--ok)">refund <?= inr($mny['refund']) ?></small><?php endif;
            endif; ?></td>
      <td><span class="tag tag--<?= h($b['status']) ?>"><?= h(match (true) {
            $b['status'] === 'cancelled' => 'cancelled',
            $b['status'] === 'no_show'   => 'no-show',
            $b['status'] === 'completed' => 'left early',
            booking_lapsed($b)            => 'not paid',
            $b['status'] === 'pending'    => 'awaiting payment',
            $b['_group'] === 'now'        => 'staying',
            $b['_group'] === 'finished'   => 'checked out',
            default                       => $b['status'],
          }) ?></span></td>
      <td><?php if ($b['sf_sync_error']): ?><span class="tag tag--warn">failed</span>
          <?php elseif ($b['sf_synced_at']): ?><small style="color:var(--ok)">synced</small>
          <?php else: ?><small style="color:var(--muted)">—</small><?php endif; ?></td>
      <td><a class="btn btn--plain btn--sm" href="booking.php?id=<?= (int) $b['id'] ?>">Open</a></td>
    </tr>
  <?php endforeach; endforeach; ?>
  </tbody>
</table>
<?php if ($hidden): ?>
  <p class="older"><a href="?older=1"><?= $hidden ?> older booking<?= $hidden > 1 ? 's' : '' ?></a>
    — finished or cancelled more than <?= $clear_days ?> days ago — kept, but off the list. Show them.</p>
<?php elseif (!empty($_GET['older'])): ?>
  <p class="older"><a href="index.php">Hide finished and cancelled bookings older than <?= $clear_days ?> days</a></p>
<?php endif; ?>
<?php admin_foot(); ?>
