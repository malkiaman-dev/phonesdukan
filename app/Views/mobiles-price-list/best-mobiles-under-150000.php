<?php
$pageTitle = "Top 5 Best Mobiles Under 150000 in Pakistan - 2025";
$metaDescription = "Find the top 5 best mobiles under 150000 PKR in Pakistan for 2025. True flagship smartphones with cutting-edge cameras, 5G, and premium design at Phones Dukan!";
$metaKeywords = "best mobiles under 150000, top mobiles under 150000, best mobiles under 150000 Pakistan, flagship phones Pakistan";
$metaRobots = "index, follow";
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>
<div class="best-mobiles-page">
    <section class="best-mobiles-hero">
        <div class="best-mobiles-hero-content">
            <h1>Best Mobiles <span class="hero-accent">Under 150,000</span> in Pakistan</h1>
            <p>Explore true flagship smartphones under 150,000 — premium build, top cameras, and cutting-edge 5G performance.</p>
        </div>
        <div class="best-mobiles-hero-art" aria-hidden="true">
            <svg viewBox="0 0 420 260" xmlns="http://www.w3.org/2000/svg">
                <rect x="26" y="22" width="368" height="214" rx="24" fill="#ffffff" stroke="#e5e7eb"/>
                <rect x="62" y="54" width="124" height="176" rx="14" fill="#111111"/>
                <rect x="72" y="66" width="104" height="132" rx="9" fill="#f5f5f5"/>
                <circle cx="124" cy="212" r="7" fill="#f7cf04"/>
                <rect x="216" y="66" width="146" height="56" rx="10" fill="#fff7cc" stroke="#f7cf04"/>
                <rect x="216" y="134" width="146" height="64" rx="10" fill="#f5f5f5" stroke="#e5e7eb"/>
                <path d="M236 88h102M236 104h78" stroke="#111111" stroke-width="5" stroke-linecap="round"/>
                <path d="M236 158h101M236 174h83" stroke="#666666" stroke-width="4" stroke-linecap="round"/>
            </svg>
        </div>
    </section>

    <nav class="best-mobiles-tabs" aria-label="Best mobiles by budget">
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-30000/'); ?>">Under 30,000</a>
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-40000/'); ?>">Under 40,000</a>
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-50000/'); ?>">Under 50,000</a>
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-60000/'); ?>">Under 60,000</a>
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-80000/'); ?>">Under 80,000</a>
        <a class="best-mobiles-tab" href="<?= url('mobiles-price-list/best-mobiles-under-100000/'); ?>">Under 100,000</a>
        <a class="best-mobiles-tab is-active" href="<?= url('mobiles-price-list/best-mobiles-under-150000/'); ?>">Under 150,000</a>
    </nav>

<div class="best-mobiles-container">
    <?php renderBestMobilesSidebar('150000'); ?>

    <div class="shop-content-right">
    <section class="product-container">
        <div class="product-item">
            <div class="product-details">
                <div class="product-top">
                <div class="product-image">
                    <img src="/wp-content/uploads/2024/03/Vivo-V30-5G.webp"
                         class="product-thumbnail" alt="Samsung Galaxy S24 FE" width="270">
                </div>
                    <h4 class="product-title"><a href="<?= url('mobiles/samsung/samsung-galaxy-s24-fe/'); ?>">Samsung Galaxy S24 FE</a></h4>
                    <div class="product-price">
                        <span class="mkt-price"><sup>Rs</sup> 149,999</span>
                        <span class="discounted-price"><sup>Rs</sup> 139,999</span>
                    </div>
                    <div class="buy-now">
                        <?php if (isProductInStock(165)): ?>
                            <button class="buy-button" data-product-id="165" data-unit-price="139999">Buy Now</button>
                        <?php else: ?>
                            <button class="buy-button disabled">Out of Stock</button>
                        <?php endif; ?>
                        <a class="secondary-cta" href="<?= url('mobiles/samsung/samsung-galaxy-s24-fe/'); ?>">View Details</a>
                    </div>
                </div>
        <div class="product-description">
         <p>The Samsung Galaxy S24 FE brings true flagship Galaxy S features to a more accessible price. With its Exynos 2500 chip, 6.7-inch AMOLED display, triple camera system with 50MP OIS, 7 years of OS updates, and an IP68 rating, it's the most future-proof phone under 150,000 PKR.</p>
 <div id="specs-bullet">
    <div class="specification"><img src="/public/assets/images/display.svg" alt="display"><div class="spec-desc"><strong>6.7 Inches</strong><span>Display</span></div></div>
    <div class="specification"><img src="/public/assets/images/camera.svg" alt="camera_svg"><div class="spec-desc"><strong>50MP + 12MP + 8MP</strong><span>Camera</span></div></div>
    <div class="specification"><img src="/public/assets/images/phone_memory.svg" alt="phone_memory_svg"><div class="spec-desc"><strong>128GB / 256GB</strong><span>Memory</span></div></div>
    <div class="specification"><img src="/public/assets/images/ram.svg" alt="phone_ram_svg"><div class="spec-desc"><strong>8GB RAM</strong><span>RAM</span></div></div>
    <div class="specification"><img src="/public/assets/images/battery.svg" alt="phone_battery_svg"><div class="spec-desc"><strong>4700 mAh</strong><span>Battery</span></div></div>
</div>
    </section>
    <section class="product-container">
        <div class="product-item">
            <div class="product-details">
                <div class="product-top">
                <div class="product-image">
                    <img src="/wp-content/uploads/2024/11/vivo-3t-pro-price-in-pakistan.webp"
                         class="product-thumbnail" alt="Vivo X90 Pro" width="270">
                </div>
                    <h4 class="product-title"><a href="<?= url('mobiles/vivo/vivo-x90-pro/'); ?>">Vivo X90 Pro</a></h4>
                    <div class="product-price">
                        <span class="mkt-price"><sup>Rs</sup> 149,999</span>
                        <span class="discounted-price"><sup>Rs</sup> 144,999</span>
                    </div>
                    <div class="buy-now">
                        <?php if (isProductInStock(180)): ?>
                            <button class="buy-button" data-product-id="180" data-unit-price="144999">Buy Now</button>
                        <?php else: ?>
                            <button class="buy-button disabled">Out of Stock</button>
                        <?php endif; ?>
                        <a class="secondary-cta" href="<?= url('mobiles/vivo/vivo-x90-pro/'); ?>">View Details</a>
                    </div>
                </div>
        <div class="product-description">
         <p>The Vivo X90 Pro is a true photography flagship featuring Zeiss co-engineered optics, a large 1-inch sensor, and Dimensity 9200 performance. With its premium build quality, flagship-grade display, and exceptional low-light photography, it redefines what's possible under 150,000 PKR.</p>
 <div id="specs-bullet">
    <div class="specification"><img src="/public/assets/images/display.svg" alt="display"><div class="spec-desc"><strong>6.78 Inches</strong><span>Display</span></div></div>
    <div class="specification"><img src="/public/assets/images/camera.svg" alt="camera_svg"><div class="spec-desc"><strong>50MP Zeiss + 50MP</strong><span>Camera</span></div></div>
    <div class="specification"><img src="/public/assets/images/phone_memory.svg" alt="phone_memory_svg"><div class="spec-desc"><strong>256GB</strong><span>Memory</span></div></div>
    <div class="specification"><img src="/public/assets/images/ram.svg" alt="phone_ram_svg"><div class="spec-desc"><strong>12GB RAM</strong><span>RAM</span></div></div>
    <div class="specification"><img src="/public/assets/images/battery.svg" alt="phone_battery_svg"><div class="spec-desc"><strong>4870 mAh</strong><span>Battery</span></div></div>
</div>
    </section>
    <section class="product-container">
        <div class="product-item">
            <div class="product-details">
                <div class="product-top">
                <div class="product-image">
                    <img src="/wp-content/uploads/2024/11/Realme-13.webp"
                         class="product-thumbnail" alt="Oppo Reno 12 Pro" width="270">
                </div>
                    <h4 class="product-title"><a href="<?= url('mobiles/oppo/oppo-reno-12-pro/'); ?>">Oppo Reno 12 Pro</a></h4>
                    <div class="product-price">
                        <span class="mkt-price"><sup>Rs</sup> 149,999</span>
                        <span class="discounted-price"><sup>Rs</sup> 134,999</span>
                    </div>
                    <div class="buy-now">
                        <?php if (isProductInStock(181)): ?>
                            <button class="buy-button" data-product-id="181" data-unit-price="134999">Buy Now</button>
                        <?php else: ?>
                            <button class="buy-button disabled">Out of Stock</button>
                        <?php endif; ?>
                        <a class="secondary-cta" href="<?= url('mobiles/oppo/oppo-reno-12-pro/'); ?>">View Details</a>
                    </div>
                </div>
        <div class="product-description">
         <p>The Oppo Reno 12 Pro is a sleek premium flagship with a stunning AMOLED display, flagship Dimensity 9300 performance, triple cameras with periscope zoom, and the fastest charging in its class. Its refined design and AI camera features make it a standout choice under 150,000 PKR.</p>
 <div id="specs-bullet">
    <div class="specification"><img src="/public/assets/images/display.svg" alt="display"><div class="spec-desc"><strong>6.7 Inches</strong><span>Display</span></div></div>
    <div class="specification"><img src="/public/assets/images/camera.svg" alt="camera_svg"><div class="spec-desc"><strong>50MP + 50MP + 8MP</strong><span>Camera</span></div></div>
    <div class="specification"><img src="/public/assets/images/phone_memory.svg" alt="phone_memory_svg"><div class="spec-desc"><strong>256GB</strong><span>Memory</span></div></div>
    <div class="specification"><img src="/public/assets/images/ram.svg" alt="phone_ram_svg"><div class="spec-desc"><strong>12GB RAM</strong><span>RAM</span></div></div>
    <div class="specification"><img src="/public/assets/images/battery.svg" alt="phone_battery_svg"><div class="spec-desc"><strong>5000 mAh</strong><span>Battery</span></div></div>
</div>
    </section>
    <section class="product-container">
        <div class="product-item">
            <div class="product-details">
                <div class="product-top">
                <div class="product-image">
                    <img src="/wp-content/uploads/2024/05/Xiaomi-Redmi-Note-13.webp"
                         class="product-thumbnail" alt="Xiaomi 13" width="270">
                </div>
                    <h4 class="product-title"><a href="<?= url('mobiles/xiaomi/xiaomi-13/'); ?>">Xiaomi 13</a></h4>
                    <div class="product-price">
                        <span class="mkt-price"><sup>Rs</sup> 149,999</span>
                        <span class="discounted-price"><sup>Rs</sup> 139,999</span>
                    </div>
                    <div class="buy-now">
                        <?php if (isProductInStock(182)): ?>
                            <button class="buy-button" data-product-id="182" data-unit-price="139999">Buy Now</button>
                        <?php else: ?>
                            <button class="buy-button disabled">Out of Stock</button>
                        <?php endif; ?>
                        <a class="secondary-cta" href="<?= url('mobiles/xiaomi/xiaomi-13/'); ?>">View Details</a>
                    </div>
                </div>
        <div class="product-description">
         <p>The Xiaomi 13 is a compact flagship featuring the Snapdragon 8 Gen 2, Leica co-engineered cameras, and a premium build in a comfortable form factor. With wireless charging, IP68 water resistance, and a vibrant AMOLED display, it's perfect for users who want a true flagship experience.</p>
 <div id="specs-bullet">
    <div class="specification"><img src="/public/assets/images/display.svg" alt="display"><div class="spec-desc"><strong>6.36 Inches</strong><span>Display</span></div></div>
    <div class="specification"><img src="/public/assets/images/camera.svg" alt="camera_svg"><div class="spec-desc"><strong>54MP Leica + 12MP + 10MP</strong><span>Camera</span></div></div>
    <div class="specification"><img src="/public/assets/images/phone_memory.svg" alt="phone_memory_svg"><div class="spec-desc"><strong>256GB</strong><span>Memory</span></div></div>
    <div class="specification"><img src="/public/assets/images/ram.svg" alt="phone_ram_svg"><div class="spec-desc"><strong>8GB / 12GB</strong><span>RAM</span></div></div>
    <div class="specification"><img src="/public/assets/images/battery.svg" alt="phone_battery_svg"><div class="spec-desc"><strong>4500 mAh</strong><span>Battery</span></div></div>
</div>
    </section>
    <section class="product-container">
        <div class="product-item">
            <div class="product-details">
                <div class="product-top">
                <div class="product-image">
                    <img src="/wp-content/uploads/2024/03/Vivo-V30-5G.webp"
                         class="product-thumbnail" alt="Samsung Galaxy A55 5G" width="270">
                </div>
                    <h4 class="product-title"><a href="<?= url('mobiles/samsung/samsung-galaxy-s24/'); ?>">Samsung Galaxy S24</a></h4>
                    <div class="product-price">
                        <span class="mkt-price"><sup>Rs</sup> 159,999</span>
                        <span class="discounted-price"><sup>Rs</sup> 149,999</span>
                    </div>
                    <div class="buy-now">
                        <?php if (isProductInStock(166)): ?>
                            <button class="buy-button" data-product-id="166" data-unit-price="149999">Buy Now</button>
                        <?php else: ?>
                            <button class="buy-button disabled">Out of Stock</button>
                        <?php endif; ?>
                        <a class="secondary-cta" href="<?= url('mobiles/samsung/samsung-galaxy-s24/'); ?>">View Details</a>
                    </div>
                </div>
        <div class="product-description">
         <p>The Samsung Galaxy S24 is a true flagship delivering the full Galaxy AI experience, Snapdragon 8 Gen 3 performance, a bright 120Hz Dynamic AMOLED display, IP68 water resistance, and 7 years of OS updates. It's the smartest investment under 150,000 PKR for long-term use.</p>
 <div id="specs-bullet">
    <div class="specification"><img src="/public/assets/images/display.svg" alt="display"><div class="spec-desc"><strong>6.2 Inches</strong><span>Display</span></div></div>
    <div class="specification"><img src="/public/assets/images/camera.svg" alt="camera_svg"><div class="spec-desc"><strong>50MP + 12MP + 10MP</strong><span>Camera</span></div></div>
    <div class="specification"><img src="/public/assets/images/phone_memory.svg" alt="phone_memory_svg"><div class="spec-desc"><strong>128GB / 256GB</strong><span>Memory</span></div></div>
    <div class="specification"><img src="/public/assets/images/ram.svg" alt="phone_ram_svg"><div class="spec-desc"><strong>8GB RAM</strong><span>RAM</span></div></div>
    <div class="specification"><img src="/public/assets/images/battery.svg" alt="phone_battery_svg"><div class="spec-desc"><strong>4000 mAh</strong><span>Battery</span></div></div>
</div>
    </section>
    </div>
</div>
<section class="p-table">
    <h2>Best Mobiles Under 150,000</h2>
<table border="1" cellpadding="10" cellspacing="0">
  <thead><tr><th>Product Name</th><th>Sale Price</th><th>Rating</th></tr></thead>
  <tbody>
    <tr><td>Samsung Galaxy S24 FE</td><td>PKR 139,999</td><td>⭐⭐⭐⭐⭐ (5/5)</td></tr>
    <tr><td>Vivo X90 Pro</td><td>PKR 144,999</td><td>⭐⭐⭐⭐⭐ (5/5)</td></tr>
    <tr><td>Oppo Reno 12 Pro</td><td>PKR 134,999</td><td>⭐⭐⭐⭐⭐ (5/5)</td></tr>
    <tr><td>Xiaomi 13</td><td>PKR 139,999</td><td>⭐⭐⭐⭐⭐ (5/5)</td></tr>
    <tr><td>Samsung Galaxy S24</td><td>PKR 149,999</td><td>⭐⭐⭐⭐⭐ (5/5)</td></tr>
  </tbody>
</table>
</section>
<section id="buyers-guide">
<h2>What to Look for in a Mobile Under 150,000?</h2>
    <div class="buy-container">
        <ul>
            <li><strong>Flagship Chip:</strong> Snapdragon 8 Gen 2/3 or Dimensity 9200+ for true flagship performance.</li>
            <li><strong>Camera System:</strong> Co-engineered optics (Leica, Zeiss, Hasselblad) set these phones apart.</li>
            <li><strong>IP Rating:</strong> IP68 water and dust resistance is essential for premium protection.</li>
        </ul>
        <ul>
            <li><strong>Software Updates:</strong> Brands like Samsung now promise 7 years of updates — a key differentiator.</li>
            <li><strong>Wireless Charging:</strong> Available on most flagships at this price; convenient and practical.</li>
            <li><strong>AI Features:</strong> Galaxy AI, Gemini, and other AI enhancements are available in this segment.</li>
        </ul>
    </div>
</section>
<section id="use-cases">
    <h2>Best Mobiles Under 150,000 for Specific Needs</h2>
    <p>At this price point, you're getting true flagship experiences. Every phone here excels in its own way.</p>
    <div class="case-container">
        <div class="use-case">
            <h3>Best for Longevity Under 150,000</h3>
            <p>The Samsung Galaxy S24 with 7 years of guaranteed updates is the smartest long-term investment, ensuring you receive the latest features and security patches well into the future.</p>
        </div>
        <div class="use-case">
            <h3>Best for Photography Under 150,000</h3>
            <p>The Vivo X90 Pro with its Zeiss-engineered 1-inch sensor delivers professional-grade low-light photography and versatile zoom capabilities that rival dedicated cameras.</p>
        </div>
        <div class="use-case">
            <h3>Best for Performance Under 150,000</h3>
            <p>The Oppo Reno 12 Pro and Xiaomi 13, both powered by Dimensity 9300 and Snapdragon 8 Gen 2 respectively, deliver the fastest possible performance at this price point.</p>
        </div>
    </div>
</section>
<section id="pricing">
    <h2>Pricing and Availability</h2>
    <ul>
        <li><strong>Samsung Galaxy S24 FE:</strong> PKR 139,999 – Available on <a href="<?= url('mobiles/samsung/samsung-galaxy-s24-fe/'); ?>">Phones Dukan</a></li>
        <li><strong>Samsung Galaxy S24:</strong> PKR 149,999 – Available on <a href="<?= url('mobiles/samsung/samsung-galaxy-s24/'); ?>">Phones Dukan</a></li>
        <li><strong>Xiaomi 13:</strong> PKR 139,999 – Available on <a href="<?= url('mobiles/xiaomi/xiaomi-13/'); ?>">Phones Dukan</a></li>
    </ul>
</section>
<section id="reviews">
    <h2>Reviews and User Feedback</h2>
    <p><strong>Samsung Galaxy S24:</strong> Users are impressed by Galaxy AI features, the compact yet powerful form factor, and the reassurance of 7 years of software updates. The Snapdragon 8 Gen 3 delivers exceptional performance for any task.</p>
    <p><strong>Vivo X90 Pro:</strong> Photography enthusiasts praise the Zeiss-engineered camera system, particularly its unmatched low-light performance thanks to the large 1-inch sensor. The premium build and fast charging are also frequently highlighted.</p>
</section>
<section id="faq">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-item">
        <h3>What is the best mobile under 150,000 in Pakistan?</h3>
        <p>The Samsung Galaxy S24 at PKR 149,999 is the best overall choice, offering Snapdragon 8 Gen 3 performance, 7 years of OS updates, Galaxy AI features, and IP68 protection — the most future-proof phone in this segment.</p>
    </div>
    <div class="faq-item">
        <h3>Which phone has the best camera under 150,000 in Pakistan?</h3>
        <p>The Vivo X90 Pro with its Zeiss-engineered 1-inch camera sensor delivers exceptional low-light photography and zoom capabilities, making it the top camera phone under 150,000 PKR.</p>
    </div>
    <div class="faq-item">
        <h3>Is the Samsung Galaxy S24 available under 150,000 in Pakistan?</h3>
        <p>Yes, the Samsung Galaxy S24 is available at PKR 149,999 at Phones Dukan, offering true flagship Galaxy features including Galaxy AI, IP68 water resistance, and a 7-year update guarantee.</p>
    </div>
</section>
<p class="p-note"><em>This page is updated monthly to provide the latest information on flagship smartphones costing less than 150,000 PKR.</em></p>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"What is the best mobile under 150,000 in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"The Samsung Galaxy S24 at PKR 149,999 offers Snapdragon 8 Gen 3, 7 years of OS updates, Galaxy AI features, and IP68 protection."}},{"@type":"Question","name":"Which phone has the best camera under 150,000 in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"The Vivo X90 Pro with its Zeiss-engineered 1-inch camera sensor delivers exceptional low-light photography and zoom capabilities."}},{"@type":"Question","name":"Is the Samsung Galaxy S24 available under 150,000 in Pakistan?","acceptedAnswer":{"@type":"Answer","text":"Yes, the Samsung Galaxy S24 is available at PKR 149,999 at Phones Dukan, with Galaxy AI, IP68, and a 7-year update guarantee."}}]}
</script>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
</div>
