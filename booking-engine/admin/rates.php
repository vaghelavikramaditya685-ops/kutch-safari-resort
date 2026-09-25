<?php
/* Set the nightly price for a date range, or close dates off entirely. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/inventory.php';
$user = require_login();
check_csrf();

$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $plan_ids = array_map('intval', $_POST['rate_plan_ids'] ?? []);
    $from = $_POST['from']; $to = $_POST['to'];
    $price = $_POST['price'] === '' ? null : (float) $_POST['price'];
    $closed = !empty($_POST['closed']) ? 1 : 0;
    $min_stay = max(1, (int) ($_POST['min_stay'] ?? 1));

    if (!$plan_ids)            $flash = 'Choose at least one rate plan.';
    elseif ($to < $from)       $flash = 'The end date is before the start date.';
    elseif ($price === null && !$closed) $flash = 'Give a price, or tick "close these dates".';
    else {
        $n = 0;
        foreach ($plan_ids as $pid) {
            $plan = q1("SELECT base_price FROM rate_plans WHERE id = ?", [$pid]);
            $d = new DateTime($from); $end = new DateTime($to);
            while ($d <= $end) {
                $date = $d->format('Y-m-d');
                exec_sql("DELETE FROM rates WHERE rate_plan_id = ? AND stay_date = ?", [$pid, $date]);
                insert('rates', [
                    'rate_plan_id' => $pid, 'stay_date' => $date,
                    'price' => $price ?? (float) $plan['base_price'],
                    'min_stay' => $min_stay, 'closed' => $closed,
                ]);
                $d->modify('+1 day'); $n++;
            }
        }
        audit('rates_updated', null, null,
              ['plans' => $plan_ids, 'from' => $from, 'to' => $to, 'price' => $price, 'closed' => $closed], $user['name']);
        $flash = "$n night(s) updated.";
    }
}

$plans = q("SELECT rp.*, rt.name AS room_name, p.name AS property_name, p.id AS property_id
              FROM rate_plans rp
              JOIN room_types rt ON rt.id = rp.room_type_id
              JOIN properties p ON p.id = rt.property_id
             WHERE rp.active = 1 ORDER BY p.id, rt.sort_order, rp.sort_order");

admin_head('Rates', $user);
?>
<?php if ($flash): ?><div class="notice notice--ok"><?= h($flash) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:400px 1fr;gap:20px;align-items:start">
  <form method="post" class="panel">
    <?= csrf_field() ?>
    <h2 style="font-size:1.2rem">Change prices</h2>
    <p style="font-size:.85rem">Pick the plans, the dates, and the new nightly price.
       Anything you do not set here keeps its base price.</p>

    <div class="field" style="margin-bottom:14px">
      <label>Rate plans</label>
      <div style="max-height:260px;overflow:auto;border:1px solid var(--line);border-radius:3px;padding:8px">
        <?php $last = null; foreach ($plans as $p):
          if ($last !== $p['property_name'] . $p['room_name']):
            $last = $p['property_name'] . $p['room_name']; ?>
            <div style="font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:8px 0 4px">
              <?= h($p['room_name']) ?></div>
        <?php endif; ?>
          <label style="display:block;font-size:.85rem;padding:2px 0">
            <input type="checkbox" name="rate_plan_ids[]" value="<?= (int) $p['id'] ?>">
            <?= h($p['name']) ?> <span style="color:var(--muted)">· base <?= inr($p['base_price']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field"><label>From</label><input type="date" name="from" value="<?= date('Y-m-d') ?>" required></div>
      <div class="field"><label>To</label><input type="date" name="to" value="<?= date('Y-m-d') ?>" required></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
      <div class="field"><label>Nightly price (₹)</label><input name="price" type="number" step="1" placeholder="e.g. 8450"></div>
      <div class="field"><label>Minimum stay</label><input name="min_stay" type="number" value="1" min="1"></div>
    </div>
    <label style="display:block;margin-top:12px;font-size:.86rem">
      <input type="checkbox" name="closed" value="1"> Close these dates (stop selling)
    </label>
    <button class="btn btn--block" type="submit" style="margin-top:16px">Apply</button>
  </form>

  <div>
    <h2 style="font-size:1.2rem">Prices already set</h2>
    <p style="font-size:.85rem;color:var(--muted)">Only nights that differ from the base price are listed.</p>
    <table class="grid">
      <thead><tr><th>Room &amp; plan</th><th>Date</th><th>Price</th><th>Min stay</th><th>Status</th></tr></thead>
      <tbody>
      <?php
      $rows = q("SELECT r.*, rp.name AS plan_name, rt.name AS room_name
                   FROM rates r
                   JOIN rate_plans rp ON rp.id = r.rate_plan_id
                   JOIN room_types rt ON rt.id = rp.room_type_id
                  WHERE r.stay_date >= ? ORDER BY r.stay_date, rt.sort_order LIMIT 300", [date('Y-m-d')]);
      if (!$rows): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:26px">
          Nothing set — every night is at its base price.</td></tr>
      <?php endif;
      foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['room_name']) ?><br><small style="color:var(--muted)"><?= h($r['plan_name']) ?></small></td>
          <td><?= date('D j M Y', strtotime($r['stay_date'])) ?></td>
          <td><?= inr($r['price']) ?></td>
          <td><?= (int) $r['min_stay'] ?></td>
          <td><?= (int) $r['closed'] ? '<span class="tag tag--cancelled">closed</span>' : '<span class="tag tag--confirmed">open</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_foot(); ?>
