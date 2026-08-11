<?php

// ---------------------------------------------------------------- escaping --

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape but keep author-entered line breaks. */
function enl($v): string
{
    return nl2br(e($v), false);
}

// ------------------------------------------------------------------- urls --

function base_url(): string
{
    if (defined('BASE_URL') && BASE_URL !== '') {
        return rtrim(BASE_URL, '/');
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    $dir = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

/** Turn a stored relative path into a browsable URL. Leaves absolute URLs alone. */
function asset_url(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    return base_url() . '/' . ltrim($path, '/');
}

function page_url(string $slug): string
{
    return base_url() . '/' . rawurlencode($slug);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ------------------------------------------------------------------ pages --

function get_page_by_slug(string $slug): ?array
{
    return one('SELECT * FROM pages WHERE slug = ?', [$slug]);
}

function get_page(int $id): ?array
{
    return one('SELECT * FROM pages WHERE id = ?', [$id]);
}

/**
 * All content for a page as key => value.
 * Any field defined in the schema but missing in the DB falls back to its
 * default, so adding a new field never breaks an existing page.
 */
function page_blocks(array $page): array
{
    $defs   = template_fields($page['template']);
    $values = [];
    foreach ($defs as $key => $def) {
        $values[$key] = $def['default'] ?? '';
    }
    foreach (all('SELECT block_key, value FROM blocks WHERE page_id = ?', [$page['id']]) as $row) {
        $values[$row['block_key']] = $row['value'];
    }
    return $values;
}

/** Create any block rows the page is missing (used after a schema change). */
function sync_page_blocks(array $page): void
{
    $defs     = template_fields($page['template']);
    $existing = [];
    foreach (all('SELECT block_key FROM blocks WHERE page_id = ?', [$page['id']]) as $r) {
        $existing[$r['block_key']] = true;
    }
    foreach ($defs as $key => $def) {
        if (!isset($existing[$key])) {
            q('INSERT INTO blocks (page_id, block_key, value) VALUES (?,?,?)',
              [$page['id'], $key, (string) ($def['default'] ?? '')]);
        }
    }
}

function save_block(int $pageId, string $key, string $value): void
{
    $exists = one('SELECT id FROM blocks WHERE page_id = ? AND block_key = ?', [$pageId, $key]);
    if ($exists) {
        q('UPDATE blocks SET value = ? WHERE id = ?', [$value, $exists['id']]);
    } else {
        q('INSERT INTO blocks (page_id, block_key, value) VALUES (?,?,?)', [$pageId, $key, $value]);
    }
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    return trim($s, '-') ?: 'page';
}

/**
 * Slugs that would collide with a real file or folder and make the page
 * unreachable. A page named "Admin" becomes "admin-page" instead.
 */
function reserved_slugs(): array
{
    return ['admin', 'assets', 'uploads', 'includes', 'templates', 'storage',
            'index', 'install', 'submit', 'config', 'vendor', 'api'];
}

function unique_slug(string $base, ?int $ignoreId = null): string
{
    $slug = slugify($base);
    if (in_array($slug, reserved_slugs(), true)) {
        $slug = $slug . '-page';
        $base = $slug;
    }
    $i    = 1;
    while (true) {
        $row = $ignoreId
            ? one('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $ignoreId])
            : one('SELECT id FROM pages WHERE slug = ?', [$slug]);
        if (!$row) {
            return $slug;
        }
        $slug = slugify($base) . '-' . (++$i);
    }
}

// --------------------------------------------------------------- settings --

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (all('SELECT skey, svalue FROM settings') as $r) {
            $cache[$r['skey']] = $r['svalue'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $exists = one('SELECT skey FROM settings WHERE skey = ?', [$key]);
    if ($exists) {
        q('UPDATE settings SET svalue = ? WHERE skey = ?', [$value, $key]);
    } else {
        q('INSERT INTO settings (skey, svalue) VALUES (?,?)', [$key, $value]);
    }
}

// ------------------------------------------------------------------- csrf --

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $sent   = (string) ($_POST['_csrf'] ?? '');
    $stored = (string) ($_SESSION['csrf'] ?? '');
    // Both empty must NOT pass -- hash_equals('','') is true.
    if ($sent === '' || $stored === '' || !hash_equals($stored, $sent)) {
        http_response_code(419);
        exit('Session expired. Please go back, reload the page and try again.');
    }
}

// ---------------------------------------------------- public form tokens --
//
// The public landing page deliberately sets NO cookie and starts NO session:
// it has to stay fast and cacheable for paid traffic. The form is protected
// with a stateless signed token (valid ~2 hours) plus a honeypot and a
// duplicate-submission window, which is what actually stops form spam.

function public_token(int $pageId, ?int $window = null): string
{
    $window ??= (int) floor(time() / 3600);
    return $window . '.' . hash_hmac('sha256', $pageId . '|' . $window, APP_SECRET);
}

function public_token_valid(string $token, int $pageId): bool
{
    if (!str_contains($token, '.')) {
        return false;
    }
    [$window, ] = explode('.', $token, 2);
    $window = (int) $window;
    $nowW   = (int) floor(time() / 3600);

    // Accept the current hour and the previous one, so a form left open for
    // a while still submits.
    if ($window !== $nowW && $window !== $nowW - 1) {
        return false;
    }
    return hash_equals(public_token($pageId, $window), $token);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}
