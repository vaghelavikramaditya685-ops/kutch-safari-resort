<?php
/* One booking, in full, with every action staff need. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
$user = require_login();
check_csrf();

$id = (int) ($_GET['id'] ?? 0);
$flash = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    switch ($_POST['action'] ?? '') {
        case 'record_payment':
            record_offline_payment($id, (float) $_POST['amount'], $_POST['method'], $user['name'], $_POST['note'] ?? '');
            $flash = 'Payment recorded.'; break;
        case 'cancel':
            $r = cancel_booking($id, $_POST['reason'] ?? '', $user['name']);
            $flash = $r['ok'] ? 'Cancelled. ' . $r['note'] : $r['error']; break;
        case 'resync':
            $r = channel_push_booking($id);
            $flash = $r['ok'] ? 'Sent to Stayflexi.' : 'Stayflexi refused it: ' . ($r['reason'] ?? ''); break;
        case 'mark_status':
            update('bookings', $id, ['status' => $_POST['status'], 'updated_at' => now()]);
            audit('status_changed', 'booking', $id, ['to' => $_POST['status']], $user['name']);
            $flash = 'Status updated.'; break;
        case 'note':
            update('bookings', $id, ['special_requests' => $_POST['special_requests'], 'updated_at' => now()]);
            $flash = 'Note saved.'; break;
    }
}

$b = get_booking($id);
if (!$b) { admin_head('Not found', $user); echo '<div class="notice notice--err">No such booking.</div>'; admin_foot(); exit; }
$balance = (float) $b['total'] - (float) $b['amount_paid'];

admin_head($b['ref'], $user);
?>
<p><a href="index.php">&larr; All bookings</a></p>
<?php if ($flash): ?><div class="notice notice--ok"><?= h($flash) ?></div><?php endif; ?>
<?php if ($b['sf_sync_error']): ?>
  <div class="notice notice--err"><strong>Not in Stayflexi.</strong>
    This room may still be on sale on the OTAs. <?= h(substr($b['sf_sync_error'], 0, 200)) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">
  <div>
    <div class="panel">
      <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h2 style="margin-bottom:.1em"><?= h($b['ref']) ?></h2>
          <p style="margin:0;color:var(--muted);font-size:.86rem"><?= h($b['property_name']) ?> ·
             booked <?= date('j M Y, g:i a', strtotime($b['created_at'])) ?> · via <?= h($b['source']) ?></p>
        </div>
        <span class="tag tag--<?= h($b['status']) ?>"><?= h($b['status']) ?></span>
      </div>

      <div class="summary__dates" style="margin-top:16px">
        <div><span>Check in</span><b><?= date('D j M Y', strtotime($b['check_in'])) ?></b></div>
        <div><span>Check out</span><b><?= date('D j M Y', strtotime($b['check_out'])) ?></b></div>
        <div><span>Guests</span><b><?= (int) $b['adults'] ?> adults<?= (int) $b['children'] ? ', ' . (int) $b['children'] . ' children' : '' ?></b></div>
      </div>

      <table class="grid" style="margin-top:16px">
        <tr><th>Room</th><th>Plan</th><th>Qty</th><th>Amount</th></tr>
        <?php foreach ($b['rooms'] as $r): ?>
          <tr><td><?= h($r['room_type_name']) ?></td><td><?= h($r['rate_plan_name']) ?></td>
              <td><?= (int) $r['rooms'] ?></td><td><?= inr($r['subtotal']) ?></td></tr>
        <?php endforeach; ?>
        <?php foreach ($b['addons'] as $a): ?>
          <tr><td colspan="2"><?= h($a['addon_name']) ?></td>
              <td><?= (int) $a['quantity'] ?></td><td><?= inr($a['subtotal']) ?></td></tr>
        <?php endforeach; ?>
        <tr><td colspan="3" style="text-align:right;color:var(--muted)">Taxes</td><td><?= inr($b['tax_amount']) ?></td></tr>
        <tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td><strong><?= inr($b['total']) ?></strong></td></tr>
        <tr><td colspan="3" style="text-align:right;color:var(--muted)">Paid</td><td><?= inr($b['amount_paid']) ?></td></tr>
        <?php if ($balance > 0.5): ?>
        <tr><td colspan="3" style="text-align:right;color:var(--warn)"><strong>Balance due</strong></td>
            <td style="color:var(--warn)"><strong><?= inr($balance) ?></strong></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="panel">
      <h3 style="font-size:1rem">Payments</h3>
      <?php if (!$b['payments']): ?><p style="font-size:.86rem;color:var(--muted)">Nothing recorded yet.</p><?php endif; ?>
      <?php foreach ($b['payments'] as $p): ?>
        <div class="sline">
          <span><?= h($p['provider']) ?><?= $p['method'] ? ' · ' . h($p['method']) : '' ?>
            <small><?= h($p['status']) ?><?= $p['verified_by'] ? ' · confirmed by ' . h($p['verified_by']) : '' ?>
              <?= $p['upi_ref'] ? ' · ref ' . h($p['upi_ref']) : '' ?></small></span>
          <span><?= inr($p['amount']) ?></span>
        </div>
      <?php endforeach; ?>

      <form method="post" style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="record_payment">
        <div class="field"><label>Amount</label>
          <input name="amount" type="number" step="0.01" value="<?= $balance > 0 ? round($balance, 2) : '' ?>" required></div>
        <div class="field"><label>Method</label>
          <select name="method"><option>cash</option><option>card</option><option>bank transfer</option><option>upi</option></select></div>
        <div class="field"><label>Note</label><input name="note" placeholder="Taken at reception"></div>
        <button class="btn btn--sm" type="submit">Record</button>
      </form>
    </div>

    <div class="panel">
      <h3 style="font-size:1rem">Notes from the guest</h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="note">
        <div class="field"><textarea name="special_requests" rows="3"><?= h($b['special_requests']) ?></textarea></div>
        <button class="btn btn--plain btn--sm" type="submit" style="margin-top:10px">Save note</button>
      </form>
    </div>
  </div>

  <aside>
    <div class="panel">
      <h3 style="font-size:1rem">Guest</h3>
      <p style="margin:0 0 4px"><strong><?= h($b['guest_name']) ?></strong></p>
      <p style="margin:0 0 4px"><a href="tel:<?= h($b['guest_phone']) ?>"><?= h($b['guest_phone']) ?></a></p>
      <?php if ($b['guest_email']): ?><p style="margin:0 0 4px"><a href="mailto:<?= h($b['guest_email']) ?>"><?= h($b['guest_email']) ?></a></p><?php endif; ?>
      <?php if ($b['guest_city']): ?><p style="margin:0;color:var(--muted);font-size:.86rem"><?= h($b['guest_city']) ?></p><?php endif; ?>
      <?php if ($b['arrival_time']): ?><p style="margin:8px 0 0;font-size:.86rem">Arriving about <?= h($b['arrival_time']) ?></p><?php endif; ?>
      <a class="btn btn--ghost btn--sm btn--block" style="margin-top:12px" target="_blank" rel="noopener"
         href="https://wa.me/<?= h(preg_replace('/\D/', '', $b['guest_phone'])) ?>">Message on WhatsApp</a>
    </div>

    <div class="panel">
      <h3 style="font-size:1rem">Actions</h3>
      <form method="post" style="margin-bottom:10px">
        <?= csrf_field() ?><input type="hidden" name="action" value="mark_status">
        <div class="field"><label>Set status</label>
          <select name="status">
            <?php foreach (['pending','confirmed','completed','no_show'] as $s): ?>
              <option value="<?= $s ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select></div>
        <button class="btn btn--plain btn--sm btn--block" style="margin-top:8px" type="submit">Update</button>
      </form>

      <form method="post" style="margin-bottom:10px">
        <?= csrf_field() ?><input type="hidden" name="action" value="resync">
        <button class="btn btn--plain btn--sm btn--block" type="submit">Send to Stayflexi</button>
      </form>

      <?php if ($b['status'] !== 'cancelled'): ?>
      <form method="post" onsubmit="return confirm('Cancel <?= h($b['ref']) ?>? The rooms go back on sale.')">
        <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
        <div class="field"><label>Reason</label><input name="reason" placeholder="Guest called"></div>
        <button class="btn btn--sm btn--block" style="margin-top:8px;background:var(--err);border-color:var(--err)"
                type="submit">Cancel booking</button>
      </form>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php admin_foot(); ?>
