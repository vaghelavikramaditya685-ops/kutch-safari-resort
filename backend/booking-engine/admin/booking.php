<?php
/* One booking, in full, with every action staff need. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
$user = require_login();
check_csrf();

$id = (int) ($_GET['id'] ?? 0);
$flash = ''; $flash_bad = false;   // $flash_bad: show the message as an error, not a success
$methods = ['cash', 'card', 'bank transfer', 'upi'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $refuse = function (string $msg) use (&$flash, &$flash_bad) { $flash = $msg; $flash_bad = true; };
    switch ($_POST['action'] ?? '') {
        // The amount checks (up to the balance, or up to what is owed back) run inside
        // record_offline_payment()/record_offline_refund(), under the booking's lock.
        case 'record_payment':
            $amt = round((float) ($_POST['amount'] ?? 0), 2);
            if (!in_array($_POST['method'] ?? '', $methods, true)) { $refuse('Choose how it was paid from the list.'); break; }
            if (too_long($_POST['note'] ?? '', 'payment_note')) { $refuse('Please keep the note under ' . LIMITS['payment_note'] . ' characters.'); break; }
            $r = record_offline_payment($id, $amt, $_POST['method'], $user['name'], trim((string) ($_POST['note'] ?? '')));
            if (!$r['ok']) { $refuse($r['error']); break; }
            $flash = 'Payment of ' . inr($amt) . ' recorded.' . (!empty($r['confirmed']) ? ' The booking is now confirmed.' : ''); break;
        case 'record_refund':
            $amt = round((float) ($_POST['amount'] ?? 0), 2);
            if (!in_array($_POST['method'] ?? '', $methods, true)) { $refuse('Choose how it was given back from the list.'); break; }
            if (too_long($_POST['note'] ?? '', 'payment_note')) { $refuse('Please keep the note under ' . LIMITS['payment_note'] . ' characters.'); break; }
            $r = record_offline_refund($id, $amt, $_POST['method'], $user['name'], trim((string) ($_POST['note'] ?? '')));
            if (!$r['ok']) { $refuse($r['error']); break; }
            $flash = 'Refund of ' . inr($amt) . ' recorded.'; break;
        case 'no_show':
        case 'left_early':
            if (too_long($_POST['reason'] ?? '', 'cancel_reason')) { $refuse('Please keep the note under ' . LIMITS['cancel_reason'] . ' characters.'); break; }
            $r = end_stay_early($id, $_POST['action'] === 'no_show' ? 'no_show' : 'completed', $user['name'], trim((string) ($_POST['reason'] ?? '')));
            if (!$r['ok']) { $refuse($r['error']); break; }
            $flash = $_POST['action'] === 'no_show'
                ? 'Marked as a no-show. The cottages are back on sale from today; the money stays as it is.'
                : 'Marked as left early. The cottages are back on sale from today; the money stays as it is. Shorten the stay in Stayflexi by hand if it is connected.';
            break;
        case 'cancel':
            if (too_long($_POST['reason'] ?? '', 'cancel_reason')) { $refuse('Please keep the reason under ' . LIMITS['cancel_reason'] . ' characters.'); break; }
            $r = cancel_booking($id, trim((string) ($_POST['reason'] ?? '')), $user['name']);
            $flash = $r['ok'] ? 'Cancelled. ' . $r['note'] : $r['error'];
            $flash_bad = !$r['ok'];
            // Money taken by card goes back automatically; anything else is refunded by the desk.
            if ($r['ok'] && $r['refund_due'] > 0 && razorpay_enabled()) {
                $rf = razorpay_refund($id, (float) $r['refund_due'], 'Cancelled by ' . $user['name']);
                $flash .= !empty($rf['ok']) ? ' Razorpay refund started.' : ' Razorpay refund failed — refund by hand.';
            }
            break;
        case 'note':
            if (too_long($_POST['special_requests'] ?? '', 'staff_note')) { $refuse('Please keep the note under ' . number_format(LIMITS['staff_note']) . ' characters.'); break; }
            update('bookings', $id, ['special_requests' => trim((string) ($_POST['special_requests'] ?? '')), 'updated_at' => now()]);
            $flash = 'Note saved.'; break;
    }
}

$b = get_booking($id);
if (!$b) { http_response_code(404); admin_head('Not found', $user); echo '<div class="notice notice--err">No such booking.</div>'; admin_foot(); exit; }
$balance = (float) $b['total'] - (float) $b['amount_paid'];
$money = booking_money($b);
$closed = in_array($b['status'], ['cancelled', 'no_show'], true);   // nothing more to collect
$cm = $b['status'] === 'cancelled' ? cancellation_money($b) : null;
$today_ymd = date('Y-m-d');

admin_head($b['ref'], $user);
?>
<p><a href="index.php">&larr; All bookings</a></p>
<?php if ($flash): ?><div class="notice <?= $flash_bad ? 'notice--err' : 'notice--ok' ?>" role="<?= $flash_bad ? 'alert' : 'status' ?>"><?= h($flash) ?></div><?php endif; ?>
<?php if (!empty($_GET['changed']) && !$flash): $d = round((float) $b['total'] - (float) $b['amount_paid'], 2);
  $last = json_decode((string) (q1("SELECT detail FROM audit_log WHERE action = 'booking_modified' AND entity_id = ? ORDER BY id DESC LIMIT 1", [(string) $b['id']])['detail'] ?? ''), true);
  $ch = $last['changes'] ?? null; ?>
  <div class="notice notice--ok"><strong>Booking changed.</strong>
    <?php if ($ch): ?>Old total <?= inr($last['total_from']) ?><?= $ch['added'] ? ' + added ' . inr($ch['added_total']) : '' ?><?= $ch['removed'] ? ' − taken off ' . inr(-$ch['removed_total']) : '' ?><?= $ch['changed'] ? ($ch['changed_total'] >= 0 ? ' + ' : ' − ') . 'changed ' . inr(abs($ch['changed_total'])) : '' ?> = new total <?= inr($b['total']) ?>.
    <?php else: ?>New total <?= inr($b['total']) ?>.<?php endif; ?>
    <?= $money['refund'] > 0.5 ? 'Give back ' . inr($money['refund']) . ' to the guest — record it under Payments.'
      : ($money['due_now'] > 0.5 ? 'Collect ' . inr($money['due_now']) . ' now' . ($money['later'] > 0.5 ? ' and ' . inr($money['later']) . ' before arrival' : '') . ' — record it under Payments.'
      : ($money['later'] > 0.5 ? 'Nothing to collect now; ' . inr($money['later']) . ' is due before arrival.' : 'Nothing to collect or give back.')) ?>
    The guest sees the new details under "Already booked? Check status" and on the receipt.</div>
<?php endif; ?>
<?php if ($b['sf_sync_error']): ?>
  <div class="notice notice--err"><strong>Stayflexi has not been updated yet.</strong>
    It is retried automatically every hour. <?= h(substr($b['sf_sync_error'], 0, 200)) ?></div>
<?php endif; ?>

<?php
/* One card per room: which cottage, how many sleep in it, extra bed, price.
   Bookings store one line per room; older ones may hold several rooms on a
   line, with the occupancy written into the plan name ("Room 1 Double, …"). */
$incl = prices_include_tax(['code' => $b['property_code']]);
$room_cards = [];
foreach ($b['rooms'] as $line) {
    $rt = q1("SELECT base_occupancy, extra_adult_price FROM room_types WHERE id = ?", [$line['room_type_id']]);
    [$plan] = explode(' — ', (string) $line['rate_plan_name'], 2) + [''];
    preg_match_all('/Room (\d+) (Single|Double|Triple)/', (string) $line['rate_plan_name'], $m, PREG_SET_ORDER);
    $n = max(1, (int) $line['rooms']);
    $nightly = json_decode((string) $line['nightly'], true) ?: [];
    $amount = ($incl ? (float) $line['subtotal'] + (float) $line['tax_amount'] : (float) $line['subtotal']) / $n;
    for ($i = 0; $i < $n; $i++) {
        $label = $m[$i][2] ?? null;
        $guests = $label ? ['Single' => 1, 'Double' => 2, 'Triple' => 3][$label] : (int) round((int) $line['adults'] / $n);
        $extra = max(0, $guests - (int) ($rt['base_occupancy'] ?? 2));
        $room_cards[] = [
            'no' => isset($m[$i][1]) ? (int) $m[$i][1] : count($room_cards) + 1,
            'cottage' => $line['room_type_name'], 'plan' => $plan, 'label' => $label ?: "$guests guests",
            'guests' => $guests, 'extra' => $extra, 'extra_price' => (float) ($rt['extra_adult_price'] ?? 0),
            'nights' => count($nightly) ?: (int) $b['nights'], 'amount' => $amount,
        ];
    }
}
// Similar rooms together: cottage in the site's order (as get_booking returns them), then room number.
$type_order = array_flip(array_values(array_unique(array_column($room_cards, 'cottage'))));
usort($room_cards, fn($x, $y) => [$type_order[$x['cottage']], $x['no']] <=> [$type_order[$y['cottage']], $y['no']]);
$rooms_total  = array_sum(array_column($room_cards, 'amount'));
$extras_total = array_sum(array_map(fn($a) => $incl ? (float) $a['subtotal'] + (float) $a['tax_amount'] : (float) $a['subtotal'], $b['addons']));
$received = array_filter($b['payments'], fn($p) => in_array($p['status'], ['paid', 'refunded'], true));
$attempts = array_filter($b['payments'], fn($p) => !in_array($p['status'], ['paid', 'refunded'], true));
$status_word = ['confirmed' => $b['check_out'] <= $today_ymd ? 'Checked out' : ($b['check_in'] <= $today_ymd ? 'Staying' : 'Confirmed'),
                'pending' => booking_lapsed($b) ? 'Not paid' : 'Awaiting payment',
                'cancelled' => 'Cancelled', 'no_show' => 'No-show', 'completed' => 'Left early'][$b['status']] ?? ucfirst((string) $b['status']);
?>
<div class="bk">
  <div class="bk__main">
    <div class="panel bk__head">
      <div class="bk__title">
        <div>
          <h2><?= h($b['ref']) ?> <span class="bk__status bk__status--<?= h($b['status']) ?>"><?= h($status_word) ?></span></h2>
          <p><?= h($b['property_name']) ?> · booked <?= date('j M Y, g:i a', strtotime($b['created_at'])) ?> · via <?= h($b['source']) ?>
             <?php if ($mod = booking_modified_at((int) $b['id'])): ?> · changed <?= date('j M Y, g:i a', strtotime($mod)) ?><?php endif; ?></p>
        </div>
        <div class="bk__buttons">
          <a class="btn btn--plain btn--sm" target="_blank" rel="noopener"
             href="../document.php?doc=receipt&amp;ref=<?= urlencode($b['ref']) ?>">Receipt (PDF)</a>
          <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
          <a class="btn btn--sm" href="edit.php?id=<?= (int) $b['id'] ?>">Change this booking</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="bk__facts">
        <div><span>Check in</span><b><?= date('D j M Y', strtotime($b['check_in'])) ?></b><small>from <?= h($b['check_in_time']) ?></small></div>
        <div><span>Check out</span><b><?= date('D j M Y', strtotime($b['check_out'])) ?></b><small>by <?= h($b['check_out_time']) ?></small></div>
        <div><span>Nights</span><b><?= (int) $b['nights'] ?></b></div>
        <div><span>Rooms</span><b><?= count($room_cards) ?></b></div>
        <div><span>Guests</span><b><?= (int) $b['adults'] ?></b><small><?= (int) $b['children'] ? (int) $b['children'] . ' children' : 'adults' ?></small></div>
      </div>
    </div>

    <div class="panel">
      <table class="grid bk__table">
        <tr><th>Room</th><th>Cottage</th><th>Guests</th><th>Extra bed</th><th>Plan</th><th>Nights</th><th class="num">Amount</th></tr>
        <?php foreach ($room_cards as $rc): ?>
          <tr>
            <td>Room <?= $rc['no'] ?></td>
            <td><?= h($rc['cottage']) ?></td>
            <td><?= h($rc['label']) ?> · <?= $rc['guests'] ?></td>
            <td><?= $rc['extra'] ? '<b class="bk__yes">Yes' . ($rc['extra'] > 1 ? ' × ' . $rc['extra'] : '') . '</b>' : '<span style="color:var(--muted)">No</span>' ?></td>
            <td><?= h($rc['plan']) ?></td>
            <td><?= $rc['nights'] ?></td>
            <td class="num"><?= inr($rc['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php foreach ($b['addons'] as $a): ?>
          <tr>
            <td colspan="5"><?= h($a['addon_name']) ?> <small style="color:var(--muted)">· <?= inr((float) $a['unit_price']) ?> each</small></td>
            <td><?= (int) $a['quantity'] ?></td>
            <td class="num"><?= inr($incl ? (float) $a['subtotal'] + (float) $a['tax_amount'] : (float) $a['subtotal']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if ((float) $b['discount'] > 0): ?>
          <tr><td colspan="6" class="num" style="color:var(--muted)">Discount<?= $b['coupon_code'] ? ' (' . h($b['coupon_code']) . ')' : '' ?></td><td class="num">− <?= inr($b['discount']) ?></td></tr>
        <?php endif; ?>
        <?php if (!$incl): ?>
          <tr><td colspan="6" class="num" style="color:var(--muted)">GST</td><td class="num"><?= inr($b['tax_amount']) ?></td></tr>
        <?php endif; ?>
        <tr class="bk__total"><td colspan="6" class="num"><strong>Total</strong><?= $incl ? ' <small style="color:var(--muted)">includes GST ' . inr($b['tax_amount']) . '</small>' : '' ?></td>
            <td class="num"><strong><?= inr($b['total']) ?></strong></td></tr>
        <tr><td colspan="6" class="num" style="color:var(--muted)">Paid</td><td class="num"><?= inr($b['amount_paid']) ?></td></tr>
        <?php if ($cm): ?>
          <?php if ($cm['percent'] !== null): ?>
          <tr><td colspan="6" class="num">Cancellation charge <?= h(rtrim(rtrim(number_format($cm['percent'], 2), '0'), '.')) ?>%
                <?= $cm['charge'] !== null && $cm['charge'] > $cm['kept'] + 0.5 ? '<small style="color:var(--muted)">(' . inr($cm['charge']) . ', but only ' . inr($cm['kept']) . ' was paid — nothing more is collected)</small>' : '' ?></td>
              <td class="num"><?= inr($cm['kept']) ?></td></tr>
          <?php endif; ?>
          <?php if ($cm['refund'] > 0.5): ?>
          <tr><td colspan="6" class="num" style="color:var(--ok)"><strong>Refund owed</strong><?= $cm['given'] > 0.5 ? ' <small>(' . inr($cm['given']) . ' given back so far)</small>' : '' ?></td>
              <td class="num" style="color:var(--ok)"><strong><?= inr($cm['outstanding']) ?></strong></td></tr>
          <?php endif; ?>
        <?php elseif (!$closed && $balance > 0.5): ?>
          <?php if ($money['due_now'] > 0.5): ?>
          <tr><td colspan="6" class="num" style="color:var(--warn)"><strong><?= due_now_label($money) ?></strong><?= $money['mode'] === 'advance' && !$money['arrived'] ? ' <small>(to make up the ' . (int) $money['percent'] . '% advance)</small>' : '' ?></td>
              <td class="num" style="color:var(--warn)"><strong><?= inr($money['due_now']) ?></strong></td></tr>
          <?php endif; ?>
          <?php if ($money['later'] > 0.5): ?>
          <tr><td colspan="6" class="num"><strong>Due before arrival</strong></td>
              <td class="num"><strong><?= inr($money['later']) ?></strong></td></tr>
          <?php endif; ?>
        <?php elseif (!$closed && $balance < -0.5): ?>
          <tr><td colspan="6" class="num" style="color:var(--ok)"><strong>Refund due to guest</strong></td>
              <td class="num" style="color:var(--ok)"><strong><?= inr(-$balance) ?></strong></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="panel" id="payments">
      <h3 class="bk__h">Payments</h3>
      <?php if (!$received): ?><p style="font-size:.86rem;color:var(--muted)">No money received yet.</p><?php endif; ?>
      <?php foreach ($received as $p): ?>
        <div class="sline">
          <span><?= $p['purpose'] === 'refund' ? 'Refund to guest' . ($p['method'] ? ' · ' . h($p['method']) : '')
                   : ($p['provider'] === 'test' ? 'Test payment — no money taken'
                   : h(['razorpay' => 'Razorpay (online)', 'upi_qr' => 'UPI to bank', 'offline' => 'Paid at the property'][$p['provider']] ?? $p['provider'])
                     . ($p['method'] ? ' · ' . h($p['method']) : '')) ?>
            <small><?= date('j M Y, g:i a', strtotime($p['paid_at'] ?: $p['created_at'])) ?>
              <?= $p['verified_by'] ? ' · by ' . h($p['verified_by']) : '' ?><?= $p['upi_ref'] ? ' · ref ' . h($p['upi_ref']) : '' ?></small></span>
          <span><?= inr($p['amount']) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if ($attempts): ?>
        <details class="bk__attempts"><summary><?= count($attempts) ?> payment attempt<?= count($attempts) > 1 ? 's' : '' ?> not completed</summary>
        <?php foreach ($attempts as $p): ?>
          <div class="sline"><span><?= h(['razorpay' => 'Online payment started', 'upi_qr' => 'UPI QR shown', 'test' => 'Test payment started'][$p['provider']] ?? $p['provider']) ?>
            <small><?= date('j M, g:i a', strtotime($p['created_at'])) ?> · <?= h(['awaiting_confirmation' => 'waiting for you to check the bank',
                     'void' => 'withdrawn — the booking was cancelled', 'created' => 'not finished'][$p['status']] ?? $p['status']) ?></small></span>
            <span style="color:var(--muted)"><?= inr($p['amount']) ?></span></div>
        <?php endforeach; ?>
        </details>
      <?php endif; ?>

      <?php $owed_back = $cm ? $cm['outstanding'] : (!$closed ? -$balance : 0); ?>
      <?php if ($owed_back > 0.5): ?>
      <h4 style="font-size:.86rem;margin:16px 0 0">Give back to the guest — <?= inr($owed_back) ?> owed</h4>
      <p style="font-size:.8rem;color:var(--muted);margin:2px 0 0"><?= $cm ? 'The refund the cancellation left owing' . ($cm['given'] > 0.5 ? ', less what has been given back already' : '') . '.'
         : 'The booking was changed and now costs less than was paid.' ?></p>
      <form method="post" class="payform">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="record_refund">
        <div class="field"><label for="rf-amount">Amount</label>
          <input id="rf-amount" name="amount" type="number" step="0.01" min="1" max="<?= round($owed_back, 2) ?>" value="<?= round($owed_back, 2) ?>" required></div>
        <div class="field"><label for="rf-method">Method</label>
          <select id="rf-method" name="method"><option>cash</option><option>bank transfer</option><option>upi</option><option>card</option></select></div>
        <div class="field"><label for="rf-note">Note</label><input id="rf-note" name="note" maxlength="300" placeholder="Given back at reception"></div>
        <button class="btn btn--sm" type="submit">Record refund</button>
      </form>
      <?php elseif ($closed): ?>
      <?php elseif ($balance <= 0.5): ?>
      <p class="notice notice--ok" style="margin:14px 0 0">Fully paid — nothing left to collect.</p>
      <?php else: ?>
      <h4 style="font-size:.86rem;margin:16px 0 0"><?= $money['arrived'] ? 'Collect at the desk' : 'Collect the balance' ?> — <?= inr($balance) ?> due<?php if ($money['mode'] === 'advance' && !$money['arrived']): ?>
        <small class="muted">(<?= inr($money['due_now']) ?> now, <?= inr($money['later']) ?> before arrival)</small><?php endif; ?></h4>
      <form method="post" class="payform">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="record_payment">
        <div class="field"><label for="pay-amount">Amount</label>
          <input id="pay-amount" name="amount" type="number" step="0.01" min="1" max="<?= round($balance, 2) ?>" value="<?= round($balance, 2) ?>" required></div>
        <div class="field"><label for="pay-method">Method</label>
          <select id="pay-method" name="method"><option>cash</option><option>card</option><option>bank transfer</option><option>upi</option></select></div>
        <div class="field"><label for="pay-note">Note</label><input id="pay-note" name="note" maxlength="300" placeholder="Taken at reception"></div>
        <button class="btn btn--sm" type="submit">Record</button>
      </form>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h3 class="bk__h" id="notes-h">Notes</h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="note">
        <div class="field"><textarea name="special_requests" rows="3" maxlength="2000" aria-labelledby="notes-h"><?= h($b['special_requests']) ?></textarea></div>
        <button class="btn btn--plain btn--sm" type="submit" style="margin-top:10px">Save note</button>
      </form>
    </div>
  </div>

  <aside class="bk__side">
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
      <p style="font-size:.8rem;color:var(--muted);margin:0 0 12px">
        The status changes by itself: confirmed when paid, cancelled when you cancel.
        Stayflexi is updated automatically either way.</p>

      <?php if ($b['status'] === 'cancelled' || $b['status'] === 'no_show' || $b['status'] === 'completed'): ?>
        <p style="font-size:.84rem;margin:0">
          <?= ['cancelled' => 'Cancelled', 'no_show' => 'Marked as a no-show', 'completed' => 'Marked as left early'][$b['status']] ?>
          <?= $b['cancelled_at'] ? 'on ' . date('j M Y, g:i a', strtotime($b['cancelled_at'])) : '' ?>.
          <?php if ($b['cancel_reason']): ?><br><span style="color:var(--muted)">Reason: <?= h($b['cancel_reason']) ?></span><?php endif; ?></p>
      <?php elseif ($b['check_in'] >= $today_ymd): ?>
      <form method="post" id="cancel" class="cancelbox" data-confirm="Cancel <?= h($b['ref']) ?>? The rooms go back on sale." data-ok="Yes, cancel it" data-danger="1">
        <h3 style="font-size:.95rem;margin:0 0 6px">Cancel this booking</h3>
        <?php // The same calendar-day rule cancel_booking() charges by.
          $pct = cancellation_percent($b['check_in']); $charge = money((float) $b['total'] * $pct / 100);
          $back = max(0, money((float) $b['amount_paid'] - $charge)); ?>
        <p style="font-size:.8rem;margin:0 0 8px;color:var(--muted)">
          Charge if cancelled today: <strong><?= h(rtrim(rtrim(number_format($pct, 2), '0'), '.')) ?>%</strong>
          (<?= inr($charge) ?>). Paid so far: <?= inr($b['amount_paid']) ?>.
          <?= $back > 0.5 ? 'The guest gets back <strong>' . inr($back) . '</strong>.'
            : ($charge > (float) $b['amount_paid'] + 0.5 && (float) $b['amount_paid'] > 0 ? 'No refund: what was paid is kept, and nothing more is collected.' : 'No refund.') ?></p>
        <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
        <div class="field"><label for="cancel-reason">Reason</label><input id="cancel-reason" name="reason" maxlength="250" placeholder="Guest called"></div>
        <button class="btn btn--sm btn--block" style="margin-top:8px;background:var(--err);border-color:var(--err)"
                type="submit">Cancel booking</button>
      </form>
      <?php elseif ($b['status'] === 'confirmed' && $b['check_out'] > $today_ymd): ?>
      <div class="cancelbox" id="cancel">
        <h3 style="font-size:.95rem;margin:0 0 6px">The stay has started</h3>
        <p style="font-size:.8rem;margin:0 0 8px;color:var(--muted)">It can no longer be cancelled. If the guest never came, or left before
          <?= date('j M', strtotime($b['check_out'])) ?>, mark it here: the cottages go back on sale from today and the money stays as it is.</p>
        <form method="post" data-confirm="Mark <?= h($b['ref']) ?> as a no-show? The cottages go back on sale from today." data-ok="Yes, no-show" data-danger="1">
          <?= csrf_field() ?><input type="hidden" name="action" value="no_show">
          <div class="field"><label for="ns-reason">Note</label><input id="ns-reason" name="reason" maxlength="250" placeholder="Did not arrive, not answering"></div>
          <button class="btn btn--plain btn--sm btn--block" style="margin-top:8px" type="submit">Guest did not arrive</button>
        </form>
        <form method="post" style="margin-top:10px" data-confirm="Mark <?= h($b['ref']) ?> as left early? The cottages go back on sale from today." data-ok="Yes, left early">
          <?= csrf_field() ?><input type="hidden" name="action" value="left_early">
          <button class="btn btn--plain btn--sm btn--block" type="submit">Guest left early</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php admin_foot(); ?>
