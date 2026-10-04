<?php
/* The offline pipeline — everything that came in through Enquire Now. */
require_once __DIR__ . '/_auth.php';
$user = require_login();
check_csrf();

$flash = ''; $flash_bad = false;
$statuses = ['new', 'contacted', 'converted', 'closed'];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $new_status = (string) ($_POST['status'] ?? '');
    if (!q1("SELECT id FROM enquiries WHERE id = ?", [$id])) { $flash = 'That enquiry was not found.'; $flash_bad = true; }
    elseif (!in_array($new_status, $statuses, true)) { $flash = 'Choose a status from the list.'; $flash_bad = true; }
    elseif (too_long($_POST['staff_note'] ?? '', 'staff_note')) { $flash = 'Please keep the note under ' . number_format(LIMITS['staff_note']) . ' characters.'; $flash_bad = true; }
    else {
        update('enquiries', $id, ['status' => $new_status, 'staff_note' => trim((string) ($_POST['staff_note'] ?? '')) ?: null]);
        audit('enquiry_updated', 'enquiry', $id, ['status' => $new_status], $user['name']);
        $flash = 'Enquiry updated.';
    }
}

$status = $_GET['status'] ?? '';
$rows = $status
    ? q("SELECT * FROM enquiries WHERE status = ? ORDER BY created_at DESC LIMIT 200", [$status])
    : q("SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 200");

admin_head('Enquiries', $user);
?>
<?php if ($flash): ?><div class="notice <?= $flash_bad ? 'notice--err' : 'notice--ok' ?>" role="<?= $flash_bad ? 'alert' : 'status' ?>"><?= h($flash) ?></div><?php endif; ?>
<form class="filters" method="get">
  <div class="field"><label for="f-status">Status</label>
    <select id="f-status" name="status" onchange="this.form.submit()">
      <option value="">All</option>
      <?php foreach (['new','contacted','converted','closed'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select></div>
</form>

<table class="grid">
  <thead><tr><th>Received</th><th>Guest</th><th>Dates</th><th>Interest</th><th>Message</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No enquiries yet.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $e): ?>
    <tr>
      <td><?= date('j M, g:i a', strtotime($e['created_at'])) ?></td>
      <td><strong><?= h($e['name']) ?></strong><br>
          <a href="tel:<?= h($e['phone']) ?>"><?= h($e['phone']) ?></a>
          <?php if ($e['email']): ?><br><a href="mailto:<?= h($e['email']) ?>"><small><?= h($e['email']) ?></small></a><?php endif; ?>
          <br><a class="btn btn--plain btn--sm" style="margin-top:6px" target="_blank" rel="noopener"
                 href="https://wa.me/<?= h(preg_replace('/\D/', '', $e['phone'])) ?>">WhatsApp</a></td>
      <td><?= $e['check_in'] ? h($e['check_in']) . '<br>to ' . h($e['check_out']) : '<span style="color:var(--muted)">—</span>' ?>
          <?php if ($e['guests']): ?><br><small><?= h($e['guests']) ?></small><?php endif; ?></td>
      <td><small><?= h($e['interest']) ?></small></td>
      <td style="max-width:280px"><small><?= nl2br(h($e['message'])) ?></small></td>
      <td>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
          <select name="status" aria-label="Status of the enquiry from <?= h($e['name']) ?>" style="padding:5px;border:1px solid var(--line);border-radius:3px;width:100%">
            <?php foreach (['new','contacted','converted','closed'] as $s): ?>
              <option value="<?= $s ?>" <?= $e['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <input name="staff_note" value="<?= h($e['staff_note']) ?>" placeholder="Note" aria-label="Note on the enquiry from <?= h($e['name']) ?>" maxlength="2000"
                 style="margin-top:5px;padding:5px;border:1px solid var(--line);border-radius:3px;width:100%">
          <button class="btn btn--plain btn--sm btn--block" style="margin-top:5px" type="submit">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php admin_foot(); ?>
