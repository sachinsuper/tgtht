<?php
require __DIR__ . '/auth.php';
require_login();

$where  = [];
$params = [];
if (!empty($_GET['page_id'])) { $where[] = 'page_id = ?'; $params[] = (int) $_GET['page_id']; }
if (!empty($_GET['status']))  { $where[] = 'status = ?';  $params[] = $_GET['status']; }
if (!empty($_GET['q'])) {
    $where[] = '(phone LIKE ? OR name LIKE ? OR email LIKE ?)';
    $like = '%' . $_GET['q'] . '%';
    array_push($params, $like, $like, $like);
}

$rows = all('SELECT * FROM leads' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC', $params);

$name = 'leads-' . date('Y-m-d-Hi') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");                       // BOM so Excel reads UTF-8

fputcsv($out, ['ID', 'Received', 'Phone', 'Name', 'Email', 'Message', 'Page',
               'UTM source', 'UTM medium', 'UTM campaign', 'Status', 'IP'], ',', '"', '');

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'], $r['created_at'],
        // Leading apostrophe keeps Excel from mangling the number
        "'" . $r['phone'],
        $r['name'], $r['email'], $r['message'], $r['page_slug'],
        $r['utm_source'], $r['utm_medium'], $r['utm_campaign'], $r['status'], $r['ip'],
    ], ',', '"', '');
}
fclose($out);
