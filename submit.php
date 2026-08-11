<?php
/**
 * Call-back form handler: validate -> store -> email -> redirect back.
 */
require __DIR__ . '/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/templates.php';
require __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url());
}

$pageId = (int) ($_POST['page_id'] ?? 0);
$page   = get_page($pageId) ?: get_page_by_slug(DEFAULT_SLUG);
$return = (string) ($_POST['return'] ?? '');

// Signed, stateless form token -- see includes/helpers.php for why.
if (!public_token_valid((string) ($_POST['_token'] ?? ''), $pageId)) {
    redirect(($page ? page_url($page['slug']) : base_url()) . '?err=expired#callback');
}

// Only ever redirect back to our own site.
if ($return === '' || !str_starts_with($return, base_url())) {
    $return = $page ? page_url($page['slug']) : base_url();
}

// Honeypot -- silently accept and drop.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    redirect($return . '?sent=1#callback');
}

$phoneRaw = trim((string) ($_POST['phone'] ?? ''));
$digits   = preg_replace('/\D/', '', $phoneRaw);

// Tolerate a leading 0 or 91 that users often type.
if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
    $digits = substr($digits, 1);
}
if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
    $digits = substr($digits, 2);
}

if (strlen($digits) < 10 || strlen($digits) > 12) {
    redirect($return . '?err=phone#callback');
}

// Basic flood protection: same phone, same page, within 5 minutes.
$cutoff = date('Y-m-d H:i:s', time() - 300);
$dupe   = one('SELECT id FROM leads WHERE phone = ? AND page_id = ? AND created_at > ?',
              [$digits, $pageId, $cutoff]);

if (!$dupe) {
    $lead = [
        'page_id'      => $pageId ?: null,
        'page_slug'    => $page['slug'] ?? '',
        'name'         => mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 180),
        'phone'        => $digits,
        'email'        => filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '',
        'message'      => mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000),
        'utm_source'   => mb_substr(trim((string) ($_POST['utm_source']   ?? $_GET['utm_source']   ?? '')), 0, 120),
        'utm_medium'   => mb_substr(trim((string) ($_POST['utm_medium']   ?? $_GET['utm_medium']   ?? '')), 0, 120),
        'utm_campaign' => mb_substr(trim((string) ($_POST['utm_campaign'] ?? $_GET['utm_campaign'] ?? '')), 0, 180),
        'ip'           => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 60),
        'user_agent'   => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
        'created_at'   => now(),
    ];

    q('INSERT INTO leads
        (page_id, page_slug, name, phone, email, message, utm_source, utm_medium, utm_campaign, ip, user_agent, status, created_at)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
        $lead['page_id'], $lead['page_slug'], $lead['name'], $lead['phone'], $lead['email'],
        $lead['message'], $lead['utm_source'], $lead['utm_medium'], $lead['utm_campaign'],
        $lead['ip'], $lead['user_agent'], 'new', $lead['created_at'],
    ]);

    if ($page) {
        try {
            send_lead_email($lead, $page);
        } catch (Throwable $e) {
            error_log('Lead email failed: ' . $e->getMessage());   // never block the visitor
        }
    }
}

redirect($return . '?sent=1#callback');
