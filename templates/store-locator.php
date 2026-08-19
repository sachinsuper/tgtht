<?php
/** @var array $page @var array $B @var callable $b @var callable $on @var callable $img */

$stores = [];
for ($i = 1; $i <= 4; $i++) {
    if ($on("store{$i}_on") && $b("store{$i}_city") !== '') {
        $stores[] = [
            'image'   => $img("store{$i}_image"),
            'city'    => $b("store{$i}_city"),
            'address' => $b("store{$i}_address"),
            'cta'     => $b("store{$i}_cta", 'VISIT THE STORE'),
            'url'     => $b("store{$i}_url"),
        ];
    }
}

$slides = [];
for ($i = 1; $i <= 3; $i++) {
    if ($on("slide{$i}_on") && $b("slide{$i}_image") !== '') {
        $slides[] = [
            'image'   => $img("slide{$i}_image"),
            'caption' => $b("slide{$i}_caption"),
            'link'    => $b("slide{$i}_link"),
        ];
    }
}

$waHref = $b('wa_number') !== ''
    ? 'https://wa.me/' . preg_replace('/\D+/', '', $b('wa_number'))
      . ($b('wa_message') !== '' ? '?text=' . rawurlencode($b('wa_message')) : '')
    : '';

$metaTitle = $page['meta_title'] ?: $page['name'];
$ogImage   = $page['og_image'] ? asset_url($page['og_image']) : ($slides[0]['image'] ?? '');
$submitted = isset($_GET['sent']) && $_GET['sent'] === '1';
$formError = match ($_GET['err'] ?? '') {
    'phone'   => 'Please enter a valid 10-digit mobile number.',
    'expired' => 'That form had been open too long. Please try once more.',
    default   => '',
};
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($metaTitle) ?></title>
<?php if ($page['meta_description']): ?>
<meta name="description" content="<?= e($page['meta_description']) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e($metaTitle) ?>">
<meta property="og:description" content="<?= e($page['meta_description']) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(page_url($page['slug'])) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="<?= e(page_url($page['slug'])) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/site.css')) ?>">
<style>
  :root{
    --accent: <?= e($b('accent_color', '#FFD100')) ?>;
    --dark:   <?= e($b('dark_color',   '#1A1A1A')) ?>;
    --cream:  <?= e($b('cream_color',  '#F5F2EC')) ?>;
  }
</style>
<?= $page['head_code'] ?? '' ?>
</head>
<body>
<?= $page['body_code'] ?? '' ?>

<!-- ============================== HEADER ============================== -->
<header class="site-header">
  <div class="wrap header-inner">
    <?php if ($b('logo_image')): ?>
      <a class="logo" href="<?= e($b('web_url') ?: '#') ?>">
        <img src="<?= e($img('logo_image')) ?>" alt="<?= e($page['name']) ?>" width="150" height="38">
      </a>
    <?php endif; ?>

    <?php if ($b('header_phone')): ?>
      <a class="header-phone" href="tel:<?= e($b('header_tel')) ?>" data-track="header-call">
        <span class="header-phone__label"><?= e($b('header_label')) ?></span>
        <span class="header-phone__num"><?= e($b('header_phone')) ?></span>
      </a>
    <?php endif; ?>
  </div>
</header>

<!-- =============================== HERO =============================== -->
<?php if ($slides): ?>
<section class="hero" data-slider>
  <div class="hero__track">
    <?php foreach ($slides as $i => $s): ?>
      <?php $tag = $s['link'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="hero__slide<?= $i === 0 ? ' is-active' : '' ?>"
        <?= $s['link'] ? 'href="' . e($s['link']) . '"' : '' ?>>
        <img src="<?= e($s['image']) ?>" alt="<?= e($s['caption']) ?>"
             <?= $i === 0 ? '' : 'loading="lazy"' ?>>
        <?php if ($s['caption']): ?>
          <span class="hero__caption"><?= e($s['caption']) ?></span>
        <?php endif; ?>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>

  <?php if (count($slides) > 1): ?>
    <button class="hero__nav hero__nav--prev" type="button" aria-label="Previous slide">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <button class="hero__nav hero__nav--next" type="button" aria-label="Next slide">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="hero__dots" role="tablist">
      <?php foreach ($slides as $i => $s): ?>
        <button type="button" class="hero__dot<?= $i === 0 ? ' is-active' : '' ?>"
                aria-label="Go to slide <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- ============================== STORES ============================== -->
<?php if ($stores): ?>
<section class="stores">
  <div class="wrap">
    <h2 class="section-title"><span class="dot"></span><?= e($b('stores_title')) ?></h2>

    <div class="store-grid">
      <?php foreach ($stores as $s): ?>
        <article class="store-card">
          <?php if ($s['image']): ?>
            <div class="store-card__media">
              <img src="<?= e($s['image']) ?>" alt="Technogym store, <?= e($s['city']) ?>" loading="lazy">
            </div>
          <?php endif; ?>
          <div class="store-card__body">
            <h3 class="store-card__city"><span class="pin"></span><?= e($s['city']) ?></h3>
            <?php if ($s['address']): ?>
              <p class="store-card__address"><?= enl($s['address']) ?></p>
            <?php endif; ?>
            <?php if ($s['url']): ?>
              <a class="store-card__cta" href="<?= e($s['url']) ?>" target="_blank" rel="noopener"
                 data-track="store-<?= e(strtolower($s['city'])) ?>">
                <?= e($s['cta']) ?> <span aria-hidden="true">&rarr;</span>
              </a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================= CONNECT ============================== -->
<section class="connect" id="connect">
  <div class="wrap">
    <h2 class="connect__title"><?= e($b('connect_title')) ?></h2>

    <div class="tiles">

      <?php if ($on('call_on')): ?>
      <a class="tile" href="tel:<?= e($b('call_tel')) ?>" data-track="tile-call">
        <span class="tile__icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 11.4 11.4 0 00.57 3.6 1 1 0 01-.25 1z" fill="currentColor"/></svg>
        </span>
        <span class="tile__text">
          <span class="tile__title"><?= e($b('call_title')) ?></span>
          <span class="tile__sub tile__sub--big"><?= e($b('call_number')) ?></span>
        </span>
        <span class="tile__arrow" aria-hidden="true">&rarr;</span>
      </a>
      <?php endif; ?>

      <?php if ($on('wa_on') && $waHref): ?>
      <a class="tile" href="<?= e($waHref) ?>" target="_blank" rel="noopener" data-track="tile-whatsapp">
        <span class="tile__icon tile__icon--wa">
          <svg viewBox="0 0 32 32" aria-hidden="true"><path fill="#25D366" d="M16 0a16 16 0 00-13.8 24L0 32l8.3-2.2A16 16 0 1016 0z"/><path fill="#fff" d="M23.3 19.3c-.4-.2-2.3-1.1-2.6-1.2s-.6-.2-.9.2-1 1.2-1.2 1.5-.5.3-.8.1a10.4 10.4 0 01-3.1-1.9 11.6 11.6 0 01-2.1-2.7c-.2-.4 0-.6.2-.8l.6-.7a2.6 2.6 0 00.4-.6.7.7 0 000-.7c0-.2-.9-2.1-1.2-2.9s-.6-.6-.9-.6h-.7a1.4 1.4 0 00-1 .5 4.2 4.2 0 00-1.3 3.1A7.3 7.3 0 0010.2 17c.2.2 2.6 4 6.3 5.6a20.9 20.9 0 002.1.8 5 5 0 002.3.1 3.8 3.8 0 002.5-1.7 3.1 3.1 0 00.2-1.7c-.1-.2-.4-.3-.8-.5z"/></svg>
        </span>
        <span class="tile__text">
          <span class="tile__title"><?= e($b('wa_title')) ?></span>
          <span class="tile__sub"><?= e($b('wa_sub')) ?></span>
        </span>
        <span class="tile__arrow" aria-hidden="true">&rarr;</span>
      </a>
      <?php endif; ?>

      <?php if ($on('cb_on')): ?>
      <div class="tile tile--form<?= $submitted ? ' is-done' : '' ?>" id="callback">
        <span class="tile__icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5V2L8 6l4 4V7a5 5 0 11-5 5H5a7 7 0 107-7z" fill="currentColor"/></svg>
        </span>
        <div class="tile__text tile__text--form">
          <span class="tile__title"><?= e($b('cb_title')) ?></span>

          <form class="cb-form" method="post" action="<?= e(base_url()) ?>/submit.php" novalidate>
            <input type="hidden" name="_token" value="<?= e(public_token((int) $page['id'])) ?>">
            <input type="hidden" name="page_id" value="<?= (int) $page['id'] ?>">
            <input type="hidden" name="utm_source"   value="<?= e($_GET['utm_source']   ?? '') ?>">
            <input type="hidden" name="utm_medium"   value="<?= e($_GET['utm_medium']   ?? '') ?>">
            <input type="hidden" name="utm_campaign" value="<?= e($_GET['utm_campaign'] ?? '') ?>">
            <input type="hidden" name="return" value="<?= e(page_url($page['slug'])) ?>">
            <!-- honeypot: bots fill this, humans never see it -->
            <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

            <div class="cb-form__row">
              <span class="cb-form__cc">+91</span>
              <input class="cb-form__input" type="tel" name="phone" inputmode="numeric"
                     pattern="[0-9\s\-]{10,15}" maxlength="15" required
                     placeholder="<?= e($b('cb_placeholder')) ?>" aria-label="Phone number">
            </div>
            <button class="cb-form__btn" type="submit" data-track="tile-callback">
              <span aria-hidden="true">&rarr;</span><span class="sr-only">Submit</span>
            </button>
          </form>

          <p class="cb-form__thanks"<?= $submitted ? '' : ' hidden' ?>><?= e($b('cb_thanks')) ?></p>
          <?php if ($formError): ?>
            <p class="cb-form__error"><?= e($formError) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($on('web_on')): ?>
      <a class="tile tile--web" href="<?= e($b('web_url')) ?>" target="_blank" rel="noopener" data-track="tile-website">
        <?php if ($b('web_logo')): ?>
          <img class="tile__logo" src="<?= e($img('web_logo')) ?>" alt="" loading="lazy">
        <?php endif; ?>
        <span class="tile__title tile__title--center"><?= e($b('web_title')) ?></span>
      </a>
      <?php endif; ?>

    </div>
  </div>
</section>

<!-- ============================== FOOTER ============================== -->
<footer class="site-footer">
  <div class="wrap footer-inner">
    <?php if ($b('footer_logo')): ?>
      <img class="footer-logo" src="<?= e($img('footer_logo')) ?>" alt="" width="130" height="33" loading="lazy">
    <?php endif; ?>
    <span class="footer-text"><?= e($b('footer_text')) ?></span>
    <span class="footer-phone"><?= e($b('footer_phone')) ?></span>
  </div>
</footer>

<script src="<?= e(asset_url('assets/js/site.js')) ?>" defer></script>

<?php if ($submitted): ?>
<!-- Meta Pixel standard "Lead" event -- fires only once, on the page load
     right after the server confirms the call-back form was received
     (?sent=1). A click on Submit does not reach here on its own; an
     invalid number or an expired token redirects with ?err instead and
     never sets $submitted, so those never count as a lead. -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    try {
      if (typeof window.fbq === 'function') window.fbq('track', 'Lead', { cta: 'tile-callback' });
    } catch (e) { /* never break the page for analytics */ }
  });
</script>
<?php endif; ?>

<?php if ($on('wa_on')): ?>
<!-- Chat360 WhatsApp redirect widget -- only loaded on pages where the
     WhatsApp tile is switched on. -->
<script>
    (function (widgetHash) {
      var s = document.createElement("script");
      s.async = true;
      s.src =
        "https://app.chat360.io/widget/chatbox/common_scripts/whatsapp_redirect/script.js";

      s.onload = function () {
        if (window.loadChat360Widget) {
          window.loadChat360Widget(widgetHash);
        }
      };

      document.body.appendChild(s);
    })("e5b2b069-d1f2-4aec-8df3-04d768bef6bb");
</script>
<?php endif; ?>
</body>
</html>
