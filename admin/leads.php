<?php
require __DIR__ . '/auth.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $id  = (int) ($_POST['id'] ?? 0);

    if ($act === 'status' && $id) {
        $s = in_array($_POST['status'] ?? '', ['new', 'contacted', 'converted', 'junk'], true)
             ? $_POST['status'] : 'new';
        q('UPDATE leads SET status = ? WHERE id = ?', [$s, $id]);
    }
    if ($act === 'delete' && $id && $user['role'] === 'admin') {
        q('DELETE FROM leads WHERE id = ?', [$id]);
        flash('Lead deleted.');
    }
    redirect('leads.php' . (isset($_POST['q_page']) && $_POST['q_page'] !== '' ? '?page_id=' . (int) $_POST['q_page'] : ''));
}

$pageId = isset($_GET['page_id']) && $_GET['page_id'] !== '' ? (int) $_GET['page_id'] : null;
$status = $_GET['status'] ?? '';
$search = trim((string) ($_GET['q'] ?? ''));

$where  = [];
$params = [];
if ($pageId)  { $where[] = 'page_id = ?';  $params[] = $pageId; }
if ($status)  { $where[] = 'status = ?';   $params[] = $status; }
if ($search)  { $where[] = '(phone LIKE ? OR name LIKE ? OR email LIKE ?)';
                $like = '%' . $search . '%'; array_push($params, $like, $like, $like); }

$sql   = 'SELECT * FROM leads' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 500';
$leads = all($sql, $params);
$pages = all('SELECT id, name FROM pages ORDER BY name');

$byStatus = [];
foreach (all('SELECT status, COUNT(*) AS c FROM leads GROUP BY status') as $r) {
    $byStatus[$r['status']] = (int) $r['c'];
}

$title  = 'Leads';
$active = 'leads';
include __DIR__ . '/layout.php';

$qs = fn(array $over = []) => '?' . http_build_query(array_filter(array_merge(
        ['page_id' => $pageId, 'status' => $status, 'q' => $search], $over
      ), fn($v) => $v !== null && $v !== ''));
?>

<div class="page-head">
  <h1>Leads <span class="muted"><?= count($leads) ?><?= count($leads) === 500 ? '+' : '' ?></span></h1>
  <a class="btn" href="export.php<?= e($qs()) ?>">Download CSV</a>
</div>

<form class="card filters" method="get">
  <select name="page_id" onchange="this.form.submit()">
    <option value="">All pages</option>
    <?php foreach ($pages as $p): ?>
      <option value="<?= (int) $p['id'] ?>" <?= $pageId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
    <?php endforeach; ?>
  </select>

  <select name="status" onchange="this.form.submit()">
    <option value="">All statuses</option>
    <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'junk' => 'Junk'] as $k => $lbl): ?>
      <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>>
        <?= $lbl ?><?= isset($byStatus[$k]) ? ' (' . $byStatus[$k] . ')' : '' ?>
      </option>
    <?php endforeach; ?>
  </select>

  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search phone, name, email">
  <button class="btn btn--ghost btn--sm" type="submit">Filter</button>
  <?php if ($pageId || $status || $search): ?>
    <a class="btn btn--ghost btn--sm" href="leads.php">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$leads): ?>
  <div class="card empty">No leads yet. They appear here the moment someone submits the call-back form.</div>
<?php else: ?>
<div class="table-wrap card">
  <table class="table">
    <thead>
      <tr><th>When</th><th>Phone</th><th>Page</th><th>Campaign</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($leads as $l): ?>
      <tr class="<?= $l['status'] === 'new' ? 'is-new' : '' ?>">
        <td class="small muted nowrap"><?= e(substr((string) $l['created_at'], 0, 16)) ?></td>
        <td>
          <a class="strong" href="tel:+91<?= e($l['phone']) ?>"><?= e($l['phone']) ?></a>
          <?php if ($l['name']): ?><div class="small muted"><?= e($l['name']) ?></div><?php endif; ?>
          <?php if ($l['email']): ?><div class="small muted"><?= e($l['email']) ?></div><?php endif; ?>
        </td>
        <td class="small"><?= e($l['page_slug']) ?></td>
        <td class="small muted">
          <?php $camp = trim(implode(' / ', array_filter([$l['utm_source'], $l['utm_medium'], $l['utm_campaign']])), ' /'); ?>
          <?= $camp !== '' ? e($camp) : '<span class="muted">direct</span>' ?>
        </td>
        <td>
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
            <input type="hidden" name="q_page" value="<?= e((string) $pageId) ?>">
            <select name="status" class="status-select status-<?= e($l['status']) ?>" onchange="this.form.submit()">
              <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'junk' => 'Junk'] as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $l['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td class="row-actions nowrap">
          <a class="btn btn--ghost btn--sm" href="https://wa.me/91<?= e($l['phone']) ?>" target="_blank" rel="noopener">WhatsApp</a>
          <?php if ($user['role'] === 'admin'): ?>
          <form method="post" class="inline" onsubmit="return confirm('Delete this lead?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
            <input type="hidden" name="q_page" value="<?= e((string) $pageId) ?>">
            <button class="btn btn--ghost btn--sm btn--danger" type="submit">&times;</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
