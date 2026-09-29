<?php
/* ===========================================================================
 *  Special prices for chosen dates.
 *
 *  Every night of a stay is priced on its own (lib/inventory.php
 *  price_rate_plan), so a special price only touches the nights it covers:
 *  set Kutchi to ₹2,000 for the nights of 5–10 Oct, and a guest staying 1–10 Oct
 *  pays the normal price for 1–4 Oct and ₹2,000 for 5–9 Oct.
 *
 *  The normal (base) price is never changed here. Guard rails stop a slip of
 *  the keyboard (₹2 instead of ₹2,000): hard limits, a confirm tick for big
 *  changes, and a preview before anything is saved. Bookings already made keep
 *  the price they were booked at.
 * ======================================================================== */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/inventory.php';
$user = require_login();
check_csrf();

const PRICE_FLOOR    = 500;    // no night is ever sold below this
const HARD_LOW       = 0.25;   // refused below 25% of the normal price…
const HARD_HIGH      = 4.0;    // …or above 4× it
const CONFIRM_LOW    = 0.60;   // a tick is needed below 60%…
const CONFIRM_HIGH   = 1.5;    // …or above 1.5×
const MAX_RANGE_DAYS = 400;

$plans = q("SELECT rp.*, rt.name AS room_name, p.name AS property_name, p.id AS property_id, p.season_start, p.season_end
              FROM rate_plans rp
              JOIN room_types rt ON rt.id = rp.room_type_id
              JOIN properties p ON p.id = rt.property_id
             WHERE rp.active = 1 AND rt.active = 1 AND p.active = 1 ORDER BY p.id, rt.sort_order, rp.sort_order");
$plan_by_id = array_column($plans, null, 'id');

$today = date('Y-m-d');
$flash = ''; $error = ''; $preview = null;
$form = [
    'from'  => (string) ($_POST['from'] ?? date('Y-m-d', strtotime('+1 day'))),
    'to'    => (string) ($_POST['to'] ?? date('Y-m-d', strtotime('+1 day'))),
    'plans' => array_map('intval', (array) ($_POST['plans'] ?? [])),
    'price' => (string) ($_POST['price'] ?? ''),
    'sure'  => !empty($_POST['sure']),
];

$do = $_POST['do'] ?? '';
if ($do === 'remove') {
    // Back to the normal price for one saved range.
    $pid = (int) ($_POST['plan'] ?? 0);
    $rf = max($today, (string) ($_POST['from'] ?? ''));
    $rt = (string) ($_POST['to'] ?? '');
    $n = exec_sql("DELETE FROM rates WHERE rate_plan_id = ? AND stay_date BETWEEN ? AND ?", [$pid, $rf, $rt]);
    audit('special_price_removed', 'rate_plan', $pid, ['from' => $rf, 'to' => $rt, 'nights' => $n], $user['name']);
    $flash = $n ? "Special price removed for $n night" . ($n > 1 ? 's' : '') . ' — back to the normal price.' : 'Nothing to remove.';
    $form['plans'] = [];
} elseif ($do === 'check' || $do === 'save') {
    $price = $form['price'] === '' ? null : round((float) $form['price']);
    $nights = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['to']))
        ? (int) ((strtotime($form['to']) - strtotime($form['from'])) / 86400) + 1 : 0;

    if (!$form['plans'])                    $error = 'Tick at least one cottage.';
    elseif (!valid_date($form['from']) || !valid_date($form['to'])) $error = 'Choose the first and the last night.';
    elseif ($nights < 1)                    $error = 'The last night is before the first night.';
    elseif ($form['from'] < $today)         $error = 'The first night is in the past. Special prices can only be set from today on.';
    elseif ($nights > MAX_RANGE_DAYS)       $error = 'That range is longer than ' . MAX_RANGE_DAYS . ' nights. Set it in smaller parts.';
    elseif ($price === null || $price <= 0) $error = 'Enter the special price per night.';
    else {
        $rows = []; $needs_sure = false;
        foreach ($form['plans'] as $pid) {
            $p = $plan_by_id[$pid] ?? null;
            if (!$p) { $error = 'One of those cottages is no longer sold.'; break; }
            $base = (float) $p['base_price'];
            $ratio = $base > 0 ? $price / $base : 1;
            if ($price < PRICE_FLOOR) {
                $error = 'No night can be sold below ' . inr(PRICE_FLOOR) . '.' . ($price < 100 ? ' Did you mean ' . inr($price * 1000) . '?' : '');
                break;
            }
            if ($ratio < HARD_LOW || $ratio > HARD_HIGH) {
                $error = sprintf('%s is %s its normal price of %s — that looks like a typing mistake, so it was not accepted.',
                    inr($price), $ratio < 1 ? 'less than a quarter of' : 'more than four times', inr($base));
                break;
            }
            if ($ratio < CONFIRM_LOW || $ratio > CONFIRM_HIGH) $needs_sure = true;
            $rows[] = ['plan' => $p, 'base' => $base, 'ratio' => $ratio];
        }
        if (!$error) {
            $preview = ['rows' => $rows, 'price' => $price, 'nights' => $nights, 'needs_sure' => $needs_sure];
            if ($do === 'save') {
                if ($needs_sure && !$form['sure']) {
                    $error = 'This is a big change from the normal price. Tick "Yes, this price is right" to save it.';
                } else {
                    db_begin();
                    foreach ($form['plans'] as $pid) {
                        for ($d = $form['from']; $d <= $form['to']; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                            // Keep any stop-sell or minimum stay already set on that night.
                            $old = q1("SELECT min_stay, closed FROM rates WHERE rate_plan_id = ? AND stay_date = ?", [$pid, $d]);
                            exec_sql("DELETE FROM rates WHERE rate_plan_id = ? AND stay_date = ?", [$pid, $d]);
                            insert('rates', ['rate_plan_id' => $pid, 'stay_date' => $d, 'price' => $price,
                                             'min_stay' => (int) ($old['min_stay'] ?? 1), 'closed' => (int) ($old['closed'] ?? 0)]);
                        }
                    }
                    db_commit();
                    audit('special_price_set', null, null, ['plans' => $form['plans'], 'from' => $form['from'],
                          'to' => $form['to'], 'price' => $price], $user['name']);
                    $flash = sprintf('Special price of %s a night saved for %d night%s (%s – %s).', inr($price), $nights,
                        $nights > 1 ? 's' : '', date('j M', strtotime($form['from'])), date('j M Y', strtotime($form['to'])));
                    $preview = null; $form['plans'] = []; $form['price'] = ''; $form['sure'] = false;
                }
            }
        }
    }
}

/* Saved special prices, joined into date ranges: same cottage, same price, nights in a row. */
$saved = [];
foreach (q("SELECT r.rate_plan_id, r.stay_date, r.price, r.closed FROM rates r
             JOIN rate_plans rp ON rp.id = r.rate_plan_id
            WHERE r.stay_date >= ? ORDER BY r.rate_plan_id, r.stay_date", [$today]) as $r) {
    $last = end($saved);
    $next_day = $last ? date('Y-m-d', strtotime($last['to'] . ' +1 day')) : null;
    if ($last && $last['plan'] === (int) $r['rate_plan_id'] && $next_day === $r['stay_date']
        && (float) $last['price'] === (float) $r['price'] && $last['closed'] === (int) $r['closed']) {
        $saved[array_key_last($saved)]['to'] = $r['stay_date'];
        $saved[array_key_last($saved)]['nights']++;
    } else {
        $saved[] = ['plan' => (int) $r['rate_plan_id'], 'from' => $r['stay_date'], 'to' => $r['stay_date'],
                    'price' => (float) $r['price'], 'closed' => (int) $r['closed'], 'nights' => 1];
    }
}
usort($saved, fn($a, $b) => strcmp($a['from'], $b['from']) ?: $a['plan'] <=> $b['plan']);

admin_head('Special prices', $user);
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$night_word = fn($n) => $n . ' night' . ($n > 1 ? 's' : '');
?>
<?php if ($flash): ?><div class="notice notice--ok"><?= $e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice notice--err"><?= $e($error) ?></div><?php endif; ?>

<div class="sp">
  <form method="post" class="panel sp__form">
    <?= csrf_field() ?>
    <h2 style="font-size:1.2rem;margin-bottom:.2em">Set a special price</h2>
    <p style="font-size:.84rem;color:var(--muted);margin-top:0">For chosen dates only — the normal price is never changed.
       A guest whose stay covers these nights pays the special price for those nights and the normal price for the rest.</p>

    <h3 class="sp__step"><span>1</span> Which nights</h3>
    <div class="sp__dates">
      <div class="field"><label for="sp-from">First night</label>
        <input id="sp-from" type="date" name="from" min="<?= $today ?>" value="<?= $e($form['from']) ?>" required></div>
      <div class="field"><label for="sp-to">Last night</label>
        <input id="sp-to" type="date" name="to" min="<?= $today ?>" value="<?= $e($form['to']) ?>" required></div>
    </div>
    <p class="sp__hint" id="sp-hint"></p>

    <h3 class="sp__step"><span>2</span> Which cottages</h3>
    <div class="sp__plans">
      <?php $last = null; foreach ($plans as $p): if ($last !== $p['property_name']): $last = $p['property_name']; ?>
        <div class="sp__prop"><?= $e($p['property_name']) ?></div>
      <?php endif; ?>
        <label class="sp__plan <?= in_array((int) $p['id'], $form['plans'], true) ? 'is-on' : '' ?>">
          <input type="checkbox" name="plans[]" value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $form['plans'], true) ? 'checked' : '' ?>>
          <span><b><?= $e($p['room_name']) ?></b><small><?= $e($p['name']) ?> · normal <?= inr($p['base_price']) ?> a night</small></span>
        </label>
      <?php endforeach; ?>
    </div>

    <h3 class="sp__step"><span>3</span> Special price per night</h3>
    <div class="field">
      <label for="sp-price">Price for 2 guests (₹)</label>
      <input id="sp-price" name="price" type="number" step="1" min="<?= PRICE_FLOOR ?>" value="<?= $e($form['price']) ?>" placeholder="e.g. 6000" required>
      <span class="hint">Single and triple follow automatically: single keeps its discount, triple adds the extra bed.</span>
    </div>

    <?php if ($preview): ?>
    <div class="sp__preview">
      <h3 style="font-size:.95rem;margin:0 0 8px">Check before saving</h3>
      <p style="font-size:.84rem;margin:0 0 8px"><?= $night_word($preview['nights']) ?>:
        <?= date('D j M', strtotime($form['from'])) ?> to <?= date('D j M Y', strtotime($form['to'])) ?>
        <span style="color:var(--muted)">(check-out <?= date('j M', strtotime($form['to'] . ' +1 day')) ?>)</span></p>
      <?php foreach ($preview['rows'] as $row): $pct = round(($row['ratio'] - 1) * 100); ?>
        <div class="sline"><span><?= $e($row['plan']['room_name']) ?><small><?= $e($row['plan']['name']) ?></small></span>
          <span><s style="color:var(--muted)"><?= inr($row['base']) ?></s> → <b><?= inr($preview['price']) ?></b>
            <small class="<?= $pct < 0 ? 'sp__down' : 'sp__up' ?>"><?= $pct > 0 ? '+' : '' ?><?= $pct ?>%</small></span></div>
      <?php endforeach; ?>
      <?php if ($preview['needs_sure']): ?>
        <label class="sp__sure"><input type="checkbox" name="sure" value="1" <?= $form['sure'] ? 'checked' : '' ?>>
          Yes, this price is right — it is a big change from the normal price.</label>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sp__buttons">
      <button class="btn btn--plain" type="submit" name="do" value="check">Check</button>
      <?php if ($preview): ?><button class="btn" type="submit" name="do" value="save">Save special price</button><?php endif; ?>
    </div>
  </form>

  <div>
    <h2 style="font-size:1.2rem;margin-bottom:.2em">Special prices already set</h2>
    <p style="font-size:.84rem;color:var(--muted);margin-top:0">From today on. Every other night is at the normal price.
       Remove one to go back to the normal price; bookings already made keep the price they were booked at.</p>
    <table class="grid">
      <thead><tr><th>Cottage</th><th>Nights</th><th>Price a night</th><th></th></tr></thead>
      <tbody>
      <?php if (!$saved): ?>
        <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:26px">None — every night is at the normal price.</td></tr>
      <?php endif; ?>
      <?php foreach ($saved as $s): $p = $plan_by_id[$s['plan']] ?? null; if (!$p) continue; ?>
        <tr>
          <td><?= $e($p['room_name']) ?><br><small style="color:var(--muted)"><?= $e($p['property_name']) ?></small></td>
          <td><?= date('D j M', strtotime($s['from'])) ?><?= $s['nights'] > 1 ? ' – ' . date('D j M Y', strtotime($s['to'])) : date(' Y', strtotime($s['from'])) ?>
            <br><small style="color:var(--muted)"><?= $night_word($s['nights']) ?></small></td>
          <td><?php if ($s['closed']): ?><span class="tag tag--cancelled">not on sale</span>
              <?php else: ?><b><?= inr($s['price']) ?></b><br><small style="color:var(--muted)">normal <?= inr($p['base_price']) ?></small><?php endif; ?></td>
          <td>
            <form method="post" data-confirm="Go back to the normal price for these nights?" data-ok="Yes, remove it">
              <?= csrf_field() ?>
              <input type="hidden" name="do" value="remove">
              <input type="hidden" name="plan" value="<?= (int) $s['plan'] ?>">
              <input type="hidden" name="from" value="<?= $e($s['from']) ?>">
              <input type="hidden" name="to" value="<?= $e($s['to']) ?>">
              <button class="btn btn--plain btn--sm" type="submit">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// "6 nights · check-out Sun 11 Oct" under the dates, and ticked cottages highlighted.
(function () {
  var f = document.getElementById('sp-from'), t = document.getElementById('sp-to'), h = document.getElementById('sp-hint');
  function show() {
    if (!f.value || !t.value) { h.textContent = ''; return; }
    var a = new Date(f.value + 'T00:00:00'), b = new Date(t.value + 'T00:00:00');
    var n = Math.round((b - a) / 86400000) + 1;
    if (n < 1) { h.textContent = 'The last night is before the first night.'; return; }
    var out = new Date(b); out.setDate(out.getDate() + 1);
    h.textContent = n + ' night' + (n > 1 ? 's' : '') + ' · a guest staying all of them checks out on '
      + out.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' });
  }
  f.addEventListener('change', function () { if (t.value < f.value) t.value = f.value; t.min = f.value; show(); });
  t.addEventListener('change', show);
  show();
  document.querySelectorAll('.sp__plan input').forEach(function (cb) {
    cb.addEventListener('change', function () { cb.closest('.sp__plan').classList.toggle('is-on', cb.checked); });
  });
})();
</script>
<?php admin_foot(); ?>
