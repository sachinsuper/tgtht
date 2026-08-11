<?php
require __DIR__ . '/auth.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'save') {
        set_setting('notify_emails', trim((string) ($_POST['notify_emails'] ?? '')));
        flash('Settings saved.');
        redirect('settings.php');
    }

    if (($_POST['action'] ?? '') === 'testmail') {
        require_once dirname(__DIR__) . '/includes/mailer.php';
        $to = trim((string) ($_POST['test_to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('Enter a valid email address to test with.', 'err');
        } else {
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n"
                     . 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
            $ok = @mail($to, 'Test email from your landing pages',
                '<p>If you are reading this, lead notifications will work.</p>', $headers);
            flash($ok
                ? 'Test email handed to the server. Check the inbox and the spam folder.'
                : 'The server refused to send. Ask your host to enable mail(), or switch on SMTP in config.php.',
                $ok ? 'ok' : 'err');
        }
        redirect('settings.php');
    }

    if (($_POST['action'] ?? '') === 'password') {
        $cur  = (string) ($_POST['current'] ?? '');
        $new  = (string) ($_POST['new'] ?? '');
        $new2 = (string) ($_POST['new2'] ?? '');
        if (!password_verify($cur, $user['password_hash'])) {
            flash('Your current password is not correct.', 'err');
        } elseif (strlen($new) < 8) {
            flash('New password must be at least 8 characters.', 'err');
        } elseif ($new !== $new2) {
            flash('The two new passwords do not match.', 'err');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?',
              [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Password changed.');
        }
        redirect('settings.php');
    }
}

$title  = 'Settings';
$active = 'settings';
include __DIR__ . '/layout.php';
?>

<div class="page-head"><h1>Settings</h1></div>

<form class="card group" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <div class="group__head">Lead notifications</div>
  <div class="group__body">
    <label class="field">
      <span class="field__label">Email every lead to</span>
      <input type="text" name="notify_emails" value="<?= e(setting('notify_emails')) ?>"
             placeholder="sales@example.com, manager@example.com">
      <span class="help">Comma separated. Applies to every page; each page can add its own recipients too.</span>
    </label>
    <button class="btn" type="submit">Save</button>
  </div>
</form>

<form class="card group" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="testmail">
  <div class="group__head">Test email delivery</div>
  <div class="group__body">
    <label class="field">
      <span class="field__label">Send a test email to</span>
      <input type="email" name="test_to" value="<?= e($user['email']) ?>">
      <span class="help">
        Sending as <code><?= e(MAIL_FROM) ?></code><?= SMTP_ENABLED ? ' over SMTP' : ' via PHP mail()' ?>.
        If it does not arrive, the From address probably is not on this domain.
      </span>
    </label>
    <button class="btn btn--ghost" type="submit">Send test</button>
  </div>
</form>

<form class="card group" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="group__head">Change your password</div>
  <div class="group__body">
    <div class="grid-3">
      <label class="field"><span class="field__label">Current password</span>
        <input type="password" name="current" required></label>
      <label class="field"><span class="field__label">New password</span>
        <input type="password" name="new" minlength="8" required></label>
      <label class="field"><span class="field__label">Repeat new password</span>
        <input type="password" name="new2" minlength="8" required></label>
    </div>
    <button class="btn" type="submit">Change password</button>
  </div>
</form>

<div class="card group">
  <div class="group__head">System</div>
  <div class="group__body sysinfo">
    <div><span>Database</span><code><?= e(DB_DRIVER) ?><?= DB_DRIVER === 'mysql' ? ' &middot; ' . e(DB_NAME) : '' ?></code></div>
    <div><span>PHP</span><code><?= e(PHP_VERSION) ?></code></div>
    <div><span>Base URL</span><code><?= e(base_url()) ?></code></div>
    <div><span>Uploads folder</span><code><?= is_writable(UPLOAD_DIR) ? 'writable' : 'NOT WRITABLE — set to 755' ?></code></div>
    <div><span>Max upload</span><code><?= round(MAX_UPLOAD_BYTES / 1048576, 1) ?> MB (PHP allows <?= e(ini_get('upload_max_filesize')) ?>)</code></div>
    <div><span>Debug mode</span><code><?= DEBUG ? 'ON — turn off in config.php before going live' : 'off' ?></code></div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
