<?php
/* Admin session handling. Shared by every page in this folder. */
require_once __DIR__ . '/../lib/db.php';

// Typed as ".../book/admin" (no slash): the folder's links would point one level
// too high, so go to ".../book/admin/" first — the address fills itself in.
if (preg_match('~/admin$~', (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH))) {
    header('Location: admin/', true, 301);
    exit;
}

session_name(cfg('admin.session_name', 'kutch_admin'));
session_set_cookie_params([
    'lifetime' => 0,          // ends when the browser closes
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

function current_user(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    // Signed out after admin.idle_minutes without using the admin panel.
    $idle = 60 * max(1, (int) cfg('admin.idle_minutes', 10));
    if (isset($_SESSION['last_seen']) && time() - (int) $_SESSION['last_seen'] > $idle) {
        $_SESSION = [];
        session_destroy();
        return null;
    }
    $_SESSION['last_seen'] = time();
    return q1("SELECT * FROM admin_users WHERE id = ? AND active = 1", [$_SESSION['admin_id']]);
}

function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: login.php?again=1'); exit; }
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
        'rates.php'    => 'Special prices',
        'enquiries.php'=> 'Enquiries',
    ];
    $here = basename($_SERVER['PHP_SELF']);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>' . h($title) . ' · Reservations</title><meta name="robots" content="noindex">
      <link rel="icon" type="image/svg+xml" href="../assets/icon.svg">
      <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../assets/engine.css?v=' . filemtime(__DIR__ . '/../assets/engine.css') . '">
      <link rel="stylesheet" href="admin.css?v=' . filemtime(__DIR__ . '/admin.css') . '">';
    if ($user) {
        // A sign-in only counts in the tab it was made in: a new tab or window has
        // no mark in sessionStorage, so it is signed out and asked for the password.
        echo '<script>(function () { try {
          var q = new URLSearchParams(location.search);
          if (q.get("signed_in") === "1") {
            sessionStorage.setItem("ksr_admin_tab", "1");
            q.delete("signed_in");
            history.replaceState(null, "", location.pathname + (q.toString() ? "?" + q : "") + location.hash);
          } else if (!sessionStorage.getItem("ksr_admin_tab")) {
            location.replace("logout.php?again=1");
          }
        } catch (e) {} })();</script>';
    }
    echo '</head><body class="admin">';
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

function admin_foot(): void {
    // Anything marked data-confirm asks in a box on the page, never a browser pop-up.
    echo <<<'HTML'
</div>
<div class="ask" id="ask" hidden>
  <div class="ask__box" role="dialog" aria-modal="true" aria-labelledby="askText">
    <p id="askText"></p>
    <div class="ask__btns"><button type="button" class="btn btn--plain" id="askNo">Go back</button><button type="button" class="btn" id="askYes">OK</button></div>
  </div>
</div>
<script>
(function () {
  var box = document.getElementById('ask'), yes = document.getElementById('askYes'), no = document.getElementById('askNo'), go = null;
  function ask(el, then) {
    document.getElementById('askText').textContent = el.getAttribute('data-confirm');
    yes.textContent = el.getAttribute('data-ok') || 'OK';
    yes.classList.toggle('btn--danger', !!el.getAttribute('data-danger'));
    go = then; box.hidden = false; yes.focus();
  }
  function close() { box.hidden = true; go = null; }
  yes.addEventListener('click', function () { var f = go; close(); if (f) f(); });
  no.addEventListener('click', close);
  box.addEventListener('click', function (e) { if (e.target === box) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden) close(); });
  // A button that asks first: remember it so the form still sends its name/value.
  document.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-confirm], a[data-confirm]');
    if (!b || b.dataset.asked) return;
    e.preventDefault();
    ask(b, function () { b.dataset.asked = '1'; b.click(); delete b.dataset.asked; });
  }, true);
  // A form that asks first.
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute('data-confirm') || f.dataset.asked) return;
    e.preventDefault();
    var via = e.submitter;
    ask(f, function () { f.dataset.asked = '1'; if (f.requestSubmit) f.requestSubmit(via || undefined); else f.submit(); });
  }, true);
})();
</script>
</body></html>
HTML;
}
