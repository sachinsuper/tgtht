<?php
require __DIR__ . '/auth.php';
$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';

    if ($act === 'create') {
        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');
        $role  = ($_POST['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
            flash('Enter a name, a valid email and a password of at least 8 characters.', 'err');
        } elseif (one('SELECT id FROM users WHERE email = ?', [$email])) {
            flash('Someone already uses that email.', 'err');
        } else {
            q('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?,?,?,?,?)',
              [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, now()]);
            flash('User added.');
        }
    }

    if ($act === 'reset') {
        $id   = (int) ($_POST['id'] ?? 0);
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) {
            flash('Password must be at least 8 characters.', 'err');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            flash('Password reset.');
        }
    }

    if ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $user['id']) {
            flash('You cannot delete your own account.', 'err');
        } elseif ((int) (one('SELECT COUNT(*) AS c FROM users')['c'] ?? 0) <= 1) {
            flash('There must always be one account.', 'err');
        } else {
            q('DELETE FROM users WHERE id = ?', [$id]);
            flash('User removed.');
        }
    }

    redirect('users.php');
}

$users  = all('SELECT * FROM users ORDER BY id');
$title  = 'Users';
$active = 'users';
include __DIR__ . '/layout.php';
?>

<div class="page-head">
  <h1>Users</h1>
  <button class="btn" type="button" data-toggle="#new-user">+ Add user</button>
</div>

<form class="card panel" id="new-user" method="post" hidden>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="create">
  <div class="grid-4">
    <label>Name<input type="text" name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="text" name="password" minlength="8" required placeholder="min 8 characters"></label>
    <label>Role
      <select name="role">
        <option value="editor">Editor — edit pages &amp; leads</option>
        <option value="admin">Admin — everything, incl. delete</option>
      </select>
    </label>
  </div>
  <button class="btn" type="submit">Add user</button>
</form>

<div class="table-wrap card">
  <table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Added</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u2): ?>
      <tr>
        <td class="strong"><?= e($u2['name']) ?><?= (int) $u2['id'] === (int) $user['id'] ? ' <span class="muted small">(you)</span>' : '' ?></td>
        <td class="small"><?= e($u2['email']) ?></td>
        <td><span class="pill pill--<?= $u2['role'] === 'admin' ? 'live' : 'draft' ?>"><?= e($u2['role']) ?></span></td>
        <td class="muted small"><?= e(substr((string) $u2['created_at'], 0, 10)) ?></td>
        <td class="row-actions">
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="id" value="<?= (int) $u2['id'] ?>">
            <input type="text" name="password" placeholder="new password" minlength="8" class="small inline-input">
            <button class="btn btn--ghost btn--sm" type="submit">Reset</button>
          </form>
          <?php if ((int) $u2['id'] !== (int) $user['id']): ?>
          <form method="post" class="inline" onsubmit="return confirm('Remove <?= e($u2['name']) ?>?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $u2['id'] ?>">
            <button class="btn btn--ghost btn--sm btn--danger" type="submit">Remove</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var el = document.querySelector(btn.getAttribute('data-toggle'));
    if (el) el.hidden = !el.hidden;
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
