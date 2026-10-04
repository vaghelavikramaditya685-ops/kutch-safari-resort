<?php
require_once __DIR__ . '/_auth.php';
$u = current_user();
if ($u) audit('logout', 'admin', $u['id'], null, $u['email']);
admin_lock_release();   // the next person can sign in straight away
// current_user() has already ended a session that was idle too long; ending it twice is a PHP warning.
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}
header('Location: login.php' . (!empty($_GET['again']) ? '?again=1' : ''));
