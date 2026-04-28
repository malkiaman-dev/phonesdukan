<?php
$pageTitle = "Vivo Mobile Price in Pakistan  " . date('F Y') . " - Latest Deals & Offers";
$metaDescription = "Check Vivo mobile price in Pakistan at Phones Dukan. Get the latest deals and fast delivery in cities like Islamabad, Lahore, Karachi, and more.";
$metaKeywords = "Vivo mobiles Pakistan, Vivo lowest prices, lowest prices for Vivo mobiles, Vivo mobiles prices in Pakistan, Vivo mobile specifications, Vivo mobile features";
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

// Set brand slug for "Vivo"
$brand = 'vivo';

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
        <h1><span>Vivo Mobile</span> Prices in Pakistan - <?php echo date('F Y'); ?></h1>
        <p>Looking for the best deals on Vivo mobiles in Pakistan? At Phones Dukan, you can get the latest Vivo smartphones starting from just <span>21,999 PKR</span>, with prices updated daily for <span><?php echo date('F Y'); ?></span>. We offer genuine Vivo mobile phones, fast delivery, and exclusive discounts, so you always get the best value for your money. Whether you love gaming, photography, or need a reliable phone for daily use, we have the perfect Vivo mobile for you.</p>
    </div>
</div>
<?php include_once(__DIR__ . '/../ad/feed1.php'); ?>
<div class="product-section">
    <div class="category-header">
        <h2>Vivo <span>Mobiles</span></h2>
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
<h2>Vivo <span>Mobile Prices in Pakistan - February 2025</span></h2>
    <ul>
        <li><b>Value-Packed Features for Every Budget:</b> Vivo is famous for offering premium features at a reasonable cost. Vivo believes that with features like powerful CPUs, bright screens, and AI-enhanced cameras, even low-cost phones provide an outstanding user experience.
For example, Vivo's V series is for people who look for phones with outstanding performance and cameras, while the Y series offers affordable phones with long-lasting batteries.</li>
<li><b>Creative Camera Technology:</b> If you're passionate about photography, Vivo specialises in providing modern camera functionality. Many Vivo phones, like the Vivo V27, have features like video stabilisation, night photography, and portrait mode that guarantee you can shoot photos with outstanding quality.</li>
<li><b>Competitive Pricing in Pakistan:</b>Many various different kinds of Pakistani mobile buyers could prefer Vivo mobiles since they are made to be fairly priced. Vivo offers a wide choice of items to fit your needs, whether you're looking for the finest mobile phone or more economical solutions. Take a look at these suggestions based on expected pricing points:</li>
<ul>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000">Best Mobiles Under 30000</a></li>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000">Best Mobiles Under 40000</a></li>
    <li><a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a></li>
</ul>
<li><b>Durable and Stylish Designs:</b> Vivo phones focus on being strong and stylish. They are made with good materials, bright colors, and slim designs to stand out.</li>
    </ul>
    <h2>Tecno <span>Mobile Prices - Trends and Insights</span></h2>
<p>
Vivo mobile phones in Pakistan range in price from <span>PKR 150,000</span> to <span>20,000</span>, making them accessible to a wide range of people. According to a recent study, rising import taxes and changing exchange rates caused fluctuations in smartphone pricing.</p>
<h2>Tecno <span>Mobile Price in Pakistan - Compare the Best Deals</span></h2>
<p>It can be tough to find the best Vivo phone as there are a lot of them available. Phones Dukan has a website that assists you in determining how to choose a Vivo phone by allowing users to compare Vivo mobiles. You will notice that there are several models available and you can select the appropriate phone by checking its features, specifics, and cost.</p>
<h3>Comparison Made Easy at Phones Dukan</h3>
<p>Selecting the best option might be challenging if there are too many options. Phones Dukan provides features for comparing Vivo mobile phones. You can make decisions with confidence when you compare features, specifications, and prices side by side.</p>
<h3>Why Choose Phones Dukan for Your Vivo Mobile?</h3>
<p>We focus on making your shopping experience great. Here's why thousands of customers choose us for their mobile phone needs:</p>
<ul>
    <li><b>Authenticity Guaranteed:</b>All Vivo mobiles sold through our platform are 100% original and come with a company warranty.</li>
    <li><b>Up-to-Date Pricing:</b>We ensure real-time price updates so you always know the best deals.</li>
    <li><b>Customer-Centric Services:</b> Our user-friendly interface and detailed product information make shopping effortless.</li>
</ul>
<h2>Final Thoughts on <span>Samsung Mobiles</span></h2>
<p>Vivo phones are popular in Pakistan because they are reliable, affordable, and have cool features. If you're a student searching for a reliable mobile or a qualified IT expert wanting advanced features, Vivo's selection might meet all of your needs.</p>
<p>For the best deals and to check real-time updates on <a href="https://www.phonesdukan.com/mobiles/">Mobile Prices in Pakistan</a>, be sure to visit Phones Dukan.</p>
<div class="faq-container">
<h2>FAQs About <span>Vivo Mobile Prices in Pakistan</span></h2>
<p>Find answers to the most common questions about Vivo mobile prices, features, and availability in Pakistan.</p>

<div class="faq-item">
    <h3 class="faq-question">What is the price range of Vivo mobiles in Pakistan?</h3>
    <p class="faq-answer">Vivo offers smartphones in a wide price range in Pakistan. Entry-level models start from around <span>PKR 20,000</span>, while premium models can go up to approximately <span>PKR 150,000</span>. Whether you're looking for an affordable phone or a high-end device, Vivo has something for everyone.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Which Vivo mobile is the best under PKR 40,000?</h3>
    <p class="faq-answer">A popular option under <span>PKR 40,000</span> is the <a href="https://www.phonesdukan.com/mobiles/vivo/vivo-y19s/">Vivo Y19s</a>. At just <span>PKR 38,999</span>, it offers an excellent combination of features and performance, making it a wonderful choice for buyers on a budget.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Can I buy Vivo smartphones online in Pakistan?</h3>
    <p class="faq-answer">Yes, Vivo smartphones are available for purchase online in Pakistan through various platforms, including the official Vivo website and trusted online retailers. These platforms offer the convenience of home delivery and exclusive online discounts.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">What are the features of the Vivo V40e?</h3>
    <p class="faq-answer">The <a href="https://www.phonesdukan.com/mobiles/vivo/vivo-v40e/">Vivo V40e</a>, priced at <span>PKR 92,999</span>, is packed with great features. It offers a stunning <span>6.77-inch AMOLED display</span> with a <span>120Hz refresh rate</span>, a powerful <span>MediaTek Dimensity processor</span>, <span>8GB RAM</span>, and a <span>5000mAh battery</span> with fast charging. It also has a <span>triple rear camera setup</span>, including a <span>50MP primary sensor</span>.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">How does the Vivo Y37 compare to other models in terms of pricing?</h3>
    <p class="faq-answer">The <span>Vivo Y37</span> is priced at <span>PKR 82,999</span>, making it a mid-range option. It offers a solid combination of features, ideal for users who want more than an entry-level smartphone but aren't looking for a flagship model.</p>
</div>

<em>Prices and availability are subject to change. Please check official retailers or our website for the latest updates.</em>
</div>
</div>
<p class="p-note">Looking for the best Vivo mobile? Explore the latest Vivo smartphones at <a href="https://www.phonesdukan.com" class="cta-button">Phones Dukan</a> and get the perfect mobile at unbeatable prices!</p>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the price range of Vivo mobiles in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Vivo offers smartphones in a wide price range in Pakistan. Entry-level models start from around PKR 20,000, while premium models can go up to approximately PKR 150,000. Whether you're looking for an affordable phone or a high-end device, Vivo has something for everyone."
      }
    },
    {
      "@type": "Question",
      "name": "Which Vivo mobile is the best under PKR 40,000?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "A popular option under PKR 40,000 is the Vivo Y19s. At just PKR 38,999, it offers an excellent combination of features and performance, making it a wonderful choice for buyers on limited funds."
      }
    },
    {
      "@type": "Question",
      "name": "Can I buy Vivo smartphones online in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Vivo smartphones are available for purchase online in Pakistan through various platforms, including the official Vivo website and trusted online retailers. These platforms offer the convenience of home delivery and exclusive online discounts."
      }
    },
    {
      "@type": "Question",
      "name": "What are the features of the Vivo V40e?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Vivo V40e, priced at PKR 92,999, is packed with great features. It offers a stunning 6.77-inch AMOLED display with a 120Hz refresh rate, a powerful MediaTek Dimensity processor, 8GB RAM, and a 5000mAh battery with fast charging. It also has a triple rear camera setup, including a 50MP primary sensor."
      }
    },
    {
      "@type": "Question",
      "name": "How does the Vivo Y37 compare to other models in terms of pricing?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The Vivo Y37 is priced at PKR 82,999, making it a mid-range option. It offers a solid combination of features, ideal for users who want more than an entry-level smartphone but aren't looking for a flagship model."
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
