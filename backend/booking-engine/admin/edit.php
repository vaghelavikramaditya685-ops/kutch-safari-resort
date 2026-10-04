<?php
/* ===========================================================================
 *  Change a booking — the desk, when a guest calls or at the property.
 *  Dates, each room's cottage and occupancy, rooms added or removed, extras.
 *  "Check price" shows the new total and what to collect or give back;
 *  "Save changes" appears only after that and writes it.
 *  Priced as a difference (lib/booking.php quote_modification): whatever the
 *  guest keeps stays at its booked amount; only what is added, taken off or
 *  changed moves the total. What to collect follows how the guest pays
 *  (in full, or 50% now and the rest before arrival).
 * ======================================================================== */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
$user = require_login();
check_csrf();

$id = (int) ($_GET['id'] ?? 0);
$b  = get_booking($id);
if (!$b) { http_response_code(404); admin_head('Not found', $user); echo '<div class="notice notice--err">No such booking.</div>'; admin_foot(); exit; }
// What the price was checked against: if the booking changes before Save (a payment,
// another change, a cancellation), the save is refused and the price checked again.
$version = substr(hash('sha256', implode('|', [$b['status'], $b['total'], $b['amount_paid'], $b['check_in'], $b['check_out'], $b['updated_at']])), 0, 16);

$room_types = q("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order, id", [$b['property_id']]);
$plans = q("SELECT rp.*, rt.name AS room_name, rt.max_adults FROM rate_plans rp JOIN room_types rt ON rt.id = rp.room_type_id
             WHERE rt.property_id = ? AND rt.active = 1 AND rp.active = 1 ORDER BY rt.sort_order, rt.id, rp.sort_order", [$b['property_id']]);
$addons = list_addons((int) $b['property_id']);
$labels = [1 => 'Single', 2 => 'Double', 3 => 'Triple'];

/* The booking as it is now, one row per room, in room order. */
function booking_units(array $b): array {
    $units = [];
    foreach ($b['rooms'] as $line) {
        preg_match_all('/Room \d+ (Single|Double|Triple)/', (string) $line['rate_plan_name'], $m);
        $per = max(1, (int) round((int) $line['adults'] / max(1, (int) $line['rooms'])));
        for ($i = 0; $i < (int) $line['rooms']; $i++) {
            $g = ['Single' => 1, 'Double' => 2, 'Triple' => 3][$m[1][$i] ?? ''] ?? $per;
            $units[] = ['cottage' => $line['room_type_id'] . ':' . $line['rate_plan_id'], 'guests' => $g];
        }
    }
    return $units;
}

/* Form values: what was posted, or the booking as it stands. */
$posted = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$form = [
    'check_in'  => $posted ? (string) ($_POST['check_in'] ?? '') : $b['check_in'],
    'check_out' => $posted ? (string) ($_POST['check_out'] ?? '') : $b['check_out'],
    'rooms'     => $posted ? array_values((array) ($_POST['room'] ?? [])) : booking_units($b),
    'addons'    => [],
];
if ($posted) {
    foreach ((array) ($_POST['addon'] ?? []) as $aid => $qty) $form['addons'][(int) $aid] = max(0, (int) $qty);
} else {
    foreach ($b['addons'] as $a) $form['addons'][(int) $a['addon_id']] = ($form['addons'][(int) $a['addon_id']] ?? 0) + (int) $a['quantity'];
}
$rows = $form['rooms'];
while (count($rows) < count(booking_units($b)) + 2) $rows[] = ['cottage' => '', 'guests' => 2];   // room to add rooms

/* What goes to the pricing. */
$changes = ['check_in' => $form['check_in'], 'check_out' => $form['check_out'], 'rooms' => [], 'occupancy' => [], 'addons' => []];
foreach ($form['rooms'] as $r) {
    if (empty($r['cottage']) || !str_contains((string) $r['cottage'], ':')) continue;
    [$rt, $pl] = array_map('intval', explode(':', (string) $r['cottage']));
    $changes['rooms'][] = ['room_type_id' => $rt, 'rate_plan_id' => $pl, 'rooms' => 1];
    $changes['occupancy'][] = max(1, min(3, (int) ($r['guests'] ?? 2)));
}
foreach ($form['addons'] as $aid => $qty) if ($qty > 0) $changes['addons'][] = ['addon_id' => $aid, 'quantity' => $qty];

$quote = null; $error = '';
if ($posted) {
    if (($_POST['do'] ?? '') === 'save' && !hash_equals($version, (string) ($_POST['version'] ?? ''))) {
        $error = 'This booking has changed since you checked the price (a payment, another change or a cancellation). Check the new price below before saving.';
        $quote = quote_modification($id, $changes);
        if (!$quote['ok']) { $error = $quote['error']; $quote = null; }
    } elseif (($_POST['do'] ?? '') === 'save') {
        $r = modify_booking($id, $changes, $user['name']);
        if ($r['ok']) { header('Location: booking.php?id=' . $id . '&changed=1'); exit; }
        $error = $r['error'];
    } else {
        $quote = quote_modification($id, $changes);
        if (!$quote['ok']) { $error = $quote['error']; $quote = null; }
    }
}

admin_head('Change ' . $b['ref'], $user);
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
?>
<p><a href="booking.php?id=<?= $id ?>">&larr; Back to <?= $e($b['ref']) ?></a></p>
<h2 style="margin-bottom:.2em">Change booking <?= $e($b['ref']) ?></h2>
<p style="color:var(--muted);font-size:.86rem;margin-top:0">
  <?= $e($b['guest_name'] ?: 'No name given') ?> · now <?= date('j M', strtotime($b['check_in'])) ?> – <?= date('j M Y', strtotime($b['check_out'])) ?>
  · total <?= inr($b['total']) ?> · paid <?= inr($b['amount_paid']) ?>.
  Prices are worked out at today's rates, the same way the website prices a new booking.</p>

<?php if ($b['status'] === 'cancelled' || $b['status'] === 'no_show' || $b['status'] === 'completed'): ?>
  <div class="notice notice--err"><?= $b['status'] === 'cancelled' ? 'This booking is cancelled and cannot be changed.' : 'This stay has ended and cannot be changed.' ?></div>
<?php admin_foot(); exit; endif; ?>

<?php if ($error): ?><div class="notice notice--err"><?= $e($error) ?></div><?php endif; ?>

<form method="post" class="editform">
  <?= csrf_field() ?>
  <div class="panel">
    <h3 style="font-size:1rem">Dates</h3>
    <div class="editform__dates">
      <div class="field"><label for="e-in">Check in</label><input type="date" id="e-in" name="check_in" value="<?= $e($form['check_in']) ?>" required></div>
      <div class="field"><label for="e-out">Check out</label><input type="date" id="e-out" name="check_out" value="<?= $e($form['check_out']) ?>" required></div>
    </div>
  </div>

  <div class="panel">
    <h3 style="font-size:1rem">Rooms</h3>
    <p style="font-size:.8rem;color:var(--muted);margin:0 0 10px">Choose "— no room —" to remove a room; use an empty row to add one.</p>
    <table class="editform__rooms">
      <tr><th></th><th>Cottage</th><th>Guests</th></tr>
      <?php foreach ($rows as $i => $r): ?>
      <tr>
        <td><b>Room <?= $i + 1 ?></b></td>
        <td><select name="room[<?= $i ?>][cottage]" aria-label="Room <?= $i + 1 ?> cottage">
          <option value="">— no room —</option>
          <?php foreach ($plans as $p): $v = $p['room_type_id'] . ':' . $p['id']; ?>
            <option value="<?= $v ?>" <?= ($r['cottage'] ?? '') === $v ? 'selected' : '' ?>><?= $e($p['room_name'] . ' — ' . $p['name']) ?></option>
          <?php endforeach; ?>
          <?php if (!empty($r['cottage']) && !in_array($r['cottage'], array_map(fn($p) => $p['room_type_id'] . ':' . $p['id'], $plans), true)): ?>
            <option value="<?= $e($r['cottage']) ?>" selected>Current cottage (no longer on sale — choose another)</option>
          <?php endif; ?>
        </select></td>
        <td><select name="room[<?= $i ?>][guests]" aria-label="Room <?= $i + 1 ?> guests">
          <?php foreach ($labels as $g => $l): ?>
            <option value="<?= $g ?>" <?= (int) ($r['guests'] ?? 2) === $g ? 'selected' : '' ?>><?= $l ?> · <?= $g ?> guest<?= $g > 1 ? 's' : '' ?></option>
          <?php endforeach; ?>
        </select></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="panel" id="extras">
    <h3 style="font-size:1rem">Extras</h3>
    <table class="editform__rooms">
      <tr><th>Extra</th><th>Price</th><th>How many</th></tr>
      <?php foreach ($addons as $a):
        $unit = ['per_person' => 'per person', 'per_night' => 'per night', 'per_room_night' => 'per room per night'][$a['price_type']] ?? 'each'; ?>
      <tr>
        <td><?= $e($a['name']) ?><?= (int) $a['min_quantity'] > 1 ? ' <small style="color:var(--muted)">(min ' . (int) $a['min_quantity'] . ')</small>' : '' ?></td>
        <td><?= inr($a['price']) ?> <small style="color:var(--muted)"><?= $unit ?></small></td>
        <td><input type="number" min="0" step="1" name="addon[<?= (int) $a['id'] ?>]" aria-label="How many: <?= $e($a['name']) ?>" value="<?= (int) ($form['addons'][(int) $a['id']] ?? 0) ?>" style="width:90px"></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="editform__actions">
    <button class="btn btn--plain" type="submit" name="do" value="preview">Check price</button>
    <a class="btn btn--plain" href="booking.php?id=<?= $id ?>">Cancel changes</a>
  </div>

  <?php if ($quote): $ch = $quote['changes']; $m = $quote['money'];
    $sign = fn($v) => $v >= 0 ? '+ ' . inr($v) : '− ' . inr(-$v); ?>
  <div class="panel editform__preview">
    <h3 style="font-size:1rem">What changes — <?= date('D j M', strtotime($quote['check_in'])) ?> to <?= date('D j M Y', strtotime($quote['check_out'])) ?> (<?= (int) $quote['nights'] ?> night<?= $quote['nights'] > 1 ? 's' : '' ?>)</h3>
    <p class="muted" style="margin:0 0 8px;font-size:.84rem">Everything the guest keeps stays at the price it was booked at. Only what is added or taken off moves the total.</p>
    <table class="grid chg">
      <?php foreach ([['Added', $ch['added'], 'add'], ['Taken off', $ch['removed'], 'rem'], ['Changed', $ch['changed'], 'chg']] as [$title, $lines, $cls]):
        if (!$lines) continue; ?>
        <tr class="chg__head"><td colspan="2"><?= $title ?></td></tr>
        <?php foreach ($lines as $l): ?>
          <tr class="chg--<?= $cls ?>"><td><?= $e($l['what']) ?></td><td class="num"><?= $sign($l['amount']) ?></td></tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if (!$ch['added'] && !$ch['removed'] && !$ch['changed']): ?>
        <tr><td colspan="2" class="muted">Nothing that changes the price.</td></tr>
      <?php endif; ?>
      <tr class="chg__sum"><td>Old total</td><td class="num"><?= inr($quote['old_total']) ?></td></tr>
      <?php if ($ch['added']): ?><tr><td>Added</td><td class="num"><?= $sign($ch['added_total']) ?></td></tr><?php endif; ?>
      <?php if ($ch['removed']): ?><tr><td>Taken off</td><td class="num"><?= $sign($ch['removed_total']) ?></td></tr><?php endif; ?>
      <?php if ($ch['changed']): ?><tr><td>Changed</td><td class="num"><?= $sign($ch['changed_total']) ?></td></tr><?php endif; ?>
      <tr class="chg__total"><td><strong>New total</strong></td><td class="num"><strong><?= inr($quote['total']) ?></strong></td></tr>
    </table>

    <h3 style="font-size:.95rem;margin:16px 0 6px">Money</h3>
    <table class="grid chg">
      <tr><td>How the guest pays</td><td class="num"><?= $m['mode'] === 'advance' ? (int) $m['percent'] . '% advance, rest before arrival' : 'In full' ?></td></tr>
      <tr><td>Paid so far</td><td class="num"><?= inr($m['paid']) ?></td></tr>
      <?php if ($m['mode'] === 'advance'): ?>
        <tr><td><?= (int) $m['percent'] ?>% of the new total</td><td class="num"><?= inr($m['needed_now']) ?></td></tr>
      <?php endif; ?>
      <?php if ($m['refund'] > 0.5): ?>
        <tr class="chg--add"><td><strong>Give back to the guest</strong></td><td class="num"><strong><?= inr($m['refund']) ?></strong></td></tr>
      <?php else: ?>
        <tr class="chg__total"><td><strong><?= $m['arrived'] ? 'Collect at the desk' : 'Collect now' ?></strong></td>
          <td class="num"><strong><?= inr($m['due_now']) ?></strong></td></tr>
        <?php if (($m['mode'] === 'advance' && !$m['arrived']) || $m['later'] > 0.5): ?>
          <tr><td>Rest, before arrival</td><td class="num"><?= inr($m['later']) ?></td></tr>
        <?php endif; ?>
      <?php endif; ?>
    </table>
    <p class="muted" style="font-size:.82rem;margin:8px 0 12px">Record the money on the booking page after saving.</p>
    <input type="hidden" name="version" value="<?= $e($version) ?>">
    <button class="btn" type="submit" name="do" value="save"
            data-confirm="Save these changes to <?= $e($b['ref']) ?>? The guest will see the new details and the new total of <?= inr($quote['total']) ?>." data-ok="Save changes">Save changes</button>
  </div>
  <?php endif; ?>
</form>
<?php admin_foot(); ?>
