<?php
/* ===========================================================================
 *  Admin sign-in.
 *
 *  The typed username and password never leave the browser as plain text:
 *  the page sends SHA-256 of the username (trimmed, lower-case) and SHA-256
 *  of the password, as 64 hex characters each. The typed fields have no
 *  `name`, so they are not part of the form.
 *
 *  admin_users.password_hash stores bcrypt( sha256(password) ) — the server
 *  still uses its own slow, salted hash on top, so the database never holds
 *  the SHA-256 itself. bin/setup.php --admin writes passwords this way.
 *  The connection itself is protected by HTTPS on the live site.
 * ======================================================================== */
require_once __DIR__ . '/_auth.php';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Slow down repeated guesses from one address.
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $recent = (int) qval("SELECT COUNT(*) FROM audit_log WHERE action = 'login_failed' AND ip = ? AND created_at > ?",
                         [$ip, date('Y-m-d H:i:s', time() - 900)], 0);
    $user_sha = strtolower(trim((string) ($_POST['user_sha256'] ?? '')));
    $pass_sha = strtolower(trim((string) ($_POST['pass_sha256'] ?? '')));
    if ($recent >= (int) cfg('admin.login_attempts', 6)) {
        $error = 'Too many attempts. Please wait fifteen minutes.';
    } elseif (!preg_match('/^[a-f0-9]{64}$/', $user_sha) || !preg_match('/^[a-f0-9]{64}$/', $pass_sha)) {
        // Only the page's own script sends these; anything else is refused.
        audit('login_failed', 'admin', null, ['reason' => 'not hashed']);
        $error = 'Please sign in using this page (your browser must allow JavaScript).';
    } else {
        // admin_users.email holds the sign-in name (e.g. "manvir"); match it by its SHA-256.
        $u = null;
        foreach (q("SELECT * FROM admin_users WHERE active = 1") as $row) {
            if (hash_equals(hash('sha256', strtolower(trim((string) $row['email']))), $user_sha)) { $u = $row; break; }
        }
        if ($u && password_verify($pass_sha, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id'];
            update('admin_users', (int) $u['id'], ['last_login' => now()]);
            audit('login_ok', 'admin', $u['id'], null, $u['email']);
            $_SESSION['last_seen'] = time();
            header('Location: index.php?signed_in=1');   // marks this tab as signed in
            exit;
        }
        // Log only the start of the username hash, never the username or password.
        audit('login_failed', 'admin', null, ['user_sha256' => substr($user_sha, 0, 12)]);
        $error = 'That username and password do not match.';
    }
}

admin_head('Sign in');
?>
<div style="max-width:400px;margin:40px auto">
  <div class="panel">
    <h2>Reservations</h2>
    <p style="font-size:.86rem">Sign in to manage bookings.</p>
    <?php if ($error): ?><div class="notice notice--err"><?= h($error) ?></div>
    <?php elseif (!empty($_GET['again'])): ?><div class="notice notice--info">Please sign in. The admin panel asks for the password in every new window or tab, and after 10 minutes without use.</div><?php endif; ?>
    <noscript><div class="notice notice--err">Signing in needs JavaScript: the username and password are hashed (SHA-256) in your browser before they are sent.</div></noscript>
    <form method="post" id="loginForm">
      <?= csrf_field() ?>
      <input type="hidden" name="user_sha256" id="userSha">
      <input type="hidden" name="pass_sha256" id="passSha">
      <div class="field" style="margin-bottom:14px">
        <label for="email">Username</label><input id="email" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
      </div>
      <div class="field" style="margin-bottom:18px">
        <label for="password">Password</label><input id="password" type="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn--block" type="submit" id="loginBtn">Sign in</button>
    </form>
  </div>
</div>
<script>
(function () {
  // SHA-256 as 64 hex characters. Uses the browser's built-in crypto where it is
  // available (HTTPS and localhost); otherwise a small plain-JS version, so
  // signing in also works over plain http on a local network.
  function sha256js(msg) {
    var bytes = unescape(encodeURIComponent(msg)), K = [], H = [], i, j;
    var isPrime = function (n) { for (var f = 2; f * f <= n; f++) if (n % f === 0) return false; return true; };
    var frac = function (x) { return ((x - Math.floor(x)) * 4294967296) | 0; };
    for (var n = 2, c = 0; c < 64; n++) if (isPrime(n)) { if (c < 8) H[c] = frac(Math.pow(n, 1 / 2)); K[c++] = frac(Math.pow(n, 1 / 3)); }
    var words = [], len = bytes.length * 8;
    for (i = 0; i < bytes.length; i++) words[i >> 2] |= bytes.charCodeAt(i) << (24 - (i % 4) * 8);
    words[len >> 5] |= 0x80 << (24 - len % 32);
    words[((len + 64 >> 9) << 4) + 15] = len;
    var rot = function (x, r) { return (x >>> r) | (x << (32 - r)); };
    for (i = 0; i < words.length; i += 16) {
      var w = [], a = H[0], b = H[1], cc = H[2], d = H[3], e = H[4], f = H[5], g = H[6], h = H[7];
      for (j = 0; j < 64; j++) {
        if (j < 16) w[j] = words[i + j] | 0;
        else {
          var s0 = rot(w[j - 15], 7) ^ rot(w[j - 15], 18) ^ (w[j - 15] >>> 3);
          var s1 = rot(w[j - 2], 17) ^ rot(w[j - 2], 19) ^ (w[j - 2] >>> 10);
          w[j] = (w[j - 16] + s0 + w[j - 7] + s1) | 0;
        }
        var t1 = (h + (rot(e, 6) ^ rot(e, 11) ^ rot(e, 25)) + ((e & f) ^ (~e & g)) + K[j] + w[j]) | 0;
        var t2 = ((rot(a, 2) ^ rot(a, 13) ^ rot(a, 22)) + ((a & b) ^ (a & cc) ^ (b & cc))) | 0;
        h = g; g = f; f = e; e = (d + t1) | 0; d = cc; cc = b; b = a; a = (t1 + t2) | 0;
      }
      H[0] = (H[0] + a) | 0; H[1] = (H[1] + b) | 0; H[2] = (H[2] + cc) | 0; H[3] = (H[3] + d) | 0;
      H[4] = (H[4] + e) | 0; H[5] = (H[5] + f) | 0; H[6] = (H[6] + g) | 0; H[7] = (H[7] + h) | 0;
    }
    return H.map(function (x) { return ('00000000' + (x >>> 0).toString(16)).slice(-8); }).join('');
  }
  function sha256(msg) {
    if (window.crypto && crypto.subtle && window.TextEncoder) {
      return crypto.subtle.digest('SHA-256', new TextEncoder().encode(msg)).then(function (buf) {
        return Array.prototype.map.call(new Uint8Array(buf), function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
      });
    }
    return Promise.resolve(sha256js(msg));
  }
  var form = document.getElementById('loginForm'), busy = false;
  form.addEventListener('submit', function (e) {
    if (form.dataset.hashed) return;          // second pass: the hashes are in, send it
    e.preventDefault();
    if (busy) return;
    busy = true; document.getElementById('loginBtn').disabled = true;
    var user = document.getElementById('email').value.trim().toLowerCase();
    var pass = document.getElementById('password').value;
    Promise.all([sha256(user), sha256(pass)]).then(function (h) {
      document.getElementById('userSha').value = h[0];
      document.getElementById('passSha').value = h[1];
      document.getElementById('password').value = '';   // nothing typed stays in the page
      form.dataset.hashed = '1';
      form.submit();
    });
  });
})();
</script>
<?php admin_foot(); ?>
