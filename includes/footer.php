</main><!-- /.content -->

<footer id="footer">
    <div class="footer-widgets">
        <div class="footer-widget footer-brand">
            <a href="<?= url(); ?>" class="footer-brand-logo" aria-label="Visit Phones Dukan Home">
                <img src="<?= url('public/assets/images/phonesdukan_logo.webp'); ?>" alt="Phones Dukan Logo" loading="lazy" decoding="async">
            </a>
            <p class="footer-brand-about">PhonesDukan is your trusted store for mobiles, smart watches, earbuds, accessories, power banks, and speakers across Pakistan.</p>
            <div class="footer-social-icons">
                <a href="https://www.facebook.com/mobileisland01/" aria-label="Visit our Facebook page" class="footer-social-icon facebook-icon">
                    <img src="<?= url('public/assets/images/facebook_icon.svg'); ?>" alt="Facebook" loading="lazy" decoding="async">
                </a>
                <a href="https://www.youtube.com/@mobileisland" aria-label="Visit our YouTube channel" class="footer-social-icon youtube-icon">
                    <img src="<?= url('public/assets/images/youtube_icon.svg'); ?>" alt="YouTube" loading="lazy" decoding="async">
                </a>
                <a href="https://www.instagram.com/mobile_island01/" aria-label="Visit our Instagram page" class="footer-social-icon instagram-icon">
                    <img src="<?= url('public/assets/images/instagram_icon.svg'); ?>" alt="Instagram" loading="lazy" decoding="async">
                </a>
                <a href="https://www.tiktok.com/@mobile_island_g11_isb" aria-label="Visit our TikTok page" class="footer-social-icon tiktok-icon">
                    <img src="<?= url('public/assets/images/tiktok_icon.svg'); ?>" alt="TikTok" loading="lazy" decoding="async">
                </a>
                <a href="https://wa.me/923116600031" aria-label="Visit our WhatsApp page" class="footer-social-icon whatsapp-icon">
                    <img src="<?= url('public/assets/images/whatsapp_icon.svg'); ?>" alt="Whatsapp" loading="lazy" decoding="async">
                </a>
            </div>
        </div>

        <div class="footer-widget footer-locations">
            <h4>Contact Info</h4>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/map_icon.svg'); ?>" alt="Location Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="https://www.google.com/maps/dir//Al-ghaffar+shoping+mall,+G-11+Markaz+G+11+Markaz+G-11,+Islamabad,+Islamabad+Capital+Territory+44000/@33.6682651,72.9160123,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x38df957d29581291:0xe598ee9ef0015b3d!2m2!1d72.9984135!2d33.6682924?entry=ttu&g_ep=EgoyMDI0MDkwNC4wIKXMDSoASAFQAw%3D%3D" class="footer-block-caption">
                        Al-Ghaffar Shoping Mall shop 13B, G-11 Markaz Islamabad, Pakistan
                    </a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/email_icon.svg'); ?>" alt="Email Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="mailto:info@phonesdukan.com" class="footer-block-caption">info@phonesdukan.com</a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/phone_icon.svg'); ?>" alt="Phone Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="tel:+923116600031" class="footer-block-caption">(+92) 3116600031</a>
                </div>
            </div>
            <div class="footer-block">
                <img src="<?= url('public/assets/images/second_phone.svg'); ?>" alt="Phone Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="tel:051-2756587" class="footer-block-caption">051-2756587</a>
                </div>
            </div>
            <!-- <div class="footer-block">
                <img src="/public/assets/images/fax_icon.svg" alt="Phone Icon" class="footer-icon">
                <div class="footer-block-content">
                    <a href="tel:0512756587" class="footer-block-caption">0512756587</a>
                </div>
            </div> -->
        </div>

        <div class="footer-widget footer-categories">
            <h4>Categories</h4>
            <ul>
                <li><a href="<?= url('mobiles/'); ?>">Mobiles</a></li>
                <li><a href="<?= url('smart-watches/'); ?>">Smart Watches</a></li>
                <li><a href="<?= url('wireless-earbuds/'); ?>">Wireless Earbuds</a></li>
                <li><a href="<?= url('mobile-accessories/'); ?>">Mobile Accessories</a></li>
                <li><a href="<?= url('power-banks/'); ?>">Power Banks</a></li>
                <li><a href="<?= url('bluetooth-speakers/'); ?>">Bluetooth Speakers</a></li>
            </ul>
        </div>

        <div class="footer-widget footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= url(); ?>">Home</a></li>
                <li><a href="<?= url('about-us'); ?>">About Us</a></li>
                <li><a href="<?= url('contact-us/'); ?>">Contact</a></li>
                <li><a href="<?= url('my-account'); ?>">My Account</a></li>
                <li><a href="<?= url('track-order'); ?>">Track Order</a></li>
                <li><a href="<?= url('return-policy/'); ?>">Return Policy</a></li>
            </ul>
        </div>

        <div class="footer-widget footer-newsletter">
            <h4>Newsletter</h4>
            <p>Get product updates and exclusive offers straight to your inbox.</p>
            <form action="" method="post" class="contact-form">
                <input type="hidden" name="newsletter_submitted" value="1">
                <div class="form-group">
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your name" required>
                </div>
                <div class="form-group">
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="news-submit">
                    <span class="news-submit-spinner" aria-hidden="true"></span>
                    <span class="news-submit-label">Subscribe</span>
                </button>
                <p class="newsletter-feedback" aria-live="polite"></p>
            </form>
        </div>
    </div>
</footer>

<div class="footer-info">
    <p class="footer-info-copy">&copy; <?php echo date('Y'); ?> Phones Dukan • All rights reserved</p>
    <nav class="footer-policy-links" aria-label="Legal policies">
        <a href="<?= url('privacy-policy/'); ?>">Privacy Policy</a>
        <span class="footer-policy-sep" aria-hidden="true">•</span>
        <a href="<?= url('return-policy/'); ?>">Return/Refund Policy</a>
        <span class="footer-policy-sep" aria-hidden="true">•</span>
        <a href="<?= url('shipping-policy/'); ?>">Shipping/Service Policy</a>
        <span class="footer-policy-sep" aria-hidden="true">•</span>
        <a href="<?= url('terms-and-conditions/'); ?>">Terms &amp; Conditions</a>
    </nav>
    <div class="payment-image">
        <img src="<?= url('public/assets/images/payment.webp'); ?>" alt="Secure Payment">
    </div>
</div>

<div id="product-news-popup" class="popup-overlay hidden">
    <div class="popup-content">
        <p>Stay updated with our latest products and news!</p>
        <button id="accept-popup" class="popup-button">Accept</button>
    </div>
</div>
<div id="pdPushPrompt" class="pd-push-prompt" aria-live="polite" aria-hidden="true" style="opacity:0;visibility:hidden;pointer-events:none">
    <div class="pd-push-card">
        <div class="pd-push-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#facc15" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </div>
        <div class="pd-push-content">
            <h4>Stay Updated</h4>
            <p>Subscribe to our notifications for latest products, offers, and updates.</p>
        </div>
        <div class="pd-push-actions">
            <button type="button" id="pdPushDismiss" class="pd-push-btn pd-push-btn-ghost">No Thanks</button>
            <button type="button" id="pdPushAllow" class="pd-push-btn pd-push-btn-primary">Allow</button>
        </div>
    </div>
</div>
<?php loadJS(); // Dynamically load JS ?>
<?php if (isset($schema) && !empty($schema)) : ?>
<script type="application/ld+json">
<?= $schema ?>
</script>
<?php endif; ?>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4182308742558451"
     crossorigin="anonymous"></script>
     
     <script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "rbu44uocrv");
</script>

<style>
  .pd-push-prompt {
    position: fixed;
    left: 20px;
    right: auto;
    bottom: 20px;
    width: min(360px, calc(100vw - 40px));
    z-index: 2147483001;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(16px);
    transition: opacity .25s ease, transform .25s ease, visibility .25s ease;
  }
  .pd-push-prompt.is-visible {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0);
  }
  .pd-push-card {
    width: 100%;
    margin: 0;
    background: #111111;
    color: #ffffff;
    border: 1px solid rgba(250, 204, 21, .38);
    border-radius: 16px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, .28);
    padding: 14px 14px 12px;
  }
  .pd-push-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(250, 204, 21, .16);
    border: 1px solid rgba(250, 204, 21, .45);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 10px;
  }
  .pd-push-content h4 {
    margin: 0 0 6px;
    font-size: 1.05rem;
    font-weight: 800;
    color: #ffffff;
  }
  .pd-push-content p {
    margin: 0;
    color: #d1d5db;
    font-size: .9rem;
    line-height: 1.45;
  }
  .pd-push-actions {
    margin-top: 12px;
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    flex-wrap: wrap;
  }
  .pd-push-btn {
    min-width: 110px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: .86rem;
    font-weight: 800;
    cursor: pointer;
    transition: color .15s ease, border-color .15s ease;
  }
  .pd-push-btn-primary {
    background: #facc15;
    border-color: #facc15;
    color: #111111;
  }
  .pd-push-btn-primary:hover {
    background: #fde047;
    border-color: #fde047;
    color: #111111;
  }
  .pd-push-btn-ghost {
    background: #ffffff;
    border-color: #e5e7eb;
    color: #111111;
  }
  .pd-push-btn-ghost:hover {
    border-color: #facc15;
    color: #111111;
  }
  @media (max-width: 768px) {
    .pd-push-prompt {
      left: 10px;
      right: 10px;
      bottom: 12px;
      width: auto;
    }
    .pd-push-card {
      border-radius: 14px;
      padding: 12px 12px 10px;
    }
    .pd-push-actions {
      justify-content: stretch;
    }
    .pd-push-btn { flex: 1 1 auto; min-width: 0; }
  }
</style>
<script>
  window.pdRequestPushPermission = (function () {
    var sdkLoadingPromise = null;
    var initialized = false;

    function loadSdk() {
      if (window.OneSignal) return Promise.resolve(window.OneSignal);
      if (sdkLoadingPromise) return sdkLoadingPromise;
      sdkLoadingPromise = new Promise(function (resolve, reject) {
        var script = document.createElement('script');
        script.src = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
        script.async = true;
        script.onload = function () { resolve(window.OneSignal); };
        script.onerror = function () { reject(new Error('OneSignal SDK failed to load')); };
        document.head.appendChild(script);
      });
      return sdkLoadingPromise;
    }

    async function initIfNeeded(OneSignal) {
      if (initialized) return;
      await OneSignal.init({
        appId: "9b0a0b38-8dba-4ad8-8f5e-fa7531e4f27c",
        safari_web_id: "web.onesignal.auto.521cdcf4-43b8-4659-a2e2-fd037f95e0d5",
        notifyButton: { enable: false },
        autoResubscribe: false,
        promptOptions: { slidedown: { enabled: false, autoPrompt: false } },
        welcomeNotification: {
          title: "Thanks for Subscribing!",
          message: "You’ll now receive our latest updates."
        }
      });
      initialized = true;
    }

    return async function () {
      try {
        var OneSignal = await loadSdk();
        if (!OneSignal) throw new Error('OneSignal unavailable');
        await initIfNeeded(OneSignal);
        if (OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') {
          await OneSignal.Notifications.requestPermission();
        } else if (typeof Notification !== 'undefined' && typeof Notification.requestPermission === 'function') {
          await Notification.requestPermission();
        }
      } catch (err) {
        console.error('OneSignal permission request failed:', err);
      }
    };
  })();
</script>
<script>
(function () {
  var promptEl = document.getElementById('pdPushPrompt');
  var allowBtn = document.getElementById('pdPushAllow');
  var dismissBtn = document.getElementById('pdPushDismiss');
  if (!promptEl || !allowBtn || !dismissBtn) return;

  var HIDE_KEY = 'pd_push_prompt_hidden_until';
  var SHOWN_ONCE_KEY = 'pd_push_prompt_shown_once';
  var HIDE_FOR_MS = 24 * 60 * 60 * 1000;

  function isHomePage() {
    var path = (window.location.pathname || '/').toLowerCase();
    path = path.replace(/\/+$/, '');
    return path === '' || path === '/' || path === '/phonesdukan' || path === '/phonesdukan/index.php';
  }

  function shouldShowPrompt() {
    if (!isHomePage()) return false;
    if (Notification && Notification.permission === 'granted') return false;
    if (localStorage.getItem(SHOWN_ONCE_KEY) === '1') return false;
    var hiddenUntil = parseInt(localStorage.getItem(HIDE_KEY) || '0', 10);
    return isNaN(hiddenUntil) || Date.now() >= hiddenUntil;
  }

  function hidePrompt(storeDelay) {
    promptEl.classList.remove('is-visible');
    promptEl.setAttribute('aria-hidden', 'true');
    if (storeDelay) localStorage.setItem(HIDE_KEY, String(Date.now() + HIDE_FOR_MS));
  }

  function showPrompt() {
    if (!shouldShowPrompt()) return;
    promptEl.removeAttribute('style');
    promptEl.classList.add('is-visible');
    promptEl.setAttribute('aria-hidden', 'false');
    localStorage.setItem(SHOWN_ONCE_KEY, '1');
  }

  dismissBtn.addEventListener('click', function () { hidePrompt(true); });
  allowBtn.addEventListener('click', async function () {
    hidePrompt(true);
    if (typeof window.pdRequestPushPermission === 'function') await window.pdRequestPushPermission();
  });

  window.addEventListener('load', function () { setTimeout(showPrompt, 2600); });
})();
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const newsletterForm = document.querySelector('#footer .footer-newsletter .contact-form');
    if (!newsletterForm) return;

    const submitButton = newsletterForm.querySelector('.news-submit');
    const submitLabel = newsletterForm.querySelector('.news-submit-label');
    const feedbackMessage = newsletterForm.querySelector('.newsletter-feedback');
    const inputs = newsletterForm.querySelectorAll('input[type="text"], input[type="email"]');

    if (!submitButton || !submitLabel || !feedbackMessage) return;

    const defaultText = (submitLabel.textContent || 'Subscribe').trim();
    let isSubmitting = false;
    let resetTimer = null;

    const clearResetTimer = function () {
      if (resetTimer) {
        clearTimeout(resetTimer);
        resetTimer = null;
      }
    };

    const setFeedback = function (message, type) {
      feedbackMessage.textContent = message || '';
      feedbackMessage.classList.remove('is-success', 'is-error');
      if (type === 'success') {
        feedbackMessage.classList.add('is-success');
      } else if (type === 'error') {
        feedbackMessage.classList.add('is-error');
      }
    };

    const setButtonState = function (stateClass, text) {
      submitButton.classList.remove('is-loading', 'is-success', 'is-error');
      if (stateClass) {
        submitButton.classList.add(stateClass);
      }
      submitLabel.textContent = text;
    };

    const resetButtonState = function () {
      isSubmitting = false;
      submitButton.disabled = false;
      setButtonState('', defaultText);
      setFeedback('', '');
      newsletterForm.setAttribute('aria-busy', 'false');
    };

    newsletterForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      if (isSubmitting) return;
      isSubmitting = true;
      clearResetTimer();

      submitButton.disabled = true;
      setButtonState('is-loading', 'Subscribing...');
      setFeedback('', '');
      newsletterForm.setAttribute('aria-busy', 'true');

      try {
        const response = await fetch(newsletterForm.getAttribute('action') || window.location.href, {
          method: (newsletterForm.getAttribute('method') || 'POST').toUpperCase(),
          body: new FormData(newsletterForm),
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const responseText = await response.text();
        const textLower = responseText.toLowerCase();
        const likelyError =
          /(error|failed|unable|invalid|try again)/.test(textLower) &&
          /(newsletter|subscribe|email)/.test(textLower);

        if (likelyError) {
          throw new Error('Subscription failed');
        }

        setButtonState('is-success', 'Subscribed');
        setFeedback('You have successfully subscribed!', 'success');

        inputs.forEach(function (field) {
          field.value = '';
        });

        resetTimer = setTimeout(function () {
          resetButtonState();
        }, 4000);
      } catch (error) {
        setButtonState('is-success', 'Subscribed');
        setFeedback('You have successfully subscribed!', 'success');

        inputs.forEach(function (field) {
          field.value = '';
        });

        resetTimer = setTimeout(function () {
          resetButtonState();
        }, 4000);
      }
    });
  });
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-EEN4K7V3GP"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-EEN4K7V3GP');
</script>

<button id="back-to-top" aria-label="Back to top" title="Back to top">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
</button>

<style>
#back-to-top {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ffd400;
    color: #111111;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(0,0,0,0.25);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease, background 0.2s ease;
}
#back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
#back-to-top:hover {
    background: #ffe033;
}
@media (max-width: 768px) {
    #back-to-top {
        bottom: 20px;
        left: 16px;
        right: auto;
        width: 40px;
        height: 40px;
    }
}
</style>
<script>
(function () {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    }, { passive: true });
    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
</script>

</div> <!-- Close site-wrapper -->

<!-- ── Download App Widget ──────────────────────────────────────────────────── -->
<?php if (empty($isPdApp)): ?>
<button id="pd-install-app-btn" type="button"
        aria-label="Download our Android app"
        title="Download App"
        data-download-url="/public/download-app.php">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
        <line x1="12" y1="18" x2="12.01" y2="18"/>
    </svg>
    <span class="pd-install-btn-label">Get App</span>
</button>

<div id="pd-install-app-panel" role="dialog" aria-label="Download Phones Dukan App" aria-hidden="true">
    <div class="pd-install-header">
        <div class="pd-install-header-icon">
            <img src="<?= url('public/assets/images/phonesdukan_logo.png'); ?>" alt="">
        </div>
        <div class="pd-install-header-info">
            <h4>Download Phones Dukan App</h4>
            <span>Get our Android app on your phone</span>
        </div>
        <button id="pd-install-app-close" type="button" class="pd-install-close" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div class="pd-install-body">
        <div id="pd-install-android" class="pd-install-section">
            <p><strong>Android installation</strong></p>
            <ol>
                <li>Your <strong>PhonesDukan.apk</strong> download should start automatically.</li>
                <li>Open the downloaded file from your notifications or Downloads folder.</li>
                <li>If asked, allow <strong>Install from unknown sources</strong> for your browser.</li>
                <li>Tap <strong>Install</strong>, then open the app and start shopping.</li>
            </ol>
            <button type="button" id="pd-install-redownload" class="pd-install-cta">Download Again</button>
        </div>

        <div id="pd-install-ios" class="pd-install-section" hidden>
            <p><strong>iPhone / iPad</strong></p>
            <p>The APK file is for Android phones only. On iPhone, you can still use Phones Dukan in Safari and add it to your home screen:</p>
            <ol>
                <li>Tap the <strong>Share</strong> button in Safari.</li>
                <li>Tap <strong>Add to Home Screen</strong>.</li>
                <li>Tap <strong>Add</strong> to open Phones Dukan like an app.</li>
            </ol>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    function pdAppCookieValue() {
        try {
            return /(?:^|;\s*)pd_app=1(?:;|$)/.test(document.cookie || "");
        } catch (e) {}
        return false;
    }
    function setPdAppCookie() {
        try {
            var cookie = "pd_app=1;path=/;max-age=31536000;SameSite=Lax";
            if (/phonesdukan\.com$/i.test(location.hostname)) {
                cookie += ";domain=.phonesdukan.com";
            }
            document.cookie = cookie;
        } catch (e) {}
    }
    function isPdAppClient() {
        var root = document.documentElement;
        var ua = navigator.userAgent || "";
        var qs = typeof location !== "undefined" ? location.search : "";
        if (/PhonesDukanApp/i.test(ua)) return true;
        if (/[?&]pd_app=1(?:&|$)/.test(qs)) return true;
        if (pdAppCookieValue()) return true;
        try {
            if (localStorage.getItem("pd_app") === "1") return true;
        } catch (e) {}
        try {
            if (window.PhonesDukanNative && window.PhonesDukanNative.isApp && window.PhonesDukanNative.isApp()) {
                return true;
            }
        } catch (e) {}
        return root.getAttribute("data-pd-app") === "1";
    }
    function markPdAppClient() {
        document.documentElement.setAttribute("data-pd-app", "1");
        try { localStorage.setItem("pd_app", "1"); } catch (e) {}
        setPdAppCookie();
    }
    function removeInstallWidget() {
        if (!isPdAppClient()) return;
        markPdAppClient();
        ["pd-install-app-btn", "pd-install-app-panel"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        });
    }
    removeInstallWidget();
    if (isPdAppClient() && typeof MutationObserver !== "undefined") {
        new MutationObserver(removeInstallWidget).observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", removeInstallWidget);
    }
})();
</script>
<!-- ── /Download App Widget ─────────────────────────────────────────────────── -->

</body>
</html>
