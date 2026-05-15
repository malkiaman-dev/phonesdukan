</div><!-- #content -->

<footer id="footer">
    <div class="footer-widgets">
        <div class="footer-widget footer-brand">
            <a href="<?= url(); ?>" class="footer-brand-logo" aria-label="Visit Phones Dukan Home">
                <img src="<?= url('public/assets/images/phonesdukan_logo.png'); ?>" alt="Phones Dukan Logo">
            </a>
            <p class="footer-brand-about">PhonesDukan is your trusted store for mobiles, smart watches, earbuds, accessories, power banks, and speakers across Pakistan.</p>
            <div class="footer-social-icons">
                <a href="https://www.facebook.com/mobileisland01/" aria-label="Visit our Facebook page" class="footer-social-icon facebook-icon">
                    <img src="<?= url('public/assets/images/facebook_icon.svg'); ?>" alt="Facebook">
                </a>
                <a href="https://www.youtube.com/@mobileisland" aria-label="Visit our YouTube channel" class="footer-social-icon youtube-icon">
                    <img src="<?= url('public/assets/images/youtube_icon.svg'); ?>" alt="YouTube">
                </a>
                <a href="https://www.instagram.com/mobile_island01/" aria-label="Visit our Instagram page" class="footer-social-icon instagram-icon">
                    <img src="<?= url('public/assets/images/instagram_icon.svg'); ?>" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@mobile_island_g11_isb" aria-label="Visit our TikTok page" class="footer-social-icon tiktok-icon">
                    <img src="<?= url('public/assets/images/tiktok_icon.svg'); ?>" alt="TikTok">
                </a>
                <a href="https://wa.me/923116600031" aria-label="Visit our WhatsApp page" class="footer-social-icon whatsapp-icon">
                    <img src="<?= url('public/assets/images/whatsapp_icon.svg'); ?>" alt="Whatsapp">
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
    <div>
        <p>&copy; <?php echo date('Y'); ?> Phones Dukan • All rights reserved</p>
    </div>
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
        bottom: 16px;
        right: 16px;
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

<!-- ── Chatbot Widget ───────────────────────────────────────────────────────── -->
<button id="pd-chatbot-toggle" aria-label="Open chat assistant" title="Chat with us">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
</button>

<div id="pd-chatbot-win" role="dialog" aria-label="Phones Dukan Chat Assistant">
    <div class="pd-chat-header">
        <div class="pd-chat-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="#111" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        </div>
        <div class="pd-chat-header-info">
            <h4>Phones Dukan Assistant</h4>
            <span>Ask me anything about our products</span>
        </div>
        <button id="pd-chatbot-close" class="pd-chat-close" aria-label="Close chat">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div id="pd-chatbot-messages" class="pd-chat-messages">
        <div class="pd-chat-welcome">
            <p>Hi! Ask me about prices, products, or anything about Phones Dukan.</p>
        </div>
    </div>

    <form id="pd-chatbot-form" class="pd-chat-footer" autocomplete="off">
        <input id="pd-chatbot-input" class="pd-chat-input" type="text"
               placeholder="Type your question..." maxlength="500" autocomplete="off">
        <button id="pd-chatbot-send" class="pd-chat-send" type="submit" aria-label="Send">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
        </button>
    </form>
</div>
<!-- ── /Chatbot Widget ─────────────────────────────────────────────────────── -->

</div> <!-- Close site-wrapper -->
</body>
</html>
