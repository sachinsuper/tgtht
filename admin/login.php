<?php
require __DIR__ . '/auth.php';

if (!tables_exist()) {
    redirect(base_url() . '/install.php');
}
if (current_user()) {
    redirect('index.php');
}

$error    = '';
$attempts = &$_SESSION['login_attempts'];
$attempts = $attempts ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($attempts >= 8) {
        $error = 'Too many attempts. Close the browser and try again in a few minutes.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');
        $user  = one('SELECT * FROM users WHERE email = ?', [$email]);

        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid']            = (int) $user['id'];
            $_SESSION['last_seen']      = time();
            $_SESSION['login_attempts'] = 0;
            $next = $_SESSION['after_login'] ?? 'index.php';
            unset($_SESSION['after_login']);
            redirect($next);
        }
        $attempts++;
        $error = 'That email and password do not match.';
        usleep(400000);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in &middot; Landing Pages</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <h1>Landing Page Manager</h1>
  <p class="muted">Sign in to edit your pages.</p>

  <?php if (isset($_GET['timeout'])): ?>
    <div class="alert warn">You were signed out after 8 hours of inactivity.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert err"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <label>Email
      <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
    </label>
    <label>Password
      <input type="password" name="password" required>
    </label>
    <button class="btn block" type="submit">Sign in</button>
  </form>
</div>
</body>
</html>
