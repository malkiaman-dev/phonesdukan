<?php
$pageTitle = "Shipping & Service Policy - Phones Dukan";
$metaDescription = "Shipping, delivery, and service policy for Phones Dukan orders across Pakistan. Learn about delivery times, charges, order tracking, and warranty support.";
$metaRobots = "index, follow";
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>

<section id="shipping-policy" class="pd-policy-page">
  <div class="pd-policy-hero">
    <div class="pd-policy-hero-content">
      <h1>Shipping &amp; Service Policy</h1>
      <p>Delivery timelines, shipping charges, tracking, and after-sales service.</p>
      <span class="pd-policy-updated">Last updated: June 15, 2026</span>
    </div>
    <div class="pd-policy-hero-icon" aria-hidden="true">
      <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 40h36l8-12V22H8v18z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
        <circle cx="18" cy="44" r="5" stroke="currentColor" stroke-width="3"/>
        <circle cx="44" cy="44" r="5" stroke="currentColor" stroke-width="3"/>
        <path d="M44 28h12l4 8" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
  </div>

  <div class="pd-policy-layout">
    <aside class="pd-policy-sidebar" aria-label="Policy quick links">
      <p class="pd-policy-sidebar-title">Quick Links</p>
      <nav class="pd-policy-sidebar-nav">
        <a href="#delivery-areas">Delivery Areas</a>
        <a href="#shipping-charges">Shipping Charges</a>
        <a href="#processing-times">Processing Times</a>
        <a href="#order-tracking">Order Tracking</a>
        <a href="#warranty-service">Warranty &amp; Service</a>
        <a href="#contact-us">Contact</a>
      </nav>
    </aside>

    <div class="pd-policy-content-card">
      <p class="pd-policy-intro">Thank you for shopping at Phones Dukan.</p>
      <p>This Shipping &amp; Service Policy outlines how we deliver products across Pakistan, how long delivery takes, and what support you can expect after your purchase.</p>

      <section id="delivery-areas" class="pd-policy-section">
        <h2>Delivery Areas</h2>
        <p>We deliver to major cities and towns across Pakistan through trusted courier partners. Delivery availability may vary for remote or hard-to-reach locations. If your area is not serviceable, our team will contact you to discuss alternatives.</p>
        <p class="pd-policy-highlight">In-store pickup is also available at our Islamabad location: Al-Ghaffar Shopping Mall, Shop 13B, G-11 Markaz.</p>
      </section>

      <section id="shipping-charges" class="pd-policy-section">
        <h2>Shipping Charges</h2>
        <p>Shipping fees depend on your delivery city, order weight, and selected courier service. Applicable charges are displayed at checkout before you confirm your order.</p>
        <ul class="pd-policy-list">
          <li>Standard delivery charges apply to most orders</li>
          <li>Free or discounted shipping may be offered during promotions</li>
          <li>Cash on delivery (COD) orders may include additional handling fees where applicable</li>
        </ul>
      </section>

      <section id="processing-times" class="pd-policy-section">
        <h2>Order Processing &amp; Delivery Times</h2>
        <p>Orders are typically processed within 1–2 business days after payment confirmation. Delivery timelines after dispatch are generally:</p>
        <ul class="pd-policy-list">
          <li><strong>Islamabad &amp; Rawalpindi:</strong> 1–2 business days</li>
          <li><strong>Major cities (Lahore, Karachi, Faisalabad, etc.):</strong> 2–4 business days</li>
          <li><strong>Other areas:</strong> 3–7 business days</li>
        </ul>
        <p>Delivery times are estimates and may be affected by public holidays, weather, courier delays, or product availability. We will notify you of any significant delay.</p>
      </section>

      <section id="order-tracking" class="pd-policy-section">
        <h2>Order Tracking</h2>
        <p>Once your order is dispatched, you will receive tracking details via SMS, WhatsApp, or email (depending on the contact information provided). You can also track your order on our website:</p>
        <p><a href="<?= url('track-order'); ?>"><?= url('track-order'); ?></a></p>
        <p class="pd-policy-highlight">Please ensure your phone number and delivery address are accurate at checkout to avoid delivery issues.</p>
      </section>

      <section id="damaged-items" class="pd-policy-section">
        <h2>Damaged or Incorrect Deliveries</h2>
        <p>If you receive a damaged, defective, or incorrect item, please contact us within 48 hours of delivery with your order number and photos of the product/packaging. We will arrange a replacement, repair, or refund in accordance with our <a href="<?= url('return-policy/'); ?>">Return &amp; Refund Policy</a>.</p>
      </section>

      <section id="warranty-service" class="pd-policy-section">
        <h2>Warranty &amp; After-Sales Service</h2>
        <p>Many products sold at Phones Dukan include manufacturer warranty. Warranty terms vary by brand and product category. Our team can guide you on:</p>
        <ul class="pd-policy-list">
          <li>Warranty registration and claim procedures</li>
          <li>Authorized service center referrals</li>
          <li>In-store support for eligible products purchased from us</li>
        </ul>
        <p>Warranty coverage does not include physical damage, liquid damage, unauthorized repairs, or misuse. Please retain your invoice for warranty claims.</p>
      </section>

      <section id="contact-us" class="pd-policy-section pd-policy-contact-section">
        <h2>Contact Us</h2>
        <p>For shipping updates, delivery issues, or service inquiries, contact our support team:</p>

        <div class="pd-policy-contact-grid">
          <a class="pd-policy-contact-card" href="<?= url('contact-us/'); ?>">
            <span class="pd-policy-contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4a14 14 0 0 1 0 16M12 4a14 14 0 0 0 0 16M5 7h14M5 17h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </span>
            <span>
              <strong>Website</strong>
              <small>Contact Us</small>
            </span>
          </a>

          <a class="pd-policy-contact-card" href="https://wa.me/923116600031" target="_blank" rel="noopener noreferrer">
            <span class="pd-policy-contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
            </span>
            <span>
              <strong>WhatsApp</strong>
              <small>(+92) 3116600031</small>
            </span>
          </a>

          <a class="pd-policy-contact-card" href="tel:+923116600031">
            <span class="pd-policy-contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-2.5 1.5a14 14 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2.2 2A17 17 0 0 1 3 6.2 2 2 0 0 1 5 4z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
            </span>
            <span>
              <strong>Phone</strong>
              <small>(+92) 3116600031</small>
            </span>
          </a>
        </div>
      </section>
    </div>
  </div>
</section>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
