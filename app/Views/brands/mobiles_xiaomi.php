<?php
$pageTitle = "Xiaomi Mobile Prices in Pakistan -  " . date('F Y');
$metaDescription = "Discover Xiaomi mobiles prices in Pakistan starting at PKR 39,999. Compare features, prices, and updates for " . date('F Y') . " to find the best deals at Phones Dukan.";
$metaKeywords = "Xiaomi mobiles Pakistan, Xiaomi lowest prices, lowest prices for Xiaomi mobiles, Xiaomi mobiles prices in Pakistan, Xiaomi mobile specifications, Xiaomi mobile features";
$metaRobots = "index, follow";
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Check if connection is established
if (!$conn) {
    die('Database connection error.');
}

// Set brand slug for "Xiaomi"
$brand = 'xiaomi';

// Pagination setup
$limit = 8;
$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
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
    <div class="mobile-price-info">
        <h1><span>Xiaomi Mobile</span> Prices in Pakistan - <?php echo date('F Y'); ?></h1>
        <p>Check out the current Xiaomi mobile prices in Pakistan, starting at <span>PKR 39,999</span>, with the latest updates for <span><?php echo date('F Y'); ?></span>, and pick the ideal phone for your requirements. Xiaomi is a popular choice for modern, affordable cell phones in Pakistan. Xiaomi has something for everyone, whether you're a student, a professional, or just searching for a great deal at Phones Dukan.</p>
    </div>
</div>
<?php include_once(__DIR__ . '/../ad/display1.php'); ?>
<div class="product-section">
    <div class="category-header">
        <h2>Xiaomi <span>Mobiles</span></h2>
    </div>
    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php if (!empty($products)): ?>
                <?php
                foreach ($products as &$product):
                    // Process product data
                    $product['regular_price'] = floatval($product['regular_price']);
                    $product['sale_price'] = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                    $product['stock_quantity'] = intval($product['stock_quantity']);
                    $product['is_sold_out'] = ($product['stock_quantity'] <= 0);
                    $product['is_on_sale'] = ($product['stock_quantity'] > 0 && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);

                    // Construct product URL
                    $product_url = '/' . htmlspecialchars($product['category_slug']) . '/' .
                                   htmlspecialchars($product['brand_slug']) . '/' .
                                   htmlspecialchars($product['product_slug']);

                    // Set product image (fallback if empty)
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg';
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
                            <?php
                            // Check if sale_price exists and product is on sale
                            if ($product['is_on_sale']) {
                                echo '<span class="r-regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> ';
                                echo '<span class="r-sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                            } else {
                                echo '<span class="r-regular-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found in this brand.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php include_once(__DIR__ . '/../ad/display2.php'); ?>
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
    <?php include_once(__DIR__ . '/../ad/display1.php'); ?>
<div class="content-container">
<h2>Why <span>Xiaomi is the Go-To Brand</span> for Many Pakistani Consumers</h2>
<p>Mobile phones are now essential due to the advancements in communication equipment. As a result, most individuals use cellphones to do various things, remain in contact with friends and family, and—above all—find information. Selecting a phone that is trustworthy, inexpensive, and capable of carrying out the necessary tasks is important in a nation like Pakistan where mobile phones are used for both business and communication.</p>
<p>Xiaomi has provided a variety of smartphones for the various individual categories in order to close this gap. Since they are well recognised for spending heavily in providing their clients with high-quality features, they have gained a lot of customers who are careful about their finances yet do not want to sacrifice performance.</p>
<h2>What Affects <span>Xiaomi Mobile Prices</span> in Pakistan?</h2>
<p>Before diving into the details of Xiaomi mobile prices in Pakistan, it's important to understand the factors that affect the cost of a mobile device. These include:</p>
<ul>
    <li><b>Exchange Rates:</b> The value of the Pakistani Rupee (PKR) against foreign currencies, particularly the US Dollar, plays a significant role in determining the price of imported goods, including smartphones.</li>
    <li><b>Import Taxes and Duties:</b> The Pakistani government imposes taxes and duties on imported goods, which can increase the overall price of mobile phones.</li>
    <li><b>Model and Features:</b> Different Xiaomi models come with varying features such as camera quality, processing power, battery life, and screen size, all of which impact the price</li>
    <li><b>Market Competition:</b> Competition between mobile brands in Pakistan, including local players like QMobile and international giants like Samsung and Huawei, can influence Xiaomi's pricing strategy to remain competitive.</li>
</ul>
<h2>Top <span>Xiaomi Mobiles</span> in Pakistan: Price Range and Features</h2>
<p>With its affordable devices and premium features, Xiaomi has established a strong presence in the Pakistani market. In this chapter, we'll talk about some of the cheapest Xiaomi smartphone models and their costs.</p>
<p>One of the greatest phones on the market for those who invest in basic devices is the Xiaomi 12 Pro 5G. Great camera quality, advanced performance, and easy and fast charging are all features of this phone. Excellent for resources in the market who are ready to use the newest technologies.</p>
<h2><span>Xiaomi Mobiles</span> vs Competitors: A Better Deal?</h2>
<p>When compared to smartphones from Samsung, Huawei, and Oppo, Xiaomi phones provide the ideal balance of price and performance. Even with Xiaomi, those with low incomes seeking a superb mobile experience would be good to choose it because some of its features would be too expensive with competitors.</p>
<p>For example, the Xiaomi Redmi Note 12 in Pakistan offers a better screen and a quicker CPU for around the same price as other similar smartphones. Xiaomi will therefore soon rank among the most affordable choices in Pakistan's competitive mobile industry.</p>
<h2>Why Choose <span>Xiaomi Mobiles</span> in Pakistan?</h2><ul>
    <li><b>Affordable Prices:</b>Xiaomi’s price range is designed to cater to a wide variety of consumers. Whether you’re on a tight budget or looking for a high-end device, Xiaomi has a solution for you.</li>
    <li><b>Reliable Performance:</b>With the latest processors, high-quality cameras, and long-lasting batteries, Xiaomi smartphones offer performance that can rival more expensive brands.</li>
    <li><b>Wide Availability:</b>Xiaomi smartphones are widely available across Pakistan, both online and in physical stores. This makes it easy for consumers to find and purchase their desired model.</li>
    <li><b>After-Sales Service:</b>Xiaomi has a solid reputation for providing reliable after-sales service, with authorized service centers available in major cities across Pakistan.</li>
</ul>
<h2>Popular <span>Xiaomi Models</span></h2>
<h3>Xiaomi Mi Series</h3>
<p>The Mi series by Xiaomi is renowned for offering high-end features at competitive prices. With powerful processors, large AMOLED displays, and rapid charging capabilities, this series delivers a premium experience for users who demand the best performance without breaking the bank. It's perfect for professionals and tech enthusiasts who seek reliability and speed in their daily tasks</p>
<h3>Xiaomi Redmi Series</h3>
<p>The Redmi series is made for users on a cheap price without compromising necessary features. The Redmi series is perfect for students and first-time users who appreciate affordability because of its strong performance, huge batteries, and reliable cameras. It is the preferred option for daily smartphone use due to its durability and clear interface.</p>
<h3>Xiaomi Poco Series</h3>
<p>The amazing gaming capabilities, quick processors, and high-refresh-rate screens of the Poco series make it stand out for gamers and heavy multitasking individuals. Poco phones guarantee smooth gaming experiences and long battery life thanks to its excellent specs at a reasonable price range. The Poco series is a great option for streaming, gaming, and multitasking.</p>
<h3>Xiaomi Note Series</h3>
<p>The Xiaomi Note series is designed for buyers wanting a blend of performance and style. With larger screens, powerful chipsets, and outstanding cameras, the Note series is ideal for buyers who enjoy watching content, multitasking, and taking amazing images. The series also has a longer battery life, making it an ideal alternative for customers who are constantly on the move.</p>
<h2>Mobile Price Trends in Pakistan: <span>What You Need to Know</span></h2>
<p>It's important to keep up with the latest pricing trends because cell phone prices in Pakistan are subject to constant fluctuations owing to a variety of factors, including inflation, currency rates, and changing market dynamics.</p>
<p>Many cell phone companies, including Xiaomi, have seen price rises as a result of the ongoing worldwide chip scarcity and growing import expenses. Xiaomi's dedication to providing competitive price and value has enabled its phones to maintain their popularity among Pakistani consumers in spite of these challenges.</p>
<p>Because mobile costs are still fluctuating, it's important for customers to carefully consider different options and keep an eye on how these outside variables can affect the accessibility and cost of mobile phones in the months ahead.</p>
<h2>Conclusion: <span>The Best Tecno Mobile for You</span></h2>
<p>No matter what your budget or needs are, Xiaomi has a wide selection of mobile phones to meet your needs. The cost, performance, and durability of Xiaomi smartphones make them a good choice for Pakistanis seeking to stay connected without breaking the bank.</p>
<p>Find out more about Xiaomi smartphones and pricing on our website. There are cheap phones like the Redmi series as well as high-end alternatives like the Xiaomi 12 Pro.</p>
<p>For those looking for more options, don’t forget to explore our Mobile Price In Pakistan page for additional comparisons and deals. If you’re on a tight budget, you can also check out our guides for <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>, <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, and <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a>.</p>
<p>With Xiaomi’s reputation for affordability and quality, it’s no wonder they’re becoming a favorite among Pakistani consumers. Get the phone you need today at a price you can afford!</p>
<h2>FAQs About <span>Redmi Mobile Prices in Pakistan</span></h2>
<p>Find answers to the most common questions about Redmi mobile prices, features, and availability in Pakistan.</p>

<div class="faq-item">
    <h3 class="faq-question">What is the price of Redmi 8GB/128GB in Pakistan?</h3>
    <p class="faq-answer">As of 2025, the price of the Redmi 8GB/128GB variant in Pakistan ranges between <span>PKR 35,000</span> to <span>PKR 38,000</span>. Prices may vary based on location, promotions, and available stock.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Is the Redmi Note 12 a 5G?</h3>
    <p class="faq-answer">Yes, the Redmi Note 12 is a <span>5G-enabled smartphone</span>. It comes with next-generation network support, allowing you to enjoy fast internet speeds and improved connectivity.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">What is the price of Redmi 2025 8GB/256GB in Pakistan?</h3>
    <p class="faq-answer">The Redmi 2025 8GB/256GB variant is available for approximately <span>PKR 45,000</span> to <span>PKR 50,000</span> in Pakistan. Prices may vary depending on retailer offers and market conditions.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Which phone is the best in Xiaomi?</h3>
    <p class="faq-answer">Xiaomi offers a variety of top-performing smartphones. Currently, the <span>Xiaomi 13 Pro</span> stands out as one of the best with its advanced features, including a powerful camera system, fast processing, and premium design. However, for those on a budget, the <span>Redmi Note 12</span> also offers great value for money.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Is Xiaomi good or Redmi?</h3>
    <p class="faq-answer">Both Xiaomi and Redmi phones offer great performance, but they serve different markets. <span>Xiaomi phones</span> are more premium, featuring top-tier specs and designs. On the other hand, <span>Redmi phones</span> are known for their affordability while still offering solid performance, making them ideal for budget-conscious users.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Is Mi or Samsung better?</h3>
    <p class="faq-answer">Both <span>Mi (Xiaomi)</span> and <span>Samsung</span> offer high-quality phones. Xiaomi provides excellent value for money with cutting-edge technology at affordable prices, while Samsung is known for its premium build quality, display, and camera capabilities. The choice depends on your budget and preferred features.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Which model is best in Redmi?</h3>
    <p class="faq-answer">The <span>Redmi Note 12 series</span> is among the best in the Redmi lineup, offering great performance, a good display, and fast charging. The <span>Redmi K40</span> (also known as <span>Mi 11X</span> in some regions) is another excellent option for users seeking high-end specs at a reasonable price.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">What is the price of Redmi Note 12 8GB RAM 128GB storage in Pakistan in 2025?</h3>
    <p class="faq-answer">The Redmi Note 12 with 8GB RAM and 128GB storage is priced between <span>PKR 38,000</span> to <span>PKR 40,000</span> in Pakistan in 2025, depending on the seller and location.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Which mobile is best in Pakistan?</h3>
    <p class="faq-answer">In Pakistan, the best mobile phones in 2025 are from brands like <span>Xiaomi, Samsung, and Oppo</span>. For Xiaomi, the <span>Redmi Note 12</span> and <span>Xiaomi 13 Pro</span> are among the top choices, offering a balance of performance, camera quality, and price.</p>
</div>

<em>Prices and availability are subject to change. Please check official retailers or our website for the latest updates.</em>
</div>
<p class="p-note">Looking for the best Redmi mobile? Explore the latest Redmi smartphones at <a href="https://www.phonesdukan.com" class="cta-button">Phones Dukan</a> and get the perfect mobile at unbeatable prices!</p>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the price of Redmi 8GB/128GB in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "As of 2025, the price of the Redmi 8GB/128GB variant in Pakistan ranges between PKR 35,000 to PKR 38,000. Prices may vary based on location, promotions, and available stock."
      }
    },
    {
      "@type": "Question",
      "name": "Is the Redmi Note 12 a 5G?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, the Redmi Note 12 is a 5G-enabled smartphone. It comes with next-generation network support, allowing you to enjoy fast internet speeds and improved connectivity."
      }
    },
    {
      "@type": "Question",
      "name": "What is the price of Redmi 2025 8GB 256GB in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Redmi 2025 8GB/256GB variant is available for approximately PKR 45,000 to PKR 50,000 in Pakistan. Prices may vary depending on retailer offers and market conditions."
      }
    },
    {
      "@type": "Question",
      "name": "Which phone is the best in Xiaomi?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Xiaomi offers a variety of top-performing smartphones. Currently, the Xiaomi 13 Pro stands out as one of the best with its advanced features, including a powerful camera system, fast processing, and premium design. However, for those on a budget, the Redmi Note 12 also offers great value for money."
      }
    },
    {
      "@type": "Question",
      "name": "Is Xiaomi good or Redmi?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Both Xiaomi and Redmi phones offer great performance, but they serve different markets. Xiaomi phones are more premium, featuring top-tier specs and designs. On the other hand, Redmi phones are known for their affordability while still offering solid performance, making them ideal for budget-conscious users."
      }
    },
    {
      "@type": "Question",
      "name": "Is Mi or Samsung better?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Both Mi (Xiaomi) and Samsung offer high-quality phones. Xiaomi provides excellent value for money with cutting-edge technology at affordable prices, while Samsung is known for its premium build quality, display, and camera capabilities. The choice depends on your budget and preferred features."
      }
    },
    {
      "@type": "Question",
      "name": "Which model is best in Redmi?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Redmi Note 12 series is among the best in the Redmi lineup, offering great performance, a good display, and fast charging. The Redmi K40 (also known as Mi 11X in some regions) is another excellent option for users seeking high-end specs at a reasonable price."
      }
    },
    {
      "@type": "Question",
      "name": "What is the price of Redmi Note 12 8GB RAM 128GB storage in Pakistan in 2025?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Redmi Note 12 with 8GB RAM and 128GB storage is priced between PKR 38,000 to PKR 40,000 in Pakistan in 2025, depending on the seller and location."
      }
    },
    {
      "@type": "Question",
      "name": "Which mobile is best in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "In Pakistan, the best mobile phones in 2025 are from brands like Xiaomi, Samsung, and Oppo. For Xiaomi, the Redmi Note 12 and Xiaomi 13 Pro are among the top choices, offering a balance of performance, camera quality, and price."
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