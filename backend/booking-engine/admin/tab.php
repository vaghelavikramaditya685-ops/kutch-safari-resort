<?php
/* A new tab or window opened on the admin panel. A sign-in only counts in the tab
   it was made in, and only one admin tab may be open: if this sign-in's tab is
   still open, say so (and leave that tab alone — it used to be signed out, losing
   whatever was being changed there); otherwise ask for the password as before. */
require_once __DIR__ . '/_auth.php';

$row = !empty($_SESSION['admin_id']) ? admin_lock_row() : null;
$beat = $row ? (strtotime((string) ($row['last_beat'] ?? '')) ?: 0) : 0;
$other_tab_open = $row && $row['session_id'] === session_id() && $beat > time() - 75;
if (!$other_tab_open) { header('Location: logout.php?again=1'); exit; }

admin_head('Already open');
?>
<div class="panel" style="max-width:560px;margin:40px auto">
  <h2>The admin panel is already open</h2>
  <p role="alert">It is open in another tab or window of this browser. Only one admin tab can be used at a time,
     so carry on in that one — anything you were changing there is still there.</p>
  <p style="font-size:.86rem;color:var(--muted)">If that tab is gone, wait a minute and reload this one, or sign in here
     instead (the other tab will then be signed out).</p>
  <p><a class="btn btn--plain btn--sm" href="logout.php?again=1">Sign in here instead</a></p>
</div>
<?php admin_foot(); ?>
