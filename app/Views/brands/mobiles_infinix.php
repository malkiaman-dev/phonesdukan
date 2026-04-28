<?php
$pageTitle = "Infinix Mobile Price in Pakistan " . date('F Y') . " – Latest & Best Deals";
$metaDescription = "Explore Infinix mobile prices in Pakistan for " . date('F Y') . " at Phones Dukan. Find top models, features, and discounts. Prices starting from 15,000 PKR!";
$metaKeywords = "Infinix mobiles Pakistan, Infinix lowest prices, lowest prices for Infinix mobiles, Infinix mobiles prices in Pakistan, Infinix mobile specifications, Infinix mobile features";
$metaRobots = "index, follow"; // Optional; default is good
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';

// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Check if connection is established
if (!$conn) {
    die("Database connection error.");
}

// Set brand slug for "Infinix"
$brand = "infinix"; 

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
        <h1><span>Infinix Mobile</span> Prices in Pakistan - <?php echo date('F Y'); ?></h1>
        <p>Infinix mobile prices in Pakistan start at just <span>22,999 PKR</span>, updated daily for <span><?php echo date('F Y'); ?></span> at Phones Dukan. We offer the latest models at the best rates, along with fast delivery and 100% original products. If you're looking for a high-performance smartphone or a phone that's affordable, our Infinix mobile selection has something to offer everyone. Shop today to take advantage of amazing deals and exclusive discounts!</p>
    </div>
</div>
<?php include_once(__DIR__ . '/../ad/feed1.php'); ?>
<div class="product-section">
    <div class="category-header">
        <h2>Infinix <span>Mobiles</span></h2>
    </div>

    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php if (!empty($products)) : ?>
                <?php foreach ($products as &$product) : 
                    // Process product data
                    $product['regular_price'] = floatval($product['regular_price']);
                    $product['sale_price'] = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                    $product['stock_quantity'] = intval($product['stock_quantity']);
                    $product['is_sold_out'] = ($product['stock_quantity'] <= 0);
                    $product['is_on_sale'] = ($product['stock_quantity'] > 0 && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);

                    // Construct product URL
                    $product_url = "/" . htmlspecialchars($product['category_slug']) . "/" .
                                   htmlspecialchars($product['brand_slug']) . "/" .
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
            <?php else : ?>
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

        for ($i = 1; $i <= $total_pages; $i++) : ?>
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
<h2>Infinix is the <span>Right Choice for You</span></h2>
    <p>In Pakistan, Infinix quickly achieved a reputation for producing reasonably priced smartphones. Infinix smartphones are perfect for people who want modern features at an affordable price since they combine stylish design with creative functionality.</p>
    <h3>Here’s why Infinix stands out:</h3>
    <ul>
        <li><strong>Affordability Without Compromise:</strong> Infinix provides premium features like AI-powered cameras and large displays at prices starting as low as <span>PKR 15,000</span>.
        </li>
        <li><strong>Durability and Quality:</strong>Infinix phones are designed for durability and everyday use, thanks to their durable manufacturing and long-lasting batteries.
        </li>
        <li><strong>Innovative Technology</strong>For smartphone lovers, Infinix is a great option because of features like modern gaming CPUs and AMOLED screens.
        </li>
    </ul>
    <h2>Compare <span>Infinix Models</span></h2>
    <p><a href="https://www.phonesdukan.com">Phones Dukan</a> offers a large selection of mobile phones together with full information about their features, specs, and costs, making buying easy. This helps you in selecting the best mobile phone for your requirements and price range.</p>
    <h3>You can explore models like:</h3>
    <ul>
        <li><strong>Infinix GT 20 Pro:</strong>Ideal for photographers with its 108 MP + 2 MP + 2 MP camera and fast charging capabilities.</li>
        <li><strong>Infinix Hot 50 Pro:</strong>A budget-friendly option offering smooth performance and large (5000mAh) battery life.</li>
        <li><strong>Infinix Note 40 Pro Plus:</strong>Perfect for gamers with its MediaTek processor and AMOLED display.</li>
    </ul>
    <p>For more options, check out the <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a>, <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, and <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-50000/">Best Mobiles Under 50000</a> to find the perfect match.</p>
    <h2>Explore More About <span>Infinix Mobile Prices in Pakistan</span></h2>
<p>Pakistan's mobile phone market has always been active. Infinix leads the pack by selling smartphones at various price points. Phones Dukan offers Infinix mobile in Pakistan ranging from <span>PKR 15,000</span> to <span>PKR 60,000</span>, making them affordable to students, professionals, and families alike.</p>
<p>It's more important than ever to keep up with the most recent price trends because mobile phone prices have been increasing. It is expected that mobile phone prices in Pakistan would increase soon due to changes in import regulations and taxes. However, in order to give you the greatest value for your money, Phones Dukan is dedicated to providing the most competitive pricing.</p>
<h2>Why Buy Infinix Mobiles from <span> Phones Dukan</span>?</h2>
<ul>
    <li><strong>Authenticity Guaranteed:</strong> Each product comes with a verified warranty to ensure customer satisfaction.</li>
    <li><strong>Best Prices:</strong> Our platform ensures that you always pay a fair price.</li>
    <li><strong>Nationwide Delivery:</strong> Whether you're in Islamabad, Karachi, Lahore, peshawar or a small town, we deliver across Pakistan.</li>
    <li><strong>Exclusive Deals:</strong> Look out for seasonal sales and discounts on Infinix smartphones.</li>
    <li><strong>Trusted Support:</strong> Our experienced team is available to assist with any queries about your purchase.</li>
</ul>
<h2>Popular <span>Infinix Models</span></h2>
<p>Infinix offers a range of smartphones designed to cater to different user needs, from budget-friendly options to high-performance devices.</p>
<h3>Infinix Zero Series</h3>
<p>With premium features like fast charging, AMOLED panels, and refined styling, this series is perfect for professionals.</p>
<h3>Infinix Hot Series</h3>
<p>The Hot series is full of features, affordable, and has acceptable cameras and a long battery life, making it ideal for students and beginner smartphone users.</p>
<h3>Infinix Note Series</h3>
<p>For gamers and people who multitask, the Note series has larger screens, improved performance, and more lasting batteries.</p>
<p>Every series offers a wide variety of options that meet every lifestyle, while also keeping user demands in mind.</p>
<h2>Final <span>Decision</span></h2>
<p>Infinix mobiles combine low price, style, and creativity to change the smartphone experience in Pakistan. Phones Dukan is proud to be your primary resource for all mobile. We have all you need, whether you're searching for Pakistani smartphone prices or more specialised options like the <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000.</a></p>
<p>Start your Infinix journey now to find out why lots of people depend on Phones Dukan for all of their mobile needs. Find the perfect Infinix phone for you by looking through our selection now!</p>
<div class="faq-container">
<h2>Frequently Asked Questions About <span>Infinix Mobile Prices in Pakistan</span></h2>
<p>Get answers to common queries about Infinix mobile prices, features, and availability in Pakistan.</p>

<div class="faq-item">
    <h3 class="faq-question">What is the price range of Infinix mobile phones in Pakistan?</h3>
    <p class="faq-answer">In Pakistan, Infinix provides a broad variety of smartphones that fit different price ranges. Beginning versions start at <span>PKR 15,000</span>, while elite mobiles can cost up to <span>PKR 234,999</span>.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">What are the latest Infinix mobile models available in Pakistan?</h3>
    <p class="faq-answer">As of 2025, some of the latest Infinix models available in Pakistan include:</p>
    <ul class="faq-answer">
        <li>Infinix GT 20 Pro: Priced at <span>PKR 99,999.00</span>.</li>
        <li>Infinix Zero 40 4G: Priced at <span>PKR 65,999</span>.</li>
        <li>Infinix Hot 50 Pro: Priced at <span>PKR 42,999</span>.</li>
        <li>Infinix Note 40 Pro: Priced at <span>PKR 69,999</span>.</li>
    </ul>
</div>

<div class="faq-item">
    <h3 class="faq-question">Where can I purchase Infinix Mobile Phones in Pakistan?</h3>
    <p class="faq-answer">Infinix smartphones are available through various online retailers and physical stores across Pakistan. Some popular online platforms include Phonesdukan.com.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Are Infinix Mobile Phones Available With Official Warranties In Pakistan?</h3>
    <p class="faq-answer">Yes, Infinix mobile phones are available with official warranties in Pakistan. Purchasing from authorized retailers ensures that you receive genuine products along with warranty coverage.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Do Infinix Mobile Phones Support 5G Connectivity in Pakistan?</h3>
    <p class="faq-answer">Yes, certain Infinix models, such as the Infinix GT 20 Pro, support 5G connectivity, offering faster internet speeds where 5G networks are available.</p>
</div>

<div class="faq-item">
    <h3 class="faq-question">Are There Any Budget-Friendly Infinix Models Available?</h3>
    <p class="faq-answer">Yes, Infinix offers budget-friendly models such as the Infinix Smart 9 HD, priced at <span>PKR 22,999</span>, and the Infinix Smart 7 HD, priced at <span>PKR 17,999</span>, catering to users seeking affordable options.</p>
</div>

    <em>Please note that prices and availability are subject to change. It's advisable to verify the latest information from official retailers or the Infinix Pakistan website before making a purchase.</em>
</div>
</div>
<p class="p-note">Looking for the best Infinix mobile? Explore the latest Infinix smartphones at <a href="https://www.phonesdukan.com" class="cta-button">Phones Dukan</a> and find the perfect match at unbeatable prices!</p>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is the price range of Infinix mobile phones in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "In Pakistan, Infinix provides a broad variety of smartphones that fit different price ranges. Entry-level models start at PKR 15,000, while premium options can cost up to PKR 234,999."
      }
    },
    {
      "@type": "Question",
      "name": "What are the latest Infinix mobile models available in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "As of December 2024, the latest Infinix models in Pakistan include: Infinix GT 20 Pro (PKR 99,999), Infinix Zero 40 4G (PKR 65,999), Infinix Hot 50 Pro (PKR 42,999), and Infinix Note 40 Pro (PKR 69,999)."
      }
    },
    {
      "@type": "Question",
      "name": "Where can I purchase Infinix mobile phones in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Infinix smartphones are available through various online retailers and physical stores in Pakistan. Popular online platforms include Phonesdukan.com, which offers a wide range of Infinix mobiles."
      }
    },
    {
      "@type": "Question",
      "name": "Are Infinix mobile phones available with official warranties in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, Infinix mobile phones come with official warranties in Pakistan when purchased from authorized retailers."
      }
    },
    {
      "@type": "Question",
      "name": "Do Infinix mobile phones support 5G connectivity in Pakistan?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, certain Infinix models, such as the Infinix GT 20 Pro, support 5G connectivity, offering faster internet speeds in areas with 5G coverage."
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