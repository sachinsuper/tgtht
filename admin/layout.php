<?php
/** Shared admin chrome. Set $title and $active before including. */
$u        = current_user();
$title    = $title    ?? 'Admin';
$active   = $active   ?? '';
$newLeads = 0;
try {
    $r = one("SELECT COUNT(*) AS c FROM leads WHERE status = 'new'");
    $newLeads = (int) ($r['c'] ?? 0);
} catch (Throwable $e) {
}
$f = flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> &middot; Landing Pages</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<header class="topbar">
  <div class="topbar__inner">
    <a class="brand" href="index.php">Landing&nbsp;Pages</a>
    <nav class="nav">
      <a href="index.php"    class="<?= $active === 'pages'    ? 'is-active' : '' ?>">Pages</a>
      <a href="leads.php"    class="<?= $active === 'leads'    ? 'is-active' : '' ?>">Leads<?= $newLeads ? ' <span class="badge">' . $newLeads . '</span>' : '' ?></a>
      <a href="settings.php" class="<?= $active === 'settings' ? 'is-active' : '' ?>">Settings</a>
      <?php if (($u['role'] ?? '') === 'admin'): ?>
        <a href="users.php" class="<?= $active === 'users' ? 'is-active' : '' ?>">Users</a>
      <?php endif; ?>
    </nav>
    <div class="topbar__right">
      <span class="whoami"><?= e($u['name'] ?? '') ?></span>
      <a class="btn btn--ghost btn--sm" href="logout.php">Sign out</a>
    </div>
  </div>
</header>

<?php if (is_file(dirname(__DIR__) . '/install.php')): ?>
  <div class="strip strip--danger">
    <strong>Security:</strong> <code>install.php</code> is still on the server. Delete it now.
  </div>
<?php endif; ?>

<main class="page">
<?php if ($f): ?>
  <div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endif; ?>
