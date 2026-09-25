<?php
/* Admin session handling. Shared by every page in this folder. */
require_once __DIR__ . '/../lib/db.php';

session_name(cfg('admin.session_name', 'kutch_admin'));
session_set_cookie_params([
    'lifetime' => 3600 * (int) cfg('admin.session_hours', 8),
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

function current_user(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    return q1("SELECT * FROM admin_users WHERE id = ? AND active = 1", [$_SESSION['admin_id']]);
}

function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}

/** Forms are protected with a token so another site cannot post to them. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}
function check_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(403);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES); }
function inr($n): string { return '₹' . number_format((float) $n, 2); }

function admin_head(string $title, ?array $user = null): void {
    $nav = [
        'index.php'    => 'Bookings',
        'calendar.php' => 'Availability',
        'rates.php'    => 'Rates',
        'enquiries.php'=> 'Enquiries',
    ];
    $here = basename($_SERVER['PHP_SELF']);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>' . h($title) . ' · Reservations</title><meta name="robots" content="noindex">
      <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../assets/engine.css">
      <link rel="stylesheet" href="admin.css"></head><body class="admin">';
    if ($user) {
        echo '<header class="eng-header"><div class="wrap">
                <div class="eng-brand"><strong>Reservations</strong></div><nav>';
        foreach ($nav as $file => $label) {
            $on = $here === $file ? ' class="on"' : '';
            echo '<a href="' . $file . '"' . $on . '>' . $label . '</a>';
        }
        echo '<a href="logout.php">Sign out (' . h($user['name']) . ')</a>';
        echo '</nav></div></header>';
    }
    echo '<div class="wrap" style="padding-block:26px">';
}

function admin_foot(): void { echo '</div></body></html>'; }
