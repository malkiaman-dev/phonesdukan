(function () {
  "use strict";

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

  function wasDismissed(key) {
    try {
      return window.localStorage.getItem(key) === "1";
    } catch (e) {
      return false;
    }
  }

  function markDismissed(key) {
    try {
      window.localStorage.setItem(key, "1");
    } catch (e) {
      // ignore storage failures
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
        closePopup(root, true);
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

  function closePopup(root, persist) {
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
    if (persist) {
      markDismissed(root.getAttribute("data-dismiss-key") || "pd_deal_dismissed");
    }
  }

  function boot() {
    var root = document.getElementById("pd-deal-popup");
    if (!root) {
      return;
    }

    var key = root.getAttribute("data-dismiss-key") || "pd_deal_dismissed";
    if (wasDismissed(key)) {
      root.remove();
      return;
    }

    root.querySelectorAll("[data-deal-close]").forEach(function (el) {
      el.addEventListener("click", function () {
        closePopup(root, true);
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && !root.hidden) {
        closePopup(root, true);
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
