<?php
/* The open admin tab checks in every 25 seconds, so the panel stays its own
   (one admin at a time, see _auth.php), and lets go when the tab is closed
   (release=1, sent as the page goes away). A check-in is not "using" the
   panel: it never resets the idle sign-out (admin.idle_minutes). */
require_once __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$out = function (array $r, int $status = 200) { http_response_code($status); echo json_encode($r); exit; };
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $out(['ok' => false, 'error' => 'POST required.'], 405);

if (empty($_SESSION['admin_id'])) $out(['ok' => false, 'error' => 'You have been signed out of the admin panel.'], 401);
$idle = 60 * max(1, (int) cfg('admin.idle_minutes', 10));
if (isset($_SESSION['last_seen']) && time() - (int) $_SESSION['last_seen'] > $idle) {
    admin_lock_release();
    $_SESSION = [];
    session_destroy();
    $mins = max(1, (int) cfg('admin.idle_minutes', 10));
    $out(['ok' => false, 'error' => "You were signed out after $mins minute" . ($mins === 1 ? '' : 's') . ' without using the admin panel.'], 401);
}
$user = q1("SELECT * FROM admin_users WHERE id = ? AND active = 1", [$_SESSION['admin_id']]);
if (!$user) $out(['ok' => false, 'error' => 'This admin login is no longer active.'], 401);

if (!empty($_POST['release'])) {
    admin_lock_release(true);   // the tab may only be moving to another admin page
    $out(['ok' => true]);
}
if ($other = admin_lock_take($user, true)) $out(['ok' => false, 'error' => admin_in_use_message($other)], 423);
$out(['ok' => true]);
