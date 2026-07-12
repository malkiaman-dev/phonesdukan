(function () {
  "use strict";

  var SESSION_SEEN_PREFIX = "pd_deal_seen_";

  function pad(n) {
    return n < 10 ? "0" + n : String(n);
  }

  function getScrollLockTargets() {
    var targets = [document.documentElement, document.body];
    var pageScroll = document.getElementById("pd-page-scroll");
    if (pageScroll) {
      targets.push(pageScroll);
    }
    return targets;
  }

  function lockScroll() {
    getScrollLockTargets().forEach(function (el) {
      el.dataset.pdDealOverflow = el.style.overflow || "";
      el.style.overflow = "hidden";
    });
  }

  function unlockScroll() {
    getScrollLockTargets().forEach(function (el) {
      if (Object.prototype.hasOwnProperty.call(el.dataset, "pdDealOverflow")) {
        el.style.overflow = el.dataset.pdDealOverflow;
        delete el.dataset.pdDealOverflow;
      }
    });
  }

  function sessionKey(dismissKey) {
    return SESSION_SEEN_PREFIX + String(dismissKey || "default");
  }

  // Once per browser tab/session: show on first landing, hide while browsing,
  // then show again only after the visitor fully leaves and returns.
  function wasSeenThisVisit(dismissKey) {
    try {
      return window.sessionStorage.getItem(sessionKey(dismissKey)) === "1";
    } catch (e) {
      return false;
    }
  }

  function markSeenThisVisit(dismissKey) {
    try {
      window.sessionStorage.setItem(sessionKey(dismissKey), "1");
    } catch (e) {
      // ignore storage failures
    }
  }

  function clearLegacyLocalDismiss(dismissKey) {
    try {
      window.localStorage.removeItem(String(dismissKey || "pd_deal_dismissed"));
    } catch (e) {
      // ignore
    }
  }

  function initTimer(root) {
    var timer = root.querySelector(".pd-deal-timer");
    if (!timer) {
      return null;
    }

    var endsRaw = timer.getAttribute("data-deal-ends") || "";
    var endsAt = Date.parse(endsRaw);
    if (!endsAt || Number.isNaN(endsAt)) {
      return null;
    }

    var daysEl = timer.querySelector("[data-deal-days]");
    var hoursEl = timer.querySelector("[data-deal-hours]");
    var minsEl = timer.querySelector("[data-deal-mins]");
    var secsEl = timer.querySelector("[data-deal-secs]");

    function tick() {
      var diff = Math.max(0, endsAt - Date.now());
      var totalSec = Math.floor(diff / 1000);
      var days = Math.floor(totalSec / 86400);
      var hours = Math.floor((totalSec % 86400) / 3600);
      var mins = Math.floor((totalSec % 3600) / 60);
      var secs = totalSec % 60;

      if (daysEl) daysEl.textContent = pad(days);
      if (hoursEl) hoursEl.textContent = pad(hours);
      if (minsEl) minsEl.textContent = pad(mins);
      if (secsEl) secsEl.textContent = pad(secs);

      if (diff <= 0) {
        closePopup(root);
        return false;
      }
      return true;
    }

    tick();
    return window.setInterval(function () {
      if (!tick()) {
        window.clearInterval(timer._dealInterval);
      }
    }, 1000);
  }

  function openPopup(root) {
    root.hidden = false;
    root.setAttribute("aria-hidden", "false");
    lockScroll();
    window.requestAnimationFrame(function () {
      root.classList.add("is-open");
    });
    root._dealInterval = initTimer(root);
  }

  function closePopup(root) {
    root.classList.remove("is-open");
    root.setAttribute("aria-hidden", "true");
    unlockScroll();
    if (root._dealInterval) {
      window.clearInterval(root._dealInterval);
      root._dealInterval = null;
    }
    window.setTimeout(function () {
      root.hidden = true;
    }, 280);
  }

  function fixCategoryOnlyCta(root) {
    var cta = root.querySelector(".pd-deal-cta");
    if (!cta) {
      return;
    }

    var href = String(cta.getAttribute("href") || "").trim();
    var path = href;
    try {
      if (/^https?:\/\//i.test(href)) {
        path = new URL(href, window.location.origin).pathname;
      }
    } catch (e) {
      path = href;
    }

    var segments = String(path || "")
      .split("/")
      .filter(function (part) {
        return part && part !== ".";
      });

    // Real product URLs have brand + category + product (3+ segments).
    if (segments.length >= 3) {
      return;
    }

    var img = root.querySelector(".pd-deal-media img");
    var imgSrc = img ? String(img.getAttribute("src") || "") : "";
    var titleEl = root.querySelector("#pd-deal-title, .pd-deal-title");
    var title = titleEl ? String(titleEl.textContent || "") : "";
    var looksLikeL210 =
      /l-210/i.test(imgSrc) ||
      /l-210/i.test(title) ||
      /premium wireless earbuds/i.test(title);

    if (!looksLikeL210) {
      return;
    }

    var base = String(window.__PD_BASE_PATH__ || window.PD_BASE_PATH || "").replace(/\/+$/, "");
    cta.setAttribute("href", base + "/login/wireless-earbuds/Login-L-210-Earbuds/");
  }

  function boot() {
    var root = document.getElementById("pd-deal-popup");
    if (!root) {
      return;
    }

    // Keep the modal on <body> so overflow/transform parents cannot clip it.
    if (root.parentElement !== document.body) {
      document.body.appendChild(root);
    }

    fixCategoryOnlyCta(root);

    var key = root.getAttribute("data-dismiss-key") || "pd_deal_dismissed";
    clearLegacyLocalDismiss(key);

    if (wasSeenThisVisit(key)) {
      root.remove();
      return;
    }

    // Mark as soon as we decide to show, so in-site navigation won't reopen it.
    markSeenThisVisit(key);

    root.querySelectorAll("[data-deal-close]").forEach(function (el) {
      el.addEventListener("click", function () {
        closePopup(root);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !root.hidden) {
        closePopup(root);
      }
    });

    window.setTimeout(function () {
      openPopup(root);
    }, 1400);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
