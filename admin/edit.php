<?php
require __DIR__ . '/auth.php';
$user = require_login();

$page = get_page((int) ($_GET['id'] ?? 0));
if (!$page) {
    flash('That page no longer exists.', 'err');
    redirect('index.php');
}

sync_page_blocks($page);           // pick up any newly added schema fields
$tpl    = templates()[$page['template']] ?? null;
$fields = template_fields($page['template']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // ------------------------------------------------------- page record --
    $name = trim((string) ($_POST['name'] ?? '')) ?: $page['name'];
    $slug = unique_slug(trim((string) ($_POST['slug'] ?? '')) ?: $name, (int) $page['id']);

    $ogImage = $page['og_image'];
    $upErr   = null;
    if ($newOg = handle_upload('file_og_image', $upErr)) {
        $ogImage = $newOg;
    } elseif ($upErr) {
        $errors[] = 'Social share image: ' . $upErr;
    }
    if (!empty($_POST['clear_og_image'])) {
        $ogImage = '';
    }

    q('UPDATE pages SET slug=?, name=?, status=?, meta_title=?, meta_description=?, og_image=?,
                        head_code=?, body_code=?, notify_emails=?, updated_at=?
       WHERE id=?', [
        $slug,
        $name,
        ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft',
        trim((string) ($_POST['meta_title'] ?? '')),
        trim((string) ($_POST['meta_description'] ?? '')),
        $ogImage,
        (string) ($_POST['head_code'] ?? ''),
        (string) ($_POST['body_code'] ?? ''),
        trim((string) ($_POST['notify_emails'] ?? '')),
        now(),
        $page['id'],
    ]);

    // ----------------------------------------------------------- content --
    $posted = $_POST['block'] ?? [];

    foreach ($fields as $key => $def) {
        $type = $def['type'];

        if ($type === 'toggle') {
            save_block((int) $page['id'], $key, isset($posted[$key]) ? '1' : '0');
            continue;
        }

        if ($type === 'image') {
            $current = (string) ($posted[$key] ?? '');
            $err     = null;
            if ($new = handle_upload('file_' . $key, $err)) {
                $current = $new;
            } elseif ($err) {
                $errors[] = ($def['label'] ?? $key) . ': ' . $err;
            }
            if (!empty($_POST['clear_' . $key])) {
                $current = '';
            }
            save_block((int) $page['id'], $key, $current);
            continue;
        }

        save_block((int) $page['id'], $key, (string) ($posted[$key] ?? ''));
    }

    if ($errors) {
        flash(implode(' ', $errors), 'err');
    } else {
        flash('Changes saved and live on the page.');
    }
    redirect('edit.php?id=' . $page['id'] . (isset($_POST['stay']) ? '#saved' : ''));
}

$B      = page_blocks($page);
$title  = 'Edit: ' . $page['name'];
$active = 'pages';
include __DIR__ . '/layout.php';
?>

<div class="page-head">
  <div>
    <a class="back" href="index.php">&larr; All pages</a>
    <h1><?= e($page['name']) ?></h1>
  </div>
  <div class="page-head__actions">
    <a class="btn btn--ghost" href="<?= e(page_url($page['slug'])) ?>" target="_blank" rel="noopener">Preview &nearr;</a>
    <button class="btn" type="submit" form="editor">Save changes</button>
  </div>
</div>

<form id="editor" method="post" enctype="multipart/form-data" class="editor">
  <?= csrf_field() ?>

  <nav class="tabs" role="tablist">
    <button class="tab is-active" type="button" data-tab="tab-content">Content</button>
    <button class="tab" type="button" data-tab="tab-seo">SEO &amp; tracking</button>
    <button class="tab" type="button" data-tab="tab-settings">Page settings</button>
  </nav>

  <!-- ============================ CONTENT ============================= -->
  <section id="tab-content" class="tab-panel is-active">
    <?php foreach (($tpl['groups'] ?? []) as $group => $groupFields): ?>
      <details class="card group" open>
        <summary class="group__head"><?= e($group) ?></summary>
        <div class="group__body">
          <?php foreach ($groupFields as $f):
              $key = $f['key'];
              $val = (string) ($B[$key] ?? '');
              $id  = 'f_' . $key;
          ?>
            <?php if ($f['type'] === 'toggle'): ?>
              <label class="switch">
                <input type="checkbox" name="block[<?= e($key) ?>]" value="1" <?= $val === '1' ? 'checked' : '' ?>>
                <span class="switch__ui"></span>
                <span class="switch__label"><?= e($f['label']) ?></span>
              </label>

            <?php elseif ($f['type'] === 'image'): ?>
              <div class="field field--image">
                <span class="field__label"><?= e($f['label']) ?></span>
                <div class="imgfield">
                  <div class="imgfield__preview">
                    <?php if ($val): ?>
                      <img src="<?= e(asset_url($val)) ?>" alt="">
                    <?php else: ?>
                      <span class="imgfield__empty">No image</span>
                    <?php endif; ?>
                  </div>
                  <div class="imgfield__controls">
                    <input type="file" id="<?= e($id) ?>" name="file_<?= e($key) ?>" accept="image/*">
                    <input type="text" name="block[<?= e($key) ?>]" value="<?= e($val) ?>"
                           class="mono small" placeholder="or paste an image URL">
                    <?php if ($val): ?>
                      <label class="check small">
                        <input type="checkbox" name="clear_<?= e($key) ?>" value="1"> Remove this image
                      </label>
                    <?php endif; ?>
                    <?php if (!empty($f['help'])): ?>
                      <span class="help"><?= e($f['help']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            <?php elseif ($f['type'] === 'textarea'): ?>
              <label class="field">
                <span class="field__label"><?= e($f['label']) ?></span>
                <textarea name="block[<?= e($key) ?>]" rows="3"><?= e($val) ?></textarea>
                <?php if (!empty($f['help'])): ?><span class="help"><?= e($f['help']) ?></span><?php endif; ?>
              </label>

            <?php elseif ($f['type'] === 'color'): ?>
              <label class="field field--color">
                <span class="field__label"><?= e($f['label']) ?></span>
                <span class="colorwrap">
                  <input type="color" value="<?= e($val ?: '#000000') ?>" data-color-for="<?= e($id) ?>">
                  <input type="text" id="<?= e($id) ?>" name="block[<?= e($key) ?>]" value="<?= e($val) ?>" class="mono">
                </span>
              </label>

            <?php else: ?>
              <label class="field">
                <span class="field__label"><?= e($f['label']) ?></span>
                <input type="<?= $f['type'] === 'url' ? 'url' : ($f['type'] === 'tel' ? 'tel' : 'text') ?>"
                       name="block[<?= e($key) ?>]" value="<?= e($val) ?>"
                       <?= $f['type'] === 'url' ? 'placeholder="https://"' : '' ?>>
                <?php if (!empty($f['help'])): ?><span class="help"><?= e($f['help']) ?></span><?php endif; ?>
              </label>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </section>

  <!-- ============================== SEO =============================== -->
  <section id="tab-seo" class="tab-panel">
    <div class="card group">
      <div class="group__head">Search &amp; social</div>
      <div class="group__body">
        <label class="field">
          <span class="field__label">Browser / Google title</span>
          <input type="text" name="meta_title" value="<?= e($page['meta_title']) ?>" maxlength="70">
          <span class="help">Around 60 characters shows in full on Google.</span>
        </label>
        <label class="field">
          <span class="field__label">Meta description</span>
          <textarea name="meta_description" rows="3" maxlength="180"><?= e($page['meta_description']) ?></textarea>
          <span class="help">Around 155 characters. This is the grey text under the Google result.</span>
        </label>
        <div class="field field--image">
          <span class="field__label">Social share image</span>
          <div class="imgfield">
            <div class="imgfield__preview">
              <?php if ($page['og_image']): ?>
                <img src="<?= e(asset_url($page['og_image'])) ?>" alt="">
              <?php else: ?>
                <span class="imgfield__empty">Falls back to slide 1</span>
              <?php endif; ?>
            </div>
            <div class="imgfield__controls">
              <input type="file" name="file_og_image" accept="image/*">
              <?php if ($page['og_image']): ?>
                <label class="check small"><input type="checkbox" name="clear_og_image" value="1"> Remove</label>
              <?php endif; ?>
              <span class="help">1200 x 630 px works best for WhatsApp, Facebook and LinkedIn.</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card group">
      <div class="group__head">Tracking codes</div>
      <div class="group__body">
        <label class="field">
          <span class="field__label">Code in &lt;head&gt;</span>
          <textarea name="head_code" rows="7" class="mono small" spellcheck="false"
                    placeholder="&lt;!-- Google Tag Manager, GA4, Meta Pixel base code, Google Ads tag --&gt;"><?= e($page['head_code']) ?></textarea>
          <span class="help">Paste the snippet exactly as your ads platform gives it, including the &lt;script&gt; tags.</span>
        </label>
        <label class="field">
          <span class="field__label">Code right after &lt;body&gt;</span>
          <textarea name="body_code" rows="5" class="mono small" spellcheck="false"
                    placeholder="&lt;!-- GTM noscript iframe --&gt;"><?= e($page['body_code']) ?></textarea>
        </label>
        <div class="note">
          <strong>Already wired for you:</strong> every CTA on the page fires a <code>cta_click</code>
          event to <code>dataLayer</code>, <code>gtag</code> and <code>fbq</code> with a label
          (<code>tile-call</code>, <code>tile-whatsapp</code>, <code>tile-callback</code>,
          <code>store-bengaluru</code>&hellip;). Build your conversions on that event —
          no extra code needed.
        </div>
      </div>
    </div>
  </section>

  <!-- ============================ SETTINGS ============================ -->
  <section id="tab-settings" class="tab-panel">
    <div class="card group">
      <div class="group__head">Page settings</div>
      <div class="group__body">
        <div class="grid-2">
          <label class="field">
            <span class="field__label">Page name <span class="muted small">(admin only)</span></span>
            <input type="text" name="name" value="<?= e($page['name']) ?>" required>
          </label>
          <label class="field">
            <span class="field__label">URL slug</span>
            <input type="text" name="slug" value="<?= e($page['slug']) ?>" class="mono" required>
            <span class="help"><?= e(base_url()) ?>/<strong><?= e($page['slug']) ?></strong></span>
          </label>
        </div>

        <label class="field">
          <span class="field__label">Status</span>
          <select name="status">
            <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published — visible to everyone</option>
            <option value="draft"     <?= $page['status'] !== 'published' ? 'selected' : '' ?>>Draft — only you can see it</option>
          </select>
        </label>

        <label class="field">
          <span class="field__label">Email leads from this page to</span>
          <input type="text" name="notify_emails" value="<?= e($page['notify_emails']) ?>"
                 placeholder="sales@example.com, manager@example.com">
          <span class="help">Comma separated. These are added to the global list in Settings.</span>
        </label>
      </div>
    </div>
  </section>

  <div class="save-bar">
    <button class="btn" type="submit" name="stay" value="1">Save changes</button>
    <a class="btn btn--ghost" href="<?= e(page_url($page['slug'])) ?>" target="_blank" rel="noopener">Preview &nearr;</a>
  </div>
</form>

<script>
// tabs
document.querySelectorAll('.tab').forEach(function (t) {
  t.addEventListener('click', function () {
    document.querySelectorAll('.tab').forEach(function (x) { x.classList.remove('is-active'); });
    document.querySelectorAll('.tab-panel').forEach(function (x) { x.classList.remove('is-active'); });
    t.classList.add('is-active');
    var p = document.getElementById(t.dataset.tab);
    if (p) p.classList.add('is-active');
  });
});

// colour swatch <-> hex text
document.querySelectorAll('[data-color-for]').forEach(function (sw) {
  var txt = document.getElementById(sw.dataset.colorFor);
  if (!txt) return;
  sw.addEventListener('input', function () { txt.value = sw.value.toUpperCase(); });
  txt.addEventListener('input', function () {
    if (/^#[0-9a-f]{6}$/i.test(txt.value)) sw.value = txt.value;
  });
});

// live image preview on file pick
document.querySelectorAll('.imgfield input[type=file]').forEach(function (inp) {
  inp.addEventListener('change', function () {
    var f = inp.files && inp.files[0];
    if (!f) return;
    var box = inp.closest('.imgfield').querySelector('.imgfield__preview');
    var url = URL.createObjectURL(f);
    box.innerHTML = '<img alt="">';
    box.querySelector('img').src = url;
  });
});

// warn before leaving with unsaved edits
(function () {
  var form = document.getElementById('editor');
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) {
    if (dirty) { e.preventDefault(); e.returnValue = ''; }
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
