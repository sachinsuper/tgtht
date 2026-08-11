<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/templates.php';

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_name('lpadmin');
    session_start();
}

function current_user(): ?array
{
    static $u = null;
    if ($u !== null) {
        return $u ?: null;
    }
    $id = $_SESSION['uid'] ?? null;
    $u  = $id ? (one('SELECT * FROM users WHERE id = ?', [$id]) ?: false) : false;
    return $u ?: null;
}

function require_login(): array
{
    if (!tables_exist()) {
        redirect(base_url() . '/install.php');
    }
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('login.php');
    }
    // Idle timeout: 8 hours
    if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 8 * 3600) {
        session_destroy();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_seen'] = time();
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('You need an administrator account for this page.');
    }
    return $u;
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/**
 * Handle one uploaded image and return its stored relative path,
 * or null when nothing was uploaded.
 */
function handle_upload(string $inputName, ?string &$error = null): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $f = $_FILES[$inputName];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed (error code ' . $f['error'] . '). The file may be larger than the server allows.';
        return null;
    }
    if ($f['size'] > MAX_UPLOAD_BYTES) {
        $error = 'That image is larger than ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.';
        return null;
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $info = @getimagesize($f['tmp_name']);
    $ext  = $info ? ($allowed[$info[2]] ?? null) : null;

    // SVG has no getimagesize signature -- allow it only by extension + sniff.
    if (!$ext) {
        $origExt = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $head    = (string) @file_get_contents($f['tmp_name'], false, null, 0, 512);
        if ($origExt === 'svg' && stripos($head, '<svg') !== false && stripos($head, '<script') === false) {
            $ext = 'svg';
        }
    }
    if (!$ext) {
        $error = 'Only JPG, PNG, GIF, WEBP or SVG images are accepted.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0775, true)) {
        $error = 'The uploads folder is not writable. Set its permissions to 755.';
        return null;
    }

    $base = slugify(pathinfo($f['name'], PATHINFO_FILENAME)) ?: 'image';
    $name = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
    $dest = rtrim(UPLOAD_DIR, '/') . '/' . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $error = 'Could not save the uploaded file.';
        return null;
    }
    @chmod($dest, 0644);

    return UPLOAD_URL . '/' . $name;
}
