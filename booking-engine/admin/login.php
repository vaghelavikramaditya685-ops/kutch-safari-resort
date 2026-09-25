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
        $u = q1("SELECT * FROM admin_users WHERE email = ? AND active = 1", [trim($_POST['email'] ?? '')]);
        if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $u['id'];
            update('admin_users', (int) $u['id'], ['last_login' => now()]);
            audit('login_ok', 'admin', $u['id'], null, $u['email']);
            header('Location: index.php');
            exit;
        }
        audit('login_failed', 'admin', null, ['email' => $_POST['email'] ?? '']);
        $error = 'That email and password do not match.';
    }
}

admin_head('Sign in');
?>
<div style="max-width:400px;margin:40px auto">
  <div class="panel">
    <h2>Reservations</h2>
    <p style="font-size:.86rem">Sign in to manage bookings.</p>
    <?php if ($error): ?><div class="notice notice--err"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field" style="margin-bottom:14px">
        <label for="email">Email</label><input id="email" name="email" type="email" required autofocus>
      </div>
      <div class="field" style="margin-bottom:18px">
        <label for="password">Password</label><input id="password" name="password" type="password" required>
      </div>
      <button class="btn btn--block" type="submit">Sign in</button>
    </form>
  </div>
</div>
<?php admin_foot(); ?>
