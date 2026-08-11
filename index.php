<?php
/**
 * Public page renderer.
 *   /                      -> DEFAULT_SLUG
 *   /technogym-india       -> that page  (needs .htaccess, included)
 *   /?p=technogym-india    -> same page, works without .htaccess
 */
require __DIR__ . '/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/templates.php';

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (!tables_exist()) {
    redirect(base_url() . '/install.php');
}

// Ads and print material often show the URL in capitals (TGTHT.COM/HOME-SOLUTION).
// Browsers lower-case the domain but NOT the path, so match case-insensitively
// and then 301 to the canonical lower-case address -- one URL for SEO, and the
// visitor still lands on the page whatever case they typed.
$raw  = trim((string) ($_GET['p'] ?? ''), '/');
$slug = strtolower($raw);
if ($slug === '') {
    $slug = DEFAULT_SLUG;
}

$page = get_page_by_slug($slug);

if ($page && $raw !== '' && $raw !== $page['slug']) {
    header('Location: ' . page_url($page['slug']), true, 301);
    exit;
}

if (!$page || $page['status'] !== 'published') {
    // Allow a logged-in admin to preview drafts.
    $preview = false;
    // Only touch the session if an admin cookie is actually present, so normal
    // ad traffic never gets a cookie or a session file.
    if ($page && isset($_COOKIE['lpadmin'])) {
        session_name('lpadmin');
        session_start();
        $preview = !empty($_SESSION['uid']);
    }
    if (!$preview) {
        http_response_code(404);
        $fallback = one("SELECT * FROM pages WHERE status = 'published' ORDER BY id LIMIT 1");
        echo '<!doctype html><meta charset="utf-8"><title>Page not found</title>'
           . '<div style="font:16px/1.6 system-ui;max-width:32rem;margin:12vh auto;padding:0 1.5rem">'
           . '<h1 style="font-size:1.5rem">Page not found</h1>'
           . '<p style="color:#666">No landing page is published at this address.</p>';
        if ($fallback) {
            echo '<p><a style="color:#0a58ca" href="' . e(page_url($fallback['slug'])) . '">'
               . e($fallback['name']) . ' &rarr;</a></p>';
        }
        echo '</div>';
        exit;
    }
}

$B    = page_blocks($page);
$tpl  = templates()[$page['template']] ?? null;
$file = __DIR__ . '/templates/' . ($tpl['file'] ?? 'store-locator.php');

if (!is_file($file)) {
    http_response_code(500);
    exit('Template file missing: ' . e(basename($file)));
}

// Available inside the template: $page, $B (content), plus these helpers.
$b   = fn(string $k, string $d = '') => (string) ($B[$k] ?? $d);
$on  = fn(string $k) => (string) ($B[$k] ?? '') === '1';
$img = fn(string $k) => asset_url((string) ($B[$k] ?? ''));

include $file;
