<?php
/* The availability grid — how many rooms are on sale each night, and how many
   are left. Type a new number into any cell to close rooms off or open them. */
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../lib/inventory.php';
require_once __DIR__ . '/../lib/channel.php';
$user = require_login();
check_csrf();

$flash = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $n = 0;
    foreach (($_POST['open'] ?? []) as $key => $value) {
        if ($value === '') continue;
        [$rt, $date] = explode('|', $key);
        exec_sql("DELETE FROM inventory WHERE room_type_id = ? AND stay_date = ?", [(int) $rt, $date]);
        insert('inventory', ['room_type_id' => (int) $rt, 'stay_date' => $date,
                             'rooms_open' => max(0, (int) $value), 'note' => 'set by ' . $user['name']]);
        $n++;
    }
    audit('inventory_edited', null, null, ['cells' => $n], $user['name']);
    $flash = "$n night(s) updated.";
}
if (($_POST['action'] ?? '') === 'pull') {
    $p = q1("SELECT * FROM properties WHERE id = ?", [(int) $_POST['property_id']]);
    $r = channel_pull_inventory($p, $_POST['from'], $_POST['to']);
    $flash = $r['ok'] ? "Pulled {$r['rows']} nights from Stayflexi." : 'Stayflexi: ' . ($r['reason'] ?? 'unavailable');
}

$property_id = (int) ($_GET['property'] ?? 1);
$start = $_GET['start'] ?? date('Y-m-d');
$days  = min(45, max(7, (int) ($_GET['days'] ?? 21)));

$property = q1("SELECT * FROM properties WHERE id = ?", [$property_id]);
$room_types = q("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order", [$property_id]);

$dates = [];
$d = new DateTime($start);
for ($i = 0; $i < $days; $i++) { $dates[] = $d->format('Y-m-d'); $d->modify('+1 day'); }

admin_head('Availability', $user);
?>
<?php if ($flash): ?><div class="notice notice--ok"><?= h($flash) ?></div><?php endif; ?>

<form class="filters" method="get">
  <div class="field"><label>Property</label>
    <select name="property" onchange="this.form.submit()">
      <?php foreach (q("SELECT id, name FROM properties ORDER BY id") as $p): ?>
        <option value="<?= $p['id'] ?>" <?= $property_id === (int) $p['id'] ? 'selected' : '' ?>><?= h($p['name']) ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="field"><label>From</label><input type="date" name="start" value="<?= h($start) ?>"></div>
  <div class="field"><label>Days</label>
    <select name="days"><?php foreach ([14,21,30,45] as $n): ?>
      <option value="<?= $n ?>" <?= $days === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--sm" type="submit">Show</button>
</form>

<p style="font-size:.85rem;color:var(--muted)">
  The top number is how many rooms are <strong>on sale</strong> that night — edit it to close rooms off.
  Below it is how many are <strong>still free</strong> after bookings.
  <?= channel_enabled() ? 'Stayflexi is connected, so these are refreshed from it.'
      : 'Stayflexi is not connected yet, so these are this website’s own numbers.' ?>
</p>

<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="save">
  <div class="cal">
    <table>
      <thead>
        <tr><th class="room">Room type</th>
        <?php foreach ($dates as $dt):
            $wd = date('D', strtotime($dt)); $weekend = in_array($wd, ['Sat','Sun']); ?>
          <th class="<?= $weekend ? 'weekend' : '' ?>"><?= date('j M', strtotime($dt)) ?><br><small><?= $wd ?></small></th>
        <?php endforeach; ?></tr>
      </thead>
      <tbody>
      <?php foreach ($room_types as $rt): ?>
        <tr>
          <td class="room"><?= h($rt['name']) ?><br><small style="color:var(--muted)"><?= (int) $rt['total_rooms'] ?> owned</small></td>
          <?php foreach ($dates as $dt):
              $open = rooms_open($rt, $dt);
              $free = rooms_free($rt, $dt);
              $cls = $free === 0 ? 'zero' : ($free <= 2 ? 'low' : ''); ?>
            <td class="<?= $cls ?>">
              <input name="open[<?= (int) $rt['id'] ?>|<?= $dt ?>]" value="<?= $open ?>" inputmode="numeric">
              <div style="font-size:.68rem;color:var(--muted)"><?= $free ?> free</div>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button class="btn" type="submit" style="margin-top:16px">Save changes</button>
</form>

<form method="post" style="margin-top:20px">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="pull">
  <input type="hidden" name="property_id" value="<?= $property_id ?>">
  <input type="hidden" name="from" value="<?= h($dates[0]) ?>">
  <input type="hidden" name="to" value="<?= h(end($dates)) ?>">
  <button class="btn btn--plain btn--sm" type="submit" <?= channel_enabled() ? '' : 'disabled' ?>>
    Pull these dates from Stayflexi
  </button>
  <?php if (!channel_enabled()): ?>
    <span style="font-size:.8rem;color:var(--muted);margin-left:8px">
      Add your Stayflexi API key in config.php to enable this.</span>
  <?php endif; ?>
</form>
<?php admin_foot(); ?>
