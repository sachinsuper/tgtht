/* Technogym landing page — hero slider, form UX, click tracking. ~2 KB, no deps. */
(function () {
  'use strict';

  /* ------------------------------------------------------------ slider -- */
  var slider = document.querySelector('[data-slider]');
  if (slider) {
    var slides = slider.querySelectorAll('.hero__slide');
    var dots   = slider.querySelectorAll('.hero__dot');
    var index  = 0;
    var timer  = null;
    var DELAY  = 5000;

    function go(i) {
      if (!slides.length) return;
      index = (i + slides.length) % slides.length;
      for (var s = 0; s < slides.length; s++) {
        slides[s].classList.toggle('is-active', s === index);
      }
      for (var d = 0; d < dots.length; d++) {
        dots[d].classList.toggle('is-active', d === index);
      }
    }

    function start() {
      if (slides.length < 2) return;
      stop();
      timer = setInterval(function () { go(index + 1); }, DELAY);
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    var prev = slider.querySelector('.hero__nav--prev');
    var next = slider.querySelector('.hero__nav--next');
    if (prev) prev.addEventListener('click', function () { go(index - 1); start(); });
    if (next) next.addEventListener('click', function () { go(index + 1); start(); });

    Array.prototype.forEach.call(dots, function (dot, i) {
      dot.addEventListener('click', function () { go(i); start(); });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);

    // Swipe
    var x0 = null;
    slider.addEventListener('touchstart', function (ev) {
      x0 = ev.changedTouches[0].clientX; stop();
    }, { passive: true });
    slider.addEventListener('touchend', function (ev) {
      if (x0 === null) return;
      var dx = ev.changedTouches[0].clientX - x0;
      if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1));
      x0 = null; start();
    }, { passive: true });

    document.addEventListener('visibilitychange', function () {
      document.hidden ? stop() : start();
    });

    start();
  }

  /* -------------------------------------------------------- call-back -- */
  var form = document.querySelector('.cb-form');
  if (form) {
    var input = form.querySelector('.cb-form__input');
    var btn   = form.querySelector('.cb-form__btn');

    if (input) {
      input.addEventListener('input', function () {
        this.value = this.value.replace(/[^\d\s-]/g, '').slice(0, 15);
      });
    }

    form.addEventListener('submit', function (ev) {
      var digits = (input ? input.value : '').replace(/\D/g, '');
      if (digits.length < 10) {
        ev.preventDefault();
        if (input) {
          input.focus();
          input.placeholder = 'Enter 10-digit number';
          form.querySelector('.cb-form__row').style.boxShadow = '0 0 0 2px #c62828';
          setTimeout(function () {
            form.querySelector('.cb-form__row').style.boxShadow = '';
          }, 1800);
        }
        return;
      }
      if (btn) { btn.disabled = true; btn.style.opacity = '.5'; }
    });
  }

  // Scroll to the form and focus it when arriving with #callback
  if (window.location.hash === '#callback') {
    var cb = document.getElementById('callback');
    if (cb) setTimeout(function () {
      cb.scrollIntoView({ behavior: 'smooth', block: 'center' });
      var i = cb.querySelector('.cb-form__input');
      if (i) i.focus({ preventScroll: true });
    }, 250);
  }

  /* --------------------------------------------------------- tracking -- */
  // Fires a GA4 / GTM / Meta Pixel event for every CTA, so ad campaigns can
  // optimise on real actions. Safe no-op if no tag is installed.
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-track]');
    if (!el) return;
    var label = el.getAttribute('data-track');
    try {
      if (window.dataLayer) window.dataLayer.push({ event: 'cta_click', cta: label });
      if (typeof window.gtag === 'function') window.gtag('event', 'cta_click', { cta: label });
      if (typeof window.fbq === 'function') window.fbq('trackCustom', 'CTAClick', { cta: label });
    } catch (e) { /* never break the page for analytics */ }
  }, true);
})();
