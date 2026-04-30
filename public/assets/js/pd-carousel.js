/**
 * pd-carousel.js — PhonesDukan reusable infinite carousel
 *
 * Usage: add data-carousel to any scroll container.
 * Cards are cloned for seamless infinite loop.
 * Autoplay, drag, touch-pause all handled here.
 */
(function () {
  'use strict';

  var AUTOPLAY_INTERVAL = 3500;   // ms between slides
  var RESUME_DELAY      = 5000;   // ms after user interaction before resuming

  function initCarousel(track) {
    /* ── 1. Clone original items for infinite loop ── */
    var originals = Array.from(track.children);
    if (originals.length === 0) return;

    originals.forEach(function (item) {
      var clone = item.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      track.appendChild(clone);
    });

    /* ── 2. Step width = first card's outer width + gap ── */
    function getStepWidth() {
      var first = track.querySelector(':scope > *');
      if (!first) return 200;
      var gap = parseInt(getComputedStyle(track).columnGap) || 20;
      return first.offsetWidth + gap;
    }

    /* ── 3. Seamless reset when scrolled into clone zone ── */
    var resetting = false;
    track.addEventListener('scroll', function () {
      if (resetting) return;
      var origWidth = getStepWidth() * originals.length;
      if (track.scrollLeft >= origWidth) {
        resetting = true;
        track.style.scrollBehavior = 'auto';
        track.scrollLeft -= origWidth;
        requestAnimationFrame(function () {
          track.style.scrollBehavior = '';
          resetting = false;
        });
      }
    }, { passive: true });

    /* ── 4. Autoplay ── */
    var autoTimer  = null;
    var pauseTimer = null;

    function advance() {
      track.scrollLeft += getStepWidth();
    }

    function startAutoplay() {
      clearInterval(autoTimer);
      autoTimer = setInterval(advance, AUTOPLAY_INTERVAL);
    }

    function stopAutoplay() {
      clearInterval(autoTimer);
      autoTimer = null;
    }

    function pauseThenResume() {
      stopAutoplay();
      clearTimeout(pauseTimer);
      pauseTimer = setTimeout(startAutoplay, RESUME_DELAY);
    }

    /* ── 5. Mouse drag ── */
    var dragging  = false;
    var startX    = 0;
    var startLeft = 0;
    var moved     = false;

    track.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      dragging  = true;
      moved     = false;
      startX    = e.clientX;
      startLeft = track.scrollLeft;
      track.classList.add('is-dragging');
      track.style.scrollSnapType = 'none'; /* disable snap mid-drag */
      pauseThenResume();
      e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
      if (!dragging) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 4) moved = true;
      track.scrollLeft = startLeft - dx;
    });

    document.addEventListener('mouseup', function () {
      if (!dragging) return;
      dragging = false;
      track.classList.remove('is-dragging');
      track.style.scrollSnapType = ''; /* restore CSS snap so release snaps cleanly */
    });

    /* Prevent link/button clicks from firing after a drag */
    track.addEventListener('click', function (e) {
      if (moved) {
        e.preventDefault();
        e.stopImmediatePropagation();
        moved = false;
      }
    }, true);

    /* ── 6. Touch: CSS handles swipe; just pause autoplay ── */
    track.addEventListener('touchstart', pauseThenResume, { passive: true });

    /* ── 7. Start ── */
    startAutoplay();
  }

  /* Bootstrap all carousels on the page */
  function init() {
    var tracks = document.querySelectorAll('[data-carousel]');
    tracks.forEach(initCarousel);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* ── Collapse unfilled AdSense slots so they leave no blank space ──
     AdSense injects inline "height:auto!important; max-height:none!important"
     which defeats CSS. We watch for data-ad-status="unfilled" and override
     the container height via JS inline styles (which win over AdSense's own). */
  function collapseUnfilledAds() {
    document.querySelectorAll('.adsbygoogle').forEach(function (ins) {
      function tryCollapse() {
        if (ins.dataset.adStatus === 'unfilled') {
          var container = ins.closest ? ins.closest('.ad-container') : (function () {
            var n = ins.parentNode;
            while (n && !(n.classList && n.classList.contains('ad-container'))) n = n.parentNode;
            return n;
          })();
          if (container) {
            container.style.setProperty('height',     '0',        'important');
            container.style.setProperty('min-height', '0',        'important');
            container.style.setProperty('max-height', '0',        'important');
            container.style.setProperty('overflow',   'hidden',   'important');
            container.style.setProperty('padding',    '0',        'important');
            container.style.setProperty('margin',     '0',        'important');
          }
          /* also collapse the ins itself */
          ins.style.setProperty('height',     '0',      'important');
          ins.style.setProperty('max-height', '0',      'important');
          ins.style.setProperty('overflow',   'hidden', 'important');
        }
      }

      /* Check immediately (in case AdSense already ran) */
      tryCollapse();

      /* Watch for when AdSense sets the status attribute */
      if ('MutationObserver' in window) {
        new MutationObserver(function () { tryCollapse(); })
          .observe(ins, { attributes: true, attributeFilter: ['data-ad-status'] });
      }
    });
  }

  /* Run after load so AdSense has had a chance to execute */
  window.addEventListener('load', collapseUnfilledAds);
  /* Fallback: also check after 4 s for slow connections */
  window.addEventListener('load', function () { setTimeout(collapseUnfilledAds, 4000); });
})();
