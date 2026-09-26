<?php
require_once __DIR__ . '/_auth.php';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Slow down repeated guesses from one address.
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $recent = (int) qval("SELECT COUNT(*) FROM audit_log WHERE action = 'login_failed' AND ip = ? AND created_at > ?",
                         [$ip, date('Y-m-d H:i:s', time() - 900)], 0);
    if ($recent >= (int) cfg('admin.login_attempts', 6)) {
        $error = 'Too many attempts. Please wait fifteen minutes.';
    } else {
        // admin_users.email holds the sign-in name: a username (e.g. "manvir") or an email address.
        $u = q1("SELECT * FROM admin_users WHERE LOWER(email) = LOWER(?) AND active = 1", [trim($_POST['email'] ?? '')]);
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id'];
            update('admin_users', (int) $u['id'], ['last_login' => now()]);
            audit('login_ok', 'admin', $u['id'], null, $u['email']);
            $_SESSION['last_seen'] = time();
            header('Location: index.php?signed_in=1');   // marks this tab as signed in
            exit;
        }
        audit('login_failed', 'admin', null, ['email' => $_POST['email'] ?? '']);
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
    <form method="post">
      <?= csrf_field() ?>
      <div class="field" style="margin-bottom:14px">
        <label for="email">Username</label><input id="email" name="email" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
      </div>
      <div class="field" style="margin-bottom:18px">
        <label for="password">Password</label><input id="password" name="password" type="password" required>
      </div>
      <button class="btn btn--block" type="submit">Sign in</button>
    </form>
  </div>
</div>
<?php admin_foot(); ?>
