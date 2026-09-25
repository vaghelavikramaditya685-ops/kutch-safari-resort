<?php
require_once __DIR__ . '/_auth.php';
$u = current_user();
if ($u) audit('logout', 'admin', $u['id'], null, $u['email']);
$_SESSION = [];
session_destroy();
header('Location: login.php');
