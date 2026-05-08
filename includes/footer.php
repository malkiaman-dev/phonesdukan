</div><!-- #content -->

<footer id="footer">
    <div class="footer-widgets">
        <div class="footer-widget footer-brand">
            <a href="<?= url(); ?>" class="footer-brand-logo" aria-label="Visit Phones Dukan Home">
                <img src="<?= url('public/assets/images/phonesdukan_logo.png'); ?>" alt="Phones Dukan Logo">
            </a>
            <p class="footer-brand-about">PhonesDukan is your trusted store for mobiles, smart watches, earbuds, accessories, power banks, and speakers across Pakistan.</p>
            <div class="footer-social-icons">
                <a href="https://www.facebook.com/phonesdukan" aria-label="Visit our Facebook page" class="footer-social-icon facebook-icon">
                    <img src="<?= url('public/assets/images/facebook_icon.svg'); ?>" alt="Facebook">
                </a>
                <a href="https://www.youtube.com/c/MobileIsland" aria-label="Visit our YouTube channel" class="footer-social-icon youtube-icon">
                    <img src="<?= url('public/assets/images/youtube_icon.svg'); ?>" alt="YouTube">
                </a>
                <a href="https://www.instagram.com/phonesdukan/" aria-label="Visit our Instagram page" class="footer-social-icon instagram-icon">
                    <img src="<?= url('public/assets/images/instagram_icon.svg'); ?>" alt="Instagram">
                </a>
                <a href="https://www.tiktok.com/@phonesdukan" aria-label="Visit our TikTok page" class="footer-social-icon tiktok-icon">
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
  /* ── OneSignal slidedown — PhonesDukan brand theme ── */
  #onesignal-slidedown-dialog,
  .onesignal-slidedown-dialog {
    background: #111111 !important;
    border: 1px solid rgba(250,204,21,.35) !important;
    border-radius: 14px !important;
    box-shadow: 0 24px 48px rgba(0,0,0,.6), 0 0 0 1px rgba(255,255,255,.04) !important;
    overflow: hidden !important;
    position: relative !important;
  }
  /* Yellow top accent bar */
  #onesignal-slidedown-dialog::before,
  .onesignal-slidedown-dialog::before {
    content: '' !important;
    display: block !important;
    position: absolute !important;
    top: 0 !important; left: 0 !important; right: 0 !important;
    height: 3px !important;
    background: linear-gradient(90deg, #facc15 0%, #f59e0b 100%) !important;
    border-radius: 14px 14px 0 0 !important;
    z-index: 1 !important;
  }
  .onesignal-slidedown-dialog .slidedown-body-message-author {
    color: #ffffff !important;
    font-weight: 700 !important;
  }
  .onesignal-slidedown-dialog .slidedown-body-message-body {
    color: #9ca3af !important;
    font-size: .86rem !important;
    line-height: 1.5 !important;
  }
  .onesignal-slidedown-dialog .slidedown-body-icon img {
    border-radius: 10px !important;
    padding: 4px !important;
    background: rgba(250,204,21,.12) !important;
    border: 1px solid rgba(250,204,21,.4) !important;
  }
  /* Allow — yellow CTA */
  .onesignal-slidedown-dialog .primary-slidedown-button {
    background: #facc15 !important;
    border-color: #facc15 !important;
    color: #111111 !important;
    font-weight: 700 !important;
    border-radius: 9px !important;
  }
  .onesignal-slidedown-dialog .primary-slidedown-button:hover {
    background: #fde047 !important;
    border-color: #fde047 !important;
  }
  /* No Thanks — ghost */
  .onesignal-slidedown-dialog .secondary-slidedown-button {
    background: rgba(255,255,255,.06) !important;
    border: 1px solid rgba(255,255,255,.12) !important;
    color: #9ca3af !important;
    font-weight: 600 !important;
    border-radius: 9px !important;
  }
  .onesignal-slidedown-dialog .secondary-slidedown-button:hover {
    background: rgba(255,255,255,.10) !important;
    border-color: rgba(255,255,255,.2) !important;
    color: #ffffff !important;
  }
</style>

<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "9b0a0b38-8dba-4ad8-8f5e-fa7531e4f27c",
      safari_web_id: "web.onesignal.auto.521cdcf4-43b8-4659-a2e2-fd037f95e0d5",
      notifyButton: {
        enable: false, // Hide the bell
      },
      promptOptions: {
        slidedown: {
          enabled: true,
          autoPrompt: false, // VERY IMPORTANT: Don't auto show immediately
          prompts: [
            {
              type: "push",
              text: {
                actionMessage: "Subscribe to our notifications for the latest news and updates.",
                acceptButton: "Allow",
                cancelButton: "No Thanks"
              },
              acceptButtonText: "Allow",
              cancelButtonText: "No Thanks"
            }
          ]
        }
      },
      welcomeNotification: {
        title: "Thanks for Subscribing!",
        message: "You’ll now receive our latest updates."
      }
    });

    // Now delay showing the subscribe popup
    setTimeout(function() {
      OneSignal.Slidedown.promptPush(); // This manually shows the popup after 5s
    }, 5000); // 5000 milliseconds = 5 seconds
  });
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

</div> <!-- Close site-wrapper -->
</body>
</html>
