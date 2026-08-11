<?php
/**
 * One-time installer. Open this in a browser once, create your admin login,
 * then DELETE this file (the app will nag you until you do).
 */
define('LP_INSTALLER', true);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/templates.php';

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/**
 * Create the database if it isn't there yet.
 *
 * On a local stack (Laragon, XAMPP, MAMP) the root user can do this, so there
 * is nothing to set up by hand. On shared hosting the account usually cannot
 * CREATE DATABASE -- that's fine, you make it in cPanel first and this simply
 * does nothing.
 */
function ensure_database(): ?string
{
    if (DB_DRIVER !== 'mysql') {
        return null;
    }
    $name = preg_replace('/[^A-Za-z0-9_]/', '', DB_NAME);
    if ($name === '') {
        return 'DB_NAME in config.php is empty or invalid.';
    }
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name`
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return null;
    } catch (Throwable $e) {
        return $e->getMessage();
    }
}

$dbWarning = ensure_database();

$pk     = pk_type();
$sfx    = table_suffix();
$errors = [];
$done   = false;

function create_schema(string $pk, string $sfx): void
{
    $ts = DB_DRIVER === 'sqlite' ? 'TEXT' : 'DATETIME';

    db()->exec("CREATE TABLE IF NOT EXISTS users (
        id $pk,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'editor',
        created_at $ts NULL
    )$sfx");

    db()->exec("CREATE TABLE IF NOT EXISTS pages (
        id $pk,
        slug VARCHAR(190) NOT NULL,
        name VARCHAR(190) NOT NULL,
        template VARCHAR(80) NOT NULL DEFAULT 'store-locator',
        status VARCHAR(20) NOT NULL DEFAULT 'published',
        meta_title VARCHAR(255) NULL,
        meta_description TEXT NULL,
        og_image VARCHAR(255) NULL,
        head_code TEXT NULL,
        body_code TEXT NULL,
        notify_emails TEXT NULL,
        created_at $ts NULL,
        updated_at $ts NULL
    )$sfx");

    db()->exec("CREATE TABLE IF NOT EXISTS blocks (
        id $pk,
        page_id INT NOT NULL,
        block_key VARCHAR(120) NOT NULL,
        value TEXT NULL
    )$sfx");

    db()->exec("CREATE TABLE IF NOT EXISTS leads (
        id $pk,
        page_id INT NULL,
        page_slug VARCHAR(190) NULL,
        name VARCHAR(190) NULL,
        phone VARCHAR(60) NULL,
        email VARCHAR(190) NULL,
        message TEXT NULL,
        utm_source VARCHAR(120) NULL,
        utm_medium VARCHAR(120) NULL,
        utm_campaign VARCHAR(190) NULL,
        ip VARCHAR(60) NULL,
        user_agent VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        created_at $ts NULL
    )$sfx");

    db()->exec("CREATE TABLE IF NOT EXISTS settings (
        skey VARCHAR(120) NOT NULL PRIMARY KEY,
        svalue TEXT NULL
    )$sfx");

    // Indexes (ignore failures -- MySQL has no IF NOT EXISTS for indexes)
    foreach ([
        'CREATE UNIQUE INDEX idx_pages_slug ON pages (slug)',
        'CREATE UNIQUE INDEX idx_users_email ON users (email)',
        'CREATE INDEX idx_blocks_page ON blocks (page_id)',
        'CREATE INDEX idx_blocks_key ON blocks (page_id, block_key)',
        'CREATE INDEX idx_leads_page ON leads (page_id)',
    ] as $sql) {
        try { db()->exec($sql); } catch (Throwable $e) { /* already there */ }
    }
}

function seed_first_page(): int
{
    $existing = one('SELECT id FROM pages WHERE slug = ?', [DEFAULT_SLUG]);
    if ($existing) {
        return (int) $existing['id'];
    }
    q('INSERT INTO pages (slug, name, template, status, meta_title, meta_description, notify_emails, created_at, updated_at)
       VALUES (?,?,?,?,?,?,?,?,?)', [
        DEFAULT_SLUG,
        'Technogym in India',
        'store-locator',
        'published',
        'Technogym India | Visit our Bengaluru & New Delhi stores',
        'Experience Technogym equipment in person. Visit our flagship stores in Bengaluru and New Delhi, or request a call back from a wellness expert.',
        '',
        now(), now(),
    ]);
    $id   = insert_id();
    $page = get_page($id);
    sync_page_blocks($page);
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    if ($name === '')                                   $errors[] = 'Enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Enter a valid email address.';
    if (strlen($pass) < 8)                              $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                               $errors[] = 'The two passwords do not match.';

    if (!$errors) {
        try {
            create_schema($pk, $sfx);
            if (one('SELECT id FROM users WHERE email = ?', [$email])) {
                $errors[] = 'That email already has an account. Use the login page.';
            } else {
                q('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?,?,?,?,?)',
                  [$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'admin', now()]);
                seed_first_page();
                set_setting('installed_at', now());
                $done = true;
            }
        } catch (Throwable $ex) {
            $errors[] = 'Setup failed: ' . $ex->getMessage();
        }
    }
}

$alreadyInstalled = false;
try {
    $alreadyInstalled = tables_exist() && (bool) one('SELECT id FROM users LIMIT 1');
} catch (Throwable $e) {
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup &middot; Landing Pages</title>
<link rel="stylesheet" href="admin/assets/admin.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <h1>Landing Page Manager</h1>

    <?php if ($done): ?>
        <div class="alert ok">
            <strong>All set.</strong>
            <p>Your admin account is created and the first landing page is seeded.</p>
            <p class="danger-note">Now delete <code>install.php</code> from the server.</p>
        </div>
        <a class="btn block" href="admin/login.php">Go to login &rarr;</a>

    <?php elseif ($alreadyInstalled): ?>
        <div class="alert warn">
            <strong>Already installed.</strong>
            <p>Delete <code>install.php</code> from the server, then use the login page.</p>
        </div>
        <a class="btn block" href="admin/login.php">Go to login &rarr;</a>

    <?php else: ?>
        <p class="muted">Create the first admin account. This runs once.</p>

        <?php if ($dbWarning): ?>
            <div class="alert warn">
                <strong>Could not create the database automatically.</strong>
                <p>Create <code><?= e(DB_NAME) ?></code> yourself (cPanel &rarr; MySQL Databases,
                   or phpMyAdmin), then reload this page.</p>
                <p class="small"><?= e($dbWarning) ?></p>
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $err): ?>
            <div class="alert err"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" autocomplete="off">
            <label>Your name
                <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" required>
            </label>
            <label>Repeat password
                <input type="password" name="password2" minlength="8" required>
            </label>
            <button class="btn block" type="submit">Create account &amp; install</button>
        </form>
        <p class="muted small">
            Database: <code><?= e(DB_DRIVER) ?></code><?= DB_DRIVER === 'mysql' ? ' &middot; ' . e(DB_NAME) . '@' . e(DB_HOST) : '' ?>.
            Change it at the top of <code>config.php</code>.
        </p>
    <?php endif; ?>
</div>
</body>
</html>
