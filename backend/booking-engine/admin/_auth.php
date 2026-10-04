<?php
/* Admin session handling. Shared by every page in this folder. */
require_once __DIR__ . '/../lib/db.php';

/* Form fields are text, except the few that are lists by design: the rooms and
 * extras on Change booking, and the cottages on Special prices. A list anywhere
 * else (note[]=x, which only a hand-made request sends) would be saved as the word
 * "Array" or crash a page, so it becomes blank and is refused like an empty field. */
foreach ($_POST as $k => $v) {
    if (!is_array($v)) continue;
    if (!in_array($k, ['room', 'addon', 'plans'], true)) { $_POST[$k] = ''; continue; }
    foreach ($v as $i => $item) {
        if ($k === 'room') $_POST[$k][$i] = is_array($item) ? array_map(fn($x) => is_scalar($x) ? $x : '', $item) : [];
        elseif (!is_scalar($item)) unset($_POST[$k][$i]);
    }
}

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

// The admin panel is never shown inside another site's frame (clickjacking), the
// browser never guesses file types, and its addresses are not sent to other sites.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/* ---------------------------------------------------------------------------
 * One admin at a time (owner's requirement, 30 Sep 2026).
 * While someone has the admin panel open, nobody else can sign in or use it;
 * the next person gets in once that tab is closed or signed out. The open tab
 * checks in every 25 seconds (heartbeat.php); closing it releases the panel
 * within a few seconds, and a tab that vanishes without saying so (a crash, a
 * dead battery) releases it ADMIN_LOCK_SECONDS after it was last heard from.
 * The holder is one sign-in (session), whichever of its tabs is open.
 * ------------------------------------------------------------------------ */
const ADMIN_LOCK_SECONDS = 90;

function admin_lock_row(): ?array {
    return q1("SELECT * FROM admin_lock WHERE id = 1");
}

/** Someone is in the admin panel right now (heard from within the last 90 seconds). */
function admin_lock_live(?array $row): bool {
    if (!$row || !$row['session_id']) return false;
    $last = max(strtotime((string) $row['last_seen']) ?: 0, strtotime((string) ($row['last_beat'] ?? '')) ?: 0);
    return $last > time() - ADMIN_LOCK_SECONDS;
}

/** Take the panel for this sign-in, or refresh it. Returns the other holder's row when someone else has it. */
function admin_lock_take(array $user, bool $beat = false): ?array {
    return db_tx(function () use ($user, $beat) {
        $row = q1("SELECT * FROM admin_lock WHERE id = 1" . for_update());
        if (admin_lock_live($row) && $row['session_id'] !== session_id()) return $row;
        $mine = $row && $row['session_id'] === session_id();
        $data = ['session_id' => session_id(), 'admin_id' => (int) $user['id'], 'admin_name' => $user['name'],
                 'since' => $mine && admin_lock_live($row) ? $row['since'] : now(), 'last_seen' => now()];
        if ($beat) $data['last_beat'] = now();
        if ($row) update('admin_lock', 1, $data);
        else insert('admin_lock', ['id' => 1] + $data);
        return null;
    });
}

/** Let go of the panel, if this sign-in holds it. $soon: in a few seconds instead of now (the tab may just be moving to another admin page). */
function admin_lock_release(bool $soon = false): void {
    try {
        $when = $soon ? date('Y-m-d H:i:s', time() - ADMIN_LOCK_SECONDS + 5) : null;
        exec_sql("UPDATE admin_lock SET last_seen = ?, last_beat = ? WHERE id = 1 AND session_id = ?", [$when, $when, session_id()]);
    } catch (Throwable $e) { /* never stop a sign-out */ }
}

/** "The admin panel is in use by Manvir since 3:10 pm." */
function admin_in_use_message(array $row): string {
    $since = strtotime((string) $row['since']);
    return 'The admin panel is in use by ' . $row['admin_name'] . ' since '
         . (date('Y-m-d', $since) === date('Y-m-d') ? date('g:i a', $since) : date('j M, g:i a', $since))
         . '. Only one person can use it at a time — try again when they close it.';
}

function current_user(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    // Signed out after admin.idle_minutes without using the admin panel.
    $idle = 60 * max(1, (int) cfg('admin.idle_minutes', 10));
    if (isset($_SESSION['last_seen']) && time() - (int) $_SESSION['last_seen'] > $idle) {
        admin_lock_release();
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
    if ($other = admin_lock_take($u)) {
        // Someone else has the panel: nothing on this page is shown or changed.
        http_response_code(423);
        admin_head('In use');
        echo '<div class="panel" style="max-width:560px;margin:40px auto"><h2>Admin panel in use</h2><p role="alert">'
           . h(admin_in_use_message($other)) . '</p><p><a class="btn btn--plain btn--sm" href="logout.php">Sign out</a></p></div>';
        admin_foot();
        exit;
    }
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

require_once __DIR__ . '/../lib/debug.php';

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
      <link rel="icon" href="../favicon.ico" sizes="any">
      <link rel="icon" type="image/svg+xml" href="../assets/icon.svg">
      <link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="../assets/engine.css?v=' . filemtime(__DIR__ . '/../assets/engine.css') . '">
      <link rel="stylesheet" href="admin.css?v=' . filemtime(__DIR__ . '/admin.css') . '">';
    if ($user) {
        // A sign-in only counts in the tab it was made in. A new tab or window has no
        // mark in sessionStorage: it goes to tab.php, which says the panel is already
        // open in another tab (without signing that tab out), or asks for the password.
        // The marked tab checks in every 25 s (heartbeat.php) so the panel stays its
        // own, and lets go when it is closed; if it is signed out meanwhile (idle,
        // or signed in again elsewhere), it says so instead of failing on the next save.
        echo '<script>(function () { try {
          var q = new URLSearchParams(location.search), K = "ksr_admin_tab";
          if (q.get("signed_in") === "1") {
            sessionStorage.setItem(K, Math.random().toString(36).slice(2));
            q.delete("signed_in");
            history.replaceState(null, "", location.pathname + (q.toString() ? "?" + q : "") + location.hash);
          } else if (!sessionStorage.getItem(K)) {
            location.replace("tab.php");
            return;
          }
          var tab = sessionStorage.getItem(K);
          function beat(release) {
            var body = new URLSearchParams({ tab: tab, release: release ? "1" : "" });
            if (release) { navigator.sendBeacon && navigator.sendBeacon("heartbeat.php", body); return; }
            fetch("heartbeat.php", { method: "POST", body: body, credentials: "same-origin" })
              .then(function (r) { return r.json(); })
              .then(function (r) { if (!r.ok) gone(r.error); })
              .catch(function () { /* offline for a moment: the next beat tries again */ });
          }
          function gone(msg) {
            clearInterval(timer);
            if (document.getElementById("adminGone")) return;
            var d = document.createElement("div");
            d.id = "adminGone"; d.className = "admin-gone"; d.setAttribute("role", "alert");
            d.innerHTML = "<p></p><a class=\"btn btn--sm\" href=\"login.php?again=1\">Sign in again</a>";
            d.firstChild.textContent = msg || "You have been signed out of the admin panel.";
            document.body.appendChild(d);
          }
          var timer = setInterval(beat, 25000);
          beat(false);
          addEventListener("pagehide", function () { beat(true); });
          addEventListener("pageshow", function (e) { if (e.persisted) beat(false); });
        } catch (e) {} })();</script>';
    }
    echo '</head><body class="admin">';
    // Every admin page has one top-level heading for screen readers; the visible titles stay as they are.
    echo '<h1 class="sr-only">' . h($title) . ' · Reservations</h1>';
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
    debug_bar();   // local only; see lib/debug.php
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
