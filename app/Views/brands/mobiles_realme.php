<?php
$pageTitle = "Realme Mobile Price in Pakistan  " . date('F Y') . " - Latest Realme Phones";
$metaDescription = "Discover the latest Realme mobile price in Pakistan for  " . date('F Y') . ", starting at PKR 23,500. Shop feature-packed phones at Phones Dukan today!";
$metaKeywords = "Realme mobiles Pakistan, Realme lowest prices, lowest prices for Realme mobiles, Realme mobiles prices in Pakistan, Realme mobile specifications, Realme mobile features";
$metaRobots = "index, follow"; // Optional; default is good
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Check if connection is established
if (!$conn) {
    die('Database connection error.');
}

// Set brand slug for "Infinix"
$brand = 'realme';

// Pagination setup
$limit = 8;
$paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
$offset = ($paged - 1) * $limit;

// Fetch products using prepared statement
$query = "SELECT p.product_slug, p.product_name, p.regular_price, p.sale_price, p.stock_quantity,
                 pi.image_url, b.slug AS brand_slug, c.slug AS category_slug
          FROM products p
          JOIN brands b ON p.brand_id = b.brand_id
          JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
          WHERE c.slug = 'mobiles' AND b.slug = :brand AND p.product_status != '0'
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':brand', $brand, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="mobiles-section">
    <!-- New SEO and user-friendly content before the product grid -->
    <div class="mobile-price-info">
        <h1><span>Realme Mobile</span> Price In Pakistan <?php echo date('F Y'); ?></h1>
        <p>Discover the latest Realme mobile prices in Pakistan, starting at just <span>PKR 23,500</span> <span>(<?php echo date('F Y'); ?>)</span>. Combining innovation with style, Realme offers something for everyone. Explore top deals now at Phones Dukan!</p>
</div>
</div>
<?php include_once(__DIR__ . '/../ad/feed1.php'); ?>
<div class="product-section">
    <div class="category-header">
        <h2>Realme <span>Mobiles</span></h2>
    </div>
    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php if (!empty($products)): ?>
                <?php
                foreach ($products as $product):
                                      // Process product data
                                      $product['regular_price'] = floatval($product['regular_price']);
                                      $product['sale_price'] = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                                      $product['stock_quantity'] = intval($product['stock_quantity']);
                                      $product['is_sold_out'] = ($product['stock_quantity'] <= 0);
                                      $product['is_on_sale'] = ($product['stock_quantity'] > 0 && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);
                    // Construct product URL
                    $product_url = '/' . htmlspecialchars($product['category_slug']) . '/'
                        . htmlspecialchars($product['brand_slug']) . '/'
                        . htmlspecialchars($product['product_slug']);

                    // Set product image (fallback if empty)
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg';

                    // Format product price
                    $product_price = (!empty($product['sale_price']))
                        ? '<del>Rs. ' . number_format($product['regular_price']) . '</del> Rs. ' . number_format($product['sale_price'])
                        : 'Rs. ' . number_format($product['regular_price']);
                    ?>
                    <div class="mobile-product-card">
                    <div class="tagwrap">
                            <?php if ($product['is_sold_out']): ?>
                                <div class="sold-out">
                                    <span>Sold Out</span>
                                </div>
                            <?php elseif ($product['is_on_sale']): ?>
                                <div class="pro-tags">
                                    <span>Sale</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo $product_url; ?>">
                            <div class="mobile-product-img-wrapper">
                                <div class="mobile-product-img">
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </div>
                            </div>
                        </a>
                        <h3 class="mobile-product-title">
                            <a href="<?php echo $product_url; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </a>
                        </h3>
                        <span class="prodline"></span>
                        <div class="mobile-product-price">
                            <span><?php echo $product_price; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found in this brand.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once(__DIR__ . '/../ad/feed2.php'); ?>
    <!-- Pagination -->
    <div class="pagination">
        <?php
        // Count total products
        $count_sql = "SELECT COUNT(*) as total FROM products p
                      JOIN brands b ON p.brand_id = b.brand_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE c.slug = 'mobiles' AND b.slug = :brand";
        $stmt_count = $conn->prepare($count_sql);
        $stmt_count->bindValue(':brand', $brand, PDO::PARAM_STR);
        $stmt_count->execute();
        $total_rows = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $total_pages = ceil($total_rows / $limit);

        for ($i = 1; $i <= $total_pages; $i++):
            ?>
            <a href="?paged=<?= $i ?>" class="<?= ($i == $paged) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php 
// Close connection
$conn = null;
?>
<div class="content-container">
    <h2>Why <span>Realme Phones Are Ideal</span></h2>
<p>Realme has reached a new level of popularity in Pakistan, as it is providing a strong blend of value and features in its smartphones. Here’s why Realme is the one for you:</p>

<h3>1. Affordable Yet Efficient Devices</h3>
<p>Realme smartphones provide exceptional value for money with top performance and a multitude of features on a budget. Do not worry if you are looking for a basic smartphone or a powerful flagship; Realme has something for everyone.</p>

<h3>2. Advanced Technology</h3>
<p>Realme smartphones always contain advanced technology. From the latest processors and displays to AI-powered cameras and fast charging, Realme is thoroughly committed to providing its users with a future-ready mobile experience.</p>

<h3>3. Attractive Design and Build Quality</h3>
<p>Realme has sleek, modern designs for their phones that protrude in the market. It does not matter which model you choose; a sophisticated metal finish, glossy back, or premium glass design; Realme guarantees that every device will be as eye-catching as it is useful.</p>

<h3>4. Extended Battery Life</h3>
<p>Get ready to remain connected and productive all day thanks to the large battery inside Realme devices. You can recharge your device with considerable ease and then move back to being productive in no time.</p>

<h3>5. The Camera Does a Wonderful Job</h3>
<p>Realme put some thought into how the cameras perform. Most devices by Realme have multi-lens cameras, powered by AI to take great shots regardless of how well-lit the surroundings are. From wide-angle showers to portraits, and even night shots, Realme captures the moment beautifully.</p>

<h2>Explore <span>Realme Mobile Series</span></h2>
<p>Realme offers a range of smartphones, each designed to meet different needs:</p>

<h3>1. Affordable Models</h3>
<p>The best features these phones offer makes them affordable is Realme’s C and Narzo series. This series balances price and features very well and guarantees solid battery life, camera, and other daily activities.</p>
<ul>
    <li>Key Features: Long battery life, Big screen, Great for day-to-day use, Camera enhancement through AI.</li>
</ul>

<h3>2. Mid-Range Options</h3>
<p>For users with more demanding requirements, the mid-range phones from Realme pack fast processors, vivid screens, and advanced camera setups while staying within reasonable price limits. Perfect for gaming, photography, and multitasking.</p>
<ul>
    <li>Key Features: High-performance processors, fast refresh rate displays, solid camera setups, long-lasting battery.</li>
</ul>

<h3>3. Flagship Models</h3>
<p>Realme’s GT Series is the culmination of advancement in mobile phone technology, featuring flagship performance and breath-taking designs on the smartphone. These phones are perfect for discerning users with the latest Snapdragon processors, 5G support, high refresh rates, and advanced AI camera functionality.</p>
<ul>
    <li>Key Features: Snapdragon Premium processors, 5G support, ultra-fast charging, advanced cameras.</li>
</ul>

<h2>Why Purchase <span>Realme Smartphones at Phones Dukan?</span></h2>
<ol>
    <li><strong>Authentic Realme Products:</strong> At Phones Dukan, we sell 100% original Realme brand smartphones only. As such, do not worry, because everything you purchase from us is authentic and is backed by warranty.</li>
    <li><strong>Price Promise:</strong> Our phonesdukan.com website contains the most affordable prices for Realme smartphones in Pakistan, so you can make the most of every single penny spent. We provide across the board discount offers, website specials, and bundle specials that add to the cost-effectiveness of shopping for Realme smartphones with us.</li>
    <li><strong>Reliable and Economical Delivery:</strong> Receive your order anywhere in Pakistan with our free shipping policy. We spend considerable effort in getting your parcel delivered promptly, and we make sure that the package is well protected so that your phone is in excellent condition upon delivery.</li>
    <li><strong>Safe Purchase Process:</strong> Shopping with us is a breeze. You do not have to worry about your security as we ensure that you do not encounter any issues while browsing our site. We accept many payment methods, including cash on delivery, and bank transfer.</li>
</ol>

<h2>Key Features of <span>Realme Smartphones</span></h2>
<ul>
    <li><strong>Brilliant Screens:</strong> Realme mobile phones come with breathtaking display screen features like Full HD+ quality, AMOLED, or even a 90 Hz/120Hz refresh rate screen. During movie watching, gaming, or even simple browsing, the visuals are sharp and immersive.</li>
    <li><strong>Great Performance:</strong> Mobile devices from Realme powered by Qualcomm Snapdragon and Mediatek chipsets ensure perfect performance to all users whether you are a gamer, content creator, or a multitasker, you will experience smooth and lag-free mobile devices.</li>
    <li><strong>AI Powered Cameras:</strong> Be it daylight or low light, Realme’s AI camera technology ensures your photos are of the highest quality. AI enhanced features like portrait mode, super night mode, HDR, and others ensure you capture perfect shots every time.</li>
    <li><strong>Quick Charging:</strong> Dart Charge and VOOC Flash Charging technology enable fast charging because, with many Realme devices having 65W charging capability, you can go to places without worrying about battery life.</li>
    <li><strong>5G Compatibility:</strong> With 5g capable Realme, enjoy faster data speeds allowing you to stream, game, or browse without interruptions on the latest 5G networks, ultimately allowing you to future-proof your smartphone experience.</li>
</ul>

<h2>Realme Mobile Phones <span>Purchase Online in Pakistan</span></h2>
<p>Purchasing Realme phones in Pakistan has become effortless. Our easy-to-use website lets you check all smartphone models, evaluate their features, and place an order in a matter of minutes. We also provide our customers with top-tier support so that their experience on the web is as enjoyable as possible, paired alongside swift payment methods and rapid delivery services.</p>
<p>Are you looking to enhance your smartphone experience? Browse through our collection of Realme phones and make your purchase today!</p>
<h3>1. What is the starting price of Realme smartphones in Pakistan?</h3>
<p>Realme smartphones cost <span>PKR 23,500</span> in Pakistan, making them an inexpensive option for many customers.</p>
<div class="faq-container">
<h2>Frequently Asked Questions About <span>Realme Mobile Prices in Pakistan</span></h2>
<p>Get answers to common queries about Realme mobile prices, features, and availability in Pakistan.</p>

<div class="faq-item">
    <h3 class="faq-question">Which Realme smartphone is best for gaming?</h3>
    <p class="faq-answer">Realme's GT and Narzo series are ideal for gaming because of their strong CPUs, high refresh rate screens, and optimized performance.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Are Realme phones good for photography?</h3>
    <p class="faq-answer">Yes, Realme smartphones include AI-powered cameras, enhanced portrait settings, and night shooting capabilities, assuring high-quality photographs in any environment.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Does Realme offer 5G smartphones in Pakistan?</h3>
    <p class="faq-answer">Yes, Realme provides multiple 5G-enabled smartphones, including the Realme GT series and various Narzo series phones, giving customers ultra-fast connectivity.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Do Realme phones have good battery life?</h3>
    <p class="faq-answer">Realme smartphones have 5000mAh battery life and fast-charging features like VOOC Flash Charge and Dart Charge for rapid power-ups.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">How can I check if my Realme smartphone is original?</h3>
    <p class="faq-answer">Check the IMEI number on Realme's official website to confirm authenticity, and make sure your phone has an official warranty card.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Which Realme smartphone offers the best value for money?</h3>
    <p class="faq-answer">Both the Realme C and Narzo series are reasonably priced devices with great features including large displays, dependable cameras, and long-lasting batteries.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">What is Realme’s flagship model?</h3>
    <p class="faq-answer">The most popular Realme GT series offers 5G compatibility, ultra-fast charging, and state-of-the-art performance.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Does Realme provide software updates?</h3>
    <p class="faq-answer">Yes, Realme frequently releases security patches and Android OS updates to boost security and performance.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Where can I buy Realme smartphones in Pakistan?</h3>
    <p class="faq-answer">To make Realme phone purchases feasible, Phones Dukan ensures genuine products at affordable prices with various payment modalities, plus they offer free shipping.</p>
</div>

<em>Please note that prices and availability are subject to change. It's advisable to verify the latest information from official retailers or the Realme Pakistan website before making a purchase.</em>
</div>
</div>
<p class="p-note">Looking for the best Realme mobile? Explore the latest Realme smartphones at <a href="https://www.phonesdukan.com" class="cta-button">Phones Dukan</a> and get the perfect mobile at unbeatable prices!</p>
            <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the starting price of Realme smartphones in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Realme smartphones cost PKR 23,500 in Pakistan, making them an inexpensive option for many customers."
      }
    },
    {
      "@type": "Question",
      "name": "Which Realme smartphone is best for gaming?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Realme's GT and Narzo series are ideal for gaming because of their strong CPUs, high refresh rate screens, and optimised performance."
      }
    },
    {
      "@type": "Question",
      "name": "Are Realme phones good for photography?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Realme smartphones include AI-powered cameras, enhanced portrait settings, and night shooting capabilities, assuring high-quality photographs in any environment."
      }
    },
    {
      "@type": "Question",
      "name": "Does Realme offer 5G smartphones in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Realme provides multiple 5G-enabled smartphones, including the Realme GT series and various Narzo series phones, giving customers ultra-fast connectivity."
      }
    },
    {
      "@type": "Question",
      "name": "Do Realme phones have good battery life?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Realme smartphones have 5000mAh battery life and fast-charging features like VOOC Flash Charge and Dart Charge for rapid power-ups."
      }
    },
    {
      "@type": "Question",
      "name": "How can I check if my Realme smartphone is original?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Check the IMEI number on Realme's official website to confirm authenticity, and make sure your phone has an official warranty card."
      }
    },
    {
      "@type": "Question",
      "name": "Which Realme smartphone offers the best value for money?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Both the Realme C and Narzo series are reasonably priced devices with great features including large displays, dependable cameras, and long-lasting batteries."
      }
    },
    {
      "@type": "Question",
      "name": "What is Realme’s flagship model?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The most popular Realme GT series offers 5G compatibility, ultra-fast charging, and state-of-the-art performance."
      }
    },
    {
      "@type": "Question",
      "name": "Does Realme provide software updates?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Realme frequently releases security patches and Android OS updates to boost security and performance."
      }
    },
    {
      "@type": "Question",
      "name": "Where can I buy Realme smartphones in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "To make Realme phone purchases feasible, Phones Dukan ensures genuine products at affordable prices with various payment modalities, plus they offer free shipping."
      }
    }
  ]
}
</script>
<?php
// Close connection
$conn = null;
?>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>