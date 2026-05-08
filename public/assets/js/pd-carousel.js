/**
 * pd-carousel.js — PhonesDukan reusable infinite carousel
 *
 * Usage:
 *  - Add data-carousel to any horizontal scroll container.
 *  - Optional:
 *      data-carousel-content="#selector" (inner element holding items)
 *      data-carousel-item=".item"        (item selector inside content)
 *      data-carousel-prev=".prev-btn"    (manual previous button)
 *      data-carousel-next=".next-btn"    (manual next button)
 */
(function () {
  'use strict';

  var AUTOPLAY_INTERVAL = 2800;
  var RESUME_DELAY = 0;

  function parseGap(element) {
    var styles = getComputedStyle(element);
    return parseInt(styles.columnGap || styles.gap || '0', 10) || 0;
  }

  function initCarousel(scroller) {
    if (scroller.dataset.carouselReady === '1') return;
    scroller.dataset.carouselReady = '1';

    var contentSelector = scroller.getAttribute('data-carousel-content');
    var content = contentSelector ? document.querySelector(contentSelector) : scroller;
    if (!content) return;

    var itemSelector = scroller.getAttribute('data-carousel-item');
    var itemQuery = itemSelector ? ':scope > ' + itemSelector : ':scope > *';
    var mode = scroller.getAttribute('data-carousel-mode') || 'step';
    var isContinuousMode = mode === 'continuous' || mode === 'marquee';
    var continuousSpeed = parseFloat(scroller.getAttribute('data-carousel-speed') || '34');
    var shouldClone = scroller.getAttribute('data-carousel-clone') !== 'false';
    var originals = Array.from(content.querySelectorAll(itemQuery)).filter(function (item) {
      return !item.hasAttribute('data-carousel-clone');
    });
    if (originals.length < 1) return;

    if (shouldClone) {
      originals.forEach(function (item) {
        var clone = item.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.setAttribute('tabindex', '-1');
        clone.setAttribute('data-carousel-clone', '1');
        content.appendChild(clone);
      });
    }

    var autoTimer = null;
    var autoRaf = null;
    var resumeTimer = null;
    var snapTimer = null;
    var isDraggingMouse = false;
    var isTouching = false;
    var isHorizontalTouch = false;
    var moved = false;
    var startX = 0;
    var startY = 0;
    var startLeft = 0;
    var resetting = false;
    var lastFrameTs = 0;

    function getItems() {
      return Array.from(content.querySelectorAll(itemQuery));
    }

    function getStepWidth() {
      var first = getItems()[0];
      if (!first) return 200;
      return first.offsetWidth + parseGap(content);
    }

    function getOriginalWidth() {
      return getStepWidth() * originals.length;
    }

    function normalizeLoopPosition() {
      if (!shouldClone) return;
      if (resetting) return;
      var origWidth = getOriginalWidth();
      if (!origWidth) return;

      if (scroller.scrollLeft >= origWidth) {
        resetting = true;
        scroller.style.scrollBehavior = 'auto';
        scroller.scrollLeft -= origWidth;
        requestAnimationFrame(function () {
          scroller.style.scrollBehavior = 'smooth';
          resetting = false;
        });
      }
    }

    function snapToNearest() {
      clearTimeout(snapTimer);
      snapTimer = setTimeout(function () {
        var step = getStepWidth();
        if (!step) return;
        var target = Math.round(scroller.scrollLeft / step) * step;
        scroller.scrollTo({ left: target, behavior: 'smooth' });
      }, 40);
    }

    function stopAutoplay() {
      if (autoTimer) {
        clearInterval(autoTimer);
        autoTimer = null;
      }
      if (autoRaf) {
        cancelAnimationFrame(autoRaf);
        autoRaf = null;
      }
      lastFrameTs = 0;
    }

    function advanceOneStep() {
      if (isDraggingMouse || isTouching || resetting) return;
      var step = getStepWidth();
      if (!step) return;

      if (!shouldClone) {
        var maxLeft = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
        var nextLeft = scroller.scrollLeft + step;
        if (nextLeft > maxLeft - 2) {
          scroller.scrollTo({ left: 0, behavior: 'smooth' });
          return;
        }
      }

      scroller.scrollTo({ left: scroller.scrollLeft + step, behavior: 'smooth' });
    }

    function startAutoplay() {
      stopAutoplay();
      if (isContinuousMode) {
        scroller.style.scrollSnapType = 'none';
        scroller.style.scrollBehavior = 'auto';
        var tick = function (ts) {
          if (isDraggingMouse || isTouching || resetting) {
            lastFrameTs = ts;
            autoRaf = requestAnimationFrame(tick);
            return;
          }

          if (!lastFrameTs) lastFrameTs = ts;
          var dt = Math.min(48, ts - lastFrameTs);
          lastFrameTs = ts;

          scroller.scrollLeft += (continuousSpeed * dt) / 1000;
          normalizeLoopPosition();
          autoRaf = requestAnimationFrame(tick);
        };
        autoRaf = requestAnimationFrame(tick);
        return;
      }

      autoTimer = setInterval(advanceOneStep, AUTOPLAY_INTERVAL);
    }

    function pauseThenResume() {
      scheduleResume(RESUME_DELAY);
    }

    function scheduleResume(delayMs) {
      stopAutoplay();
      clearTimeout(resumeTimer);
      if (!delayMs || delayMs <= 0) {
        startAutoplay();
        return;
      }
      resumeTimer = setTimeout(startAutoplay, delayMs);
    }

    scroller.addEventListener('scroll', normalizeLoopPosition, { passive: true });

    scroller.addEventListener('mousedown', function (e) {
      if (e.button !== 0) return;
      isDraggingMouse = true;
      moved = false;
      startX = e.clientX;
      startLeft = scroller.scrollLeft;
      scroller.classList.add('is-dragging');
      scroller.style.scrollSnapType = 'none';
      scroller.style.scrollBehavior = 'auto';
      pauseThenResume();
      e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
      if (!isDraggingMouse) return;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 4) moved = true;
      scroller.scrollLeft = startLeft - dx;
    });

    document.addEventListener('mouseup', function () {
      if (!isDraggingMouse) return;
      isDraggingMouse = false;
      scroller.classList.remove('is-dragging');
      scroller.style.scrollSnapType = '';
      scroller.style.scrollBehavior = 'smooth';
      if (!isContinuousMode) {
        snapToNearest();
      }
      pauseThenResume();
    });

    scroller.addEventListener('touchstart', function (e) {
      if (!e.touches || !e.touches.length) return;
      var touch = e.touches[0];
      isTouching = true;
      isHorizontalTouch = false;
      moved = false;
      startX = touch.clientX;
      startY = touch.clientY;
      startLeft = scroller.scrollLeft;
      scroller.style.scrollBehavior = 'auto';
      pauseThenResume();
    }, { passive: true });

    scroller.addEventListener('touchmove', function (e) {
      if (!isTouching || !e.touches || !e.touches.length) return;
      var touch = e.touches[0];
      var rawDx = touch.clientX - startX;
      var diffX = Math.abs(rawDx);
      var diffY = Math.abs(touch.clientY - startY);
      isHorizontalTouch = diffX > diffY;
      if (isHorizontalTouch && diffX > 4) {
        moved = true;
        scroller.scrollLeft = startLeft - rawDx;
        normalizeLoopPosition();
        if (e.cancelable) e.preventDefault();
      }
    }, { passive: false });

    scroller.addEventListener('touchend', function () {
      if (!isTouching) return;
      isTouching = false;
      scroller.style.scrollBehavior = 'smooth';
      if (!isContinuousMode) {
        snapToNearest();
      }
      pauseThenResume();
    }, { passive: true });

    scroller.addEventListener('touchcancel', function () {
      isTouching = false;
      scroller.style.scrollBehavior = 'smooth';
      pauseThenResume();
    }, { passive: true });

    scroller.addEventListener('click', function (e) {
      if (moved) {
        e.preventDefault();
        e.stopImmediatePropagation();
      }
      moved = false;
    }, true);

    var prevSelector = scroller.getAttribute('data-carousel-prev');
    var nextSelector = scroller.getAttribute('data-carousel-next');
    if (prevSelector) {
      var prevBtn = document.querySelector(prevSelector);
      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          if (shouldClone) {
            var originalWidth = getOriginalWidth();
            if (originalWidth && scroller.scrollLeft <= 1) {
              scroller.style.scrollBehavior = 'auto';
              scroller.scrollLeft += originalWidth;
              requestAnimationFrame(function () {
                scroller.style.scrollBehavior = 'smooth';
              });
            }
          }
          scroller.scrollTo({ left: scroller.scrollLeft - getStepWidth(), behavior: 'smooth' });
          if (isContinuousMode) {
            setTimeout(function () {
              scroller.style.scrollBehavior = 'auto';
            }, 450);
            scheduleResume(450);
            return;
          }
          scheduleResume(0);
        });
      }
    }
    if (nextSelector) {
      var nextBtn = document.querySelector(nextSelector);
      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          scroller.scrollTo({ left: scroller.scrollLeft + getStepWidth(), behavior: 'smooth' });
          if (isContinuousMode) {
            setTimeout(function () {
              scroller.style.scrollBehavior = 'auto';
            }, 450);
            scheduleResume(450);
            return;
          }
          scheduleResume(0);
        });
      }
    }

    var hoverTarget = scroller.closest('.cat-track-wrap') || scroller;
    hoverTarget.addEventListener('mouseenter', stopAutoplay);
    hoverTarget.addEventListener('mouseleave', pauseThenResume);
    scroller.addEventListener('wheel', pauseThenResume, { passive: true });

    scroller.style.scrollBehavior = 'smooth';
    if (isContinuousMode) {
      scroller.style.scrollSnapType = 'none';
      scroller.style.scrollBehavior = 'auto';
    }
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
