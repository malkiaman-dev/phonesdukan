<?php
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/ContactController.php';
$controller = new ContactController();
$controller->handleFormSubmission();
$showSuccessPopup = (isset($_GET['sent']) && $_GET['sent'] === '1')
    || !empty($controller->success)
    || isset($_SESSION['success']);
$showSuccessPopupAttr = $showSuccessPopup ? '1' : '0';
?>

<section class="pd-contact-page">
    <div class="pd-contact-container">
        <section class="pd-contact-hero" aria-label="Contact hero">
            <h1 class="pd-contact-hero-title">Get in Touch</h1>
            <p class="pd-contact-hero-subtitle">Have questions about products, orders, or support? We're here to help.</p>
        </section>

        <section class="pd-contact-info" aria-label="Contact information">
            <article class="pd-contact-info-card">
                <span class="pd-contact-info-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/>
                        <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h3>Email Us</h3>
                <p><a href="mailto:info@phonesdukan.com">info@phonesdukan.com</a></p>
            </article>

            <article class="pd-contact-info-card">
                <span class="pd-contact-info-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M22 16.92V20a2 2 0 0 1-2.18 2 19.7 19.7 0 0 1-8.58-3.05 19.4 19.4 0 0 1-6-6A19.7 19.7 0 0 1 2.18 4.2 2 2 0 0 1 4.17 2h3.09a2 2 0 0 1 2 1.72c.12.9.35 1.78.69 2.61a2 2 0 0 1-.45 2.11L8.2 9.8a16 16 0 0 0 6 6l1.36-1.3a2 2 0 0 1 2.1-.45c.83.34 1.71.57 2.61.69A2 2 0 0 1 22 16.92Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h3>Call Us</h3>
                <p><a href="tel:+923116600031">(+92) 3116600031</a></p>
            </article>

            <article class="pd-contact-info-card">
                <span class="pd-contact-info-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 21s7-5.8 7-11a7 7 0 1 0-14 0c0 5.2 7 11 7 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                <h3>Visit Us</h3>
                <p><a href="https://www.google.com/maps/dir//Al-ghaffar+shoping+mall,+G-11+Markaz+G+11+Markaz+G-11,+Islamabad,+Islamabad+Capital+Territory+44000/@33.6682651,72.9160123,12z/data=!4m8!4m7!1m0!1m5!1m1!1s0x38df957d29581291:0xe598ee9ef0015b3d!2m2!1d72.9984135!2d33.6682924?entry=ttu&g_ep=EgoyMDI0MDkwNC4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noopener noreferrer">Al-Ghaffar Mall Shop 13B, G-11 Markaz, Islamabad</a></p>
            </article>
        </section>

        <section class="pd-contact-main" aria-label="Map and contact form">
            <div class="pd-contact-map-card">
                <h3 class="pd-contact-card-title">Our Location</h3>
                <div class="pd-contact-map-wrap">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d13282.281486461163!2d72.9984135!3d33.6682924!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38df957d29581291%3A0xe598ee9ef0015b3d!2sMobile%20Island!5e0!3m2!1sen!2s!4v1726505074794!5m2!1sen!2s"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>

                    <div class="pd-contact-map-overlay">
                        <strong>Phones Dukan</strong>
                        <span>Al-Ghaffar Mall Shop 13B, G-11 Markaz, Islamabad</span>
                        <small>Trusted by thousands of customers</small>
                    </div>
                </div>
            </div>

            <div class="pd-contact-form-card">
                <h3 class="pd-contact-card-title">Send us a Message</h3>
                <p class="pd-contact-helper">We usually respond within 24 hours.</p>

                <?php if (!empty($controller->error)) : ?>
                    <div class="pd-alert pd-alert-danger" role="alert"><?= htmlspecialchars($controller->error) ?></div>
                <?php endif; ?>

                <?php if (!empty($controller->success)) : ?>
                    <div class="pd-alert pd-alert-success" role="alert"><?= htmlspecialchars($controller->success) ?></div>
                <?php endif; ?>

                <?php
                if (isset($_GET['sent']) && $_GET['sent'] === '1') {
                    echo '<div class="pd-alert pd-alert-success" role="alert">Thank you for contacting us. We will reply shortly.</div>';
                }
                if (isset($_SESSION['success'])) {
                    unset($_SESSION['success']);
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="pd-alert pd-alert-danger" role="alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
                    unset($_SESSION['error']);
                }
                ?>

                <form method="POST" action="" class="pd-contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="pd-contact-field">
                        <label for="contact-name">Name</label>
                        <div class="pd-contact-input-wrap">
                            <span class="pd-contact-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input id="contact-name" type="text" name="name" placeholder="Your Name" required>
                        </div>
                    </div>

                    <div class="pd-contact-field">
                        <label for="contact-email">Email</label>
                        <div class="pd-contact-input-wrap">
                            <span class="pd-contact-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/>
                                    <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input id="contact-email" type="email" name="email" placeholder="Your Email" required>
                        </div>
                    </div>

                    <div class="pd-contact-field">
                        <label for="contact-subject">Subject</label>
                        <div class="pd-contact-input-wrap">
                            <span class="pd-contact-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 12V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="m9 9 6 0M9 13h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input id="contact-subject" type="text" name="subject" placeholder="Subject" required>
                        </div>
                    </div>

                    <div class="pd-contact-field">
                        <label for="contact-message">Message</label>
                        <div class="pd-contact-input-wrap pd-contact-textarea-wrap">
                            <span class="pd-contact-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M21 15a2 2 0 0 1-2 2H8l-5 5V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <textarea id="contact-message" name="message" placeholder="Your Message" required></textarea>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="pd-contact-btn">Send Message</button>
                    <div id="form-status-message" class="pd-contact-status" aria-live="polite"></div>
                </form>
            </div>
        </section>
    </div>
</section>

<div id="pd-contact-success-modal" class="pd-success-popup-wrap<?= $showSuccessPopup ? '' : ' is-hidden' ?>" role="status" aria-live="polite" data-show-popup="<?= htmlspecialchars($showSuccessPopupAttr, ENT_QUOTES, 'UTF-8') ?>">
    <div class="pd-success-popup-card" style="opacity:1;">
        <span class="pd-success-popup-icon" aria-hidden="true">✓</span>
        <span class="pd-success-popup-text">Thank you for contacting us. We will reply shortly.</span>
    </div>
</div>

<style>
.pd-contact-page{background:linear-gradient(170deg,#f7f8fb 0%,#ffffff 58%,#f9fafb 100%);padding:34px 14px 56px}
.pd-contact-container{max-width:1160px;margin:0 auto;display:grid;gap:24px}
.pd-contact-hero{text-align:center;background:#fff;border:1px solid #eceff4;border-radius:16px;box-shadow:0 12px 32px rgba(17,24,39,.06);padding:30px 20px}
.pd-contact-hero-title{margin:0;color:#111827;font-size:clamp(30px,5vw,44px);line-height:1.15}
.pd-contact-hero-subtitle{margin:10px auto 0;max-width:680px;color:#4b5563;font-size:16px;line-height:1.7}
.pd-contact-info{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.pd-contact-info-card,.pd-contact-map-card,.pd-contact-form-card{background:#fff;border:1px solid #ebedf2;border-radius:16px;box-shadow:0 12px 30px rgba(17,24,39,.07);transition:transform .2s ease,box-shadow .2s ease}
.pd-contact-info-card:hover,.pd-contact-map-card:hover,.pd-contact-form-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(17,24,39,.1)}
.pd-contact-info-card{padding:22px}
.pd-contact-info-icon{width:42px;height:42px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:#facc15;color:#111827}
.pd-contact-info-icon svg{width:20px;height:20px}
.pd-contact-info-card h3{margin:12px 0 6px;color:#111827;font-size:20px}
.pd-contact-info-card p{margin:0;color:#374151;font-size:14px;line-height:1.6}
.pd-contact-info-card a{color:inherit;text-decoration:none}
.pd-contact-info-card a:hover{text-decoration:underline;text-decoration-color:#facc15}
.pd-contact-main{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px}
.pd-contact-map-card,.pd-contact-form-card{padding:24px}
.pd-contact-card-title{margin:0;color:#111827;font-size:28px}
.pd-contact-helper{margin:8px 0 0;color:#6b7280;font-size:14px}
.pd-contact-map-wrap{margin-top:16px;position:relative;border-radius:14px;overflow:hidden;border:1px solid #eceff4}
.pd-contact-map-wrap iframe{width:100%;min-height:420px;border:0;display:block}
.pd-contact-map-overlay{position:absolute;left:14px;right:14px;bottom:14px;background:rgba(17,24,39,.88);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:12px;padding:12px 14px;display:grid;gap:3px}
.pd-contact-map-overlay strong{color:#facc15;font-size:14px}
.pd-contact-map-overlay span,.pd-contact-map-overlay small{font-size:12px}
.pd-alert{border-radius:10px;padding:10px 12px;font-size:14px;margin-top:14px}
.pd-alert-success{border:1px solid #facc15;background:#fffbeb;color:#111827}
.pd-alert-danger{border:1px solid #fecaca;background:#fef2f2;color:#b91c1c}
.pd-contact-form{margin-top:16px;display:grid;gap:14px}
.pd-contact-field label{display:block;margin-bottom:6px;color:#1f2937;font-size:14px;font-weight:600}
.pd-contact-input-wrap{position:relative}
.pd-contact-input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:#6b7280;display:inline-flex;align-items:center;justify-content:center;transition:color .2s ease}
.pd-contact-input-icon svg{width:18px;height:18px}
.pd-contact-input-wrap input,.pd-contact-input-wrap textarea{width:100%;border:1px solid #d6deea;border-radius:10px;background:#fff;color:#111827;font-size:15px;transition:border-color .2s ease,box-shadow .2s ease}
.pd-contact-input-wrap input{height:50px;padding:0 12px 0 42px}
.pd-contact-textarea-wrap .pd-contact-input-icon{top:15px;transform:none}
.pd-contact-input-wrap textarea{min-height:130px;resize:vertical;padding:12px 12px 12px 42px}
.pd-contact-input-wrap:focus-within .pd-contact-input-icon{color:#facc15}
.pd-contact-input-wrap input:focus,.pd-contact-input-wrap textarea:focus{outline:none;border-color:#facc15;box-shadow:0 0 0 4px rgba(250,204,21,.22)}
.pd-contact-btn{width:100%;height:50px;border:none;border-radius:10px;background:#facc15;color:#111827;font-size:16px;font-weight:700;cursor:pointer;transition:background-color .2s ease,transform .2s ease,box-shadow .2s ease}
.pd-contact-btn:hover{background:#e8b900;transform:translateY(-1px);box-shadow:0 10px 22px rgba(232,185,0,.32)}
.pd-contact-btn:active{transform:translateY(0)}
.pd-contact-status{min-height:20px;color:#4b5563;font-size:13px}
#form-status-message.pd-contact-status.is-sending{margin-top:2px;padding:8px 10px;border-radius:8px;border:1px solid #facc15;background:#fffbeb;color:#111827 !important;font-weight:600}
.pd-success-popup-wrap{position:fixed;inset:0;z-index:12000;display:flex;align-items:center;justify-content:center;background:rgba(17,24,39,.28);padding:16px;opacity:1;transition:opacity .22s ease}
.pd-success-popup-wrap.is-hidden{opacity:0;pointer-events:none}
.pd-success-popup-card{max-width:460px;width:min(92vw,460px);display:flex;align-items:center;gap:12px;padding:16px 18px;border-radius:14px;border:1px solid #facc15;background:#fffbeb;color:#111827;box-shadow:0 18px 34px rgba(17,24,39,.22)}
.pd-success-popup-icon{width:24px;height:24px;border-radius:999px;background:#facc15;color:#111827;display:inline-flex;align-items:center;justify-content:center;font-weight:700;line-height:1}
.pd-success-popup-text{font-size:14px;font-weight:600;line-height:1.3}
@media (max-width:991px){.pd-contact-page{padding:24px 12px 40px}.pd-contact-info{grid-template-columns:1fr}.pd-contact-main{grid-template-columns:1fr}.pd-contact-hero,.pd-contact-info-card,.pd-contact-map-card,.pd-contact-form-card{border-radius:14px;padding:20px}.pd-contact-map-wrap iframe{min-height:320px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.pd-contact-form');
    const statusDiv = document.getElementById('form-status-message');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;
    const successToast = document.getElementById('pd-contact-success-modal');
    const sentInUrl = new URLSearchParams(window.location.search).get('sent') === '1';

    if (successToast) {
        const shouldShow = successToast.getAttribute('data-show-popup') === '1' || sentInUrl;
        if (shouldShow) {
            successToast.classList.remove('is-hidden');
            setTimeout(function () {
                successToast.classList.add('is-hidden');
                setTimeout(function () {
                    if (successToast && successToast.parentNode) {
                        successToast.parentNode.removeChild(successToast);
                    }
                }, 250);
            }, 5000);
        }
    }

    if (!form || !statusDiv) return;

    form.addEventListener('submit', function () {
        statusDiv.classList.remove('is-sending');
        statusDiv.textContent = '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.85';
            submitBtn.style.cursor = 'not-allowed';
        }
    });

});
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>