<?php
require __DIR__ . '/auth.php';
$user = require_login();

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ---------------------------------------------------------- new page --
    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $tpl  = (string) ($_POST['template'] ?? 'store-locator');
        if ($name === '') {
            flash('Give the page a name.', 'err');
        } elseif (!isset(templates()[$tpl])) {
            flash('Unknown template.', 'err');
        } else {
            $slug = unique_slug($_POST['slug'] !== '' ? $_POST['slug'] : $name);
            q('INSERT INTO pages (slug, name, template, status, meta_title, created_at, updated_at)
               VALUES (?,?,?,?,?,?,?)',
              [$slug, $name, $tpl, 'draft', $name, now(), now()]);
            $id = insert_id();
            sync_page_blocks(get_page($id));
            flash('Page created. Edit the content, then publish it.');
            redirect('edit.php?id=' . $id);
        }
    }

    // -------------------------------------------------------- clone page --
    if ($action === 'clone') {
        $src = get_page((int) ($_POST['id'] ?? 0));
        if ($src) {
            $name = $src['name'] . ' (copy)';
            $slug = unique_slug($src['slug'] . '-copy');
            q('INSERT INTO pages (slug, name, template, status, meta_title, meta_description, og_image, head_code, body_code, notify_emails, created_at, updated_at)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', [
                $slug, $name, $src['template'], 'draft',
                $src['meta_title'], $src['meta_description'], $src['og_image'],
                $src['head_code'], $src['body_code'], $src['notify_emails'], now(), now(),
            ]);
            $newId = insert_id();
            foreach (all('SELECT block_key, value FROM blocks WHERE page_id = ?', [$src['id']]) as $bk) {
                q('INSERT INTO blocks (page_id, block_key, value) VALUES (?,?,?)',
                  [$newId, $bk['block_key'], $bk['value']]);
            }
            flash('Duplicated. This copy is a draft until you publish it.');
            redirect('edit.php?id=' . $newId);
        }
    }

    // ------------------------------------------------------ toggle/delete --
    if ($action === 'toggle') {
        $p = get_page((int) ($_POST['id'] ?? 0));
        if ($p) {
            $new = $p['status'] === 'published' ? 'draft' : 'published';
            q('UPDATE pages SET status = ?, updated_at = ? WHERE id = ?', [$new, now(), $p['id']]);
            flash($new === 'published' ? 'Page is live.' : 'Page unpublished.');
        }
    }

    if ($action === 'delete' && $user['role'] === 'admin') {
        $p = get_page((int) ($_POST['id'] ?? 0));
        if ($p) {
            q('DELETE FROM blocks WHERE page_id = ?', [$p['id']]);
            q('DELETE FROM pages WHERE id = ?', [$p['id']]);
            flash('Page deleted. Its leads were kept.');
        }
    }

    redirect('index.php');
}

$pages = all('SELECT * FROM pages ORDER BY id');
$counts = [];
foreach (all('SELECT page_id, COUNT(*) AS c FROM leads GROUP BY page_id') as $r) {
    $counts[(int) $r['page_id']] = (int) $r['c'];
}

$title  = 'Pages';
$active = 'pages';
include __DIR__ . '/layout.php';
?>

<div class="page-head">
  <h1>Landing pages</h1>
  <button class="btn" type="button" data-toggle="#new-page">+ New page</button>
</div>

<form class="card panel" id="new-page" method="post" hidden>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="create">
  <div class="grid-3">
    <label>Page name
      <input type="text" name="name" placeholder="Mumbai Store Launch" required>
    </label>
    <label>URL slug <span class="muted small">(optional)</span>
      <input type="text" name="slug" placeholder="mumbai-store">
    </label>
    <label>Template
      <select name="template">
        <?php foreach (templates() as $key => $t): ?>
          <option value="<?= e($key) ?>"><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <button class="btn" type="submit">Create page</button>
</form>

<?php if (!$pages): ?>
  <div class="card empty">No pages yet. Create your first one above.</div>
<?php else: ?>
<div class="table-wrap card">
  <table class="table">
    <thead>
      <tr>
        <th>Page</th><th>Public URL</th><th>Status</th><th class="num">Leads</th><th>Updated</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($pages as $p): ?>
      <tr>
        <td>
          <a class="strong" href="edit.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a>
          <div class="muted small"><?= e(templates()[$p['template']]['name'] ?? $p['template']) ?></div>
        </td>
        <td class="mono small">
          <a href="<?= e(page_url($p['slug'])) ?>" target="_blank" rel="noopener">/<?= e($p['slug']) ?> &nearr;</a>
        </td>
        <td>
          <span class="pill pill--<?= $p['status'] === 'published' ? 'live' : 'draft' ?>">
            <?= $p['status'] === 'published' ? 'Live' : 'Draft' ?>
          </span>
        </td>
        <td class="num">
          <?php $c = $counts[(int) $p['id']] ?? 0; ?>
          <?= $c ? '<a href="leads.php?page_id=' . (int) $p['id'] . '">' . $c . '</a>' : '<span class="muted">0</span>' ?>
        </td>
        <td class="muted small"><?= e(substr((string) $p['updated_at'], 0, 16)) ?></td>
        <td class="row-actions">
          <a class="btn btn--ghost btn--sm" href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn--ghost btn--sm" type="submit"><?= $p['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
          </form>
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clone">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn--ghost btn--sm" type="submit">Duplicate</button>
          </form>
          <?php if ($user['role'] === 'admin'): ?>
          <form method="post" class="inline" onsubmit="return confirm('Delete &quot;<?= e($p['name']) ?>&quot;? This cannot be undone.')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button class="btn btn--ghost btn--sm btn--danger" type="submit">Delete</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="muted small hint">
  Tip: build one page the way you want it, then <strong>Duplicate</strong> it for each new campaign
  and just swap the text and images.
</p>
<?php endif; ?>

<script>
document.querySelectorAll('[data-toggle]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var el = document.querySelector(btn.getAttribute('data-toggle'));
    if (el) { el.hidden = !el.hidden; if (!el.hidden) { var i = el.querySelector('input'); if (i) i.focus(); } }
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
