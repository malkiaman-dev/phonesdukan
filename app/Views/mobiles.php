<?php
$pageTitle = "Mobile Prices in Pakistan " . date('F Y') . " – Updated Rates & Top Brands";
$metaDescription = "Explore mobile prices in Pakistan " . date('F Y') . ". Compare latest models, specs, and deals to find the perfect smartphone for you. Updated daily!";
$metaRobots = "index, follow";
$metaKeywords = "mobile mobile prices, Buy mobile Online Pakistan, mobile Pakistan, mobile at lowest price in Pakistan mobile price in pakistan smartphone";
require_once __DIR__ . '/../../database/db.php';
require_once dirname(__DIR__, 2) . '/includes/header.php';
// Create database connection instance
$database = new Database();
$conn = $database->getConnection();

// Check if connection is established
if (!$conn) {
    die("Database connection error.");
}

// Define mobile brands
$brands = array('infinix', 'oppo', 'realme', 'samsung', 'tecno', 'vivo', 'xiaomi');
?>

<div class="mobiles-section">
    <!-- Display introduction only once -->
    <div class="mobile-price-info">
        <h1><span>Mobiles Price</span> In Pakistan <?php echo date('F Y'); ?></h1>
        <p>Mobile phone prices in Pakistan are constantly changing due to taxes, exchange rates, and market demand, which makes it challenging for buyers. At Phones Dukan, we provide the most competitive mobile phone prices in Pakistan, We guarantee clarity and provide authentic products.</p>
    </div>
</div>
<?php include_once __DIR__ . '/ad/feed1.php'; ?>
<?php
foreach ($brands as $brand) :
?>
<div class="product-section">
    <div class="category-header">
        <h2><?php echo ucfirst($brand); ?> <span>Mobiles</span></h2>
        <a href="/mobiles/<?php echo $brand; ?>" class="view-all-btn">View All</a>
    </div>

    <div class="product-grid-container">
        <div class="product-grid-wrapper">
            <?php
            // Fetch mobiles category products with category_slug, brand_slug, and stock_quantity
            $query = "SELECT p.product_slug, p.product_name, p.regular_price, p.sale_price, p.stock_quantity,
            pi.image_url, b.slug AS brand_slug, c.slug AS category_slug
     FROM products p
     JOIN brands b ON p.brand_id = b.brand_id
     JOIN categories c ON p.category_id = c.category_id
     LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
     WHERE p.category_id = 2 AND b.slug = :brand AND p.product_status != '0'
     ORDER BY p.created_at DESC LIMIT 4";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(":brand", $brand);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($products)) :
                foreach ($products as &$product) :
                    // Process product data
                    $product['regular_price'] = floatval($product['regular_price']);
                    $product['sale_price'] = !empty($product['sale_price']) ? floatval($product['sale_price']) : null;
                    $product['stock_quantity'] = intval($product['stock_quantity']);
                    $product['is_sold_out'] = ($product['stock_quantity'] <= 0);
                    $product['is_on_sale'] = ($product['stock_quantity'] > 0 && $product['sale_price'] !== null && $product['sale_price'] > 0 && $product['sale_price'] < $product['regular_price']);

                    // Construct product URL with category_slug, brand_slug, and product_slug
                    $product_url = "/" . htmlspecialchars($product['category_slug']) . "/" .
                        htmlspecialchars($product['brand_slug']) . "/" .
                        htmlspecialchars($product['product_slug']);
                    
                    $product_image = !empty($product['image_url']) ? $product['image_url'] : 'default-image.jpg'; // Fallback image
                    $product_name = htmlspecialchars($product['product_name']);
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
                                    <img src="<?php echo $product_image; ?>" alt="<?php echo $product_name; ?>">
                                </div>
                            </div>
                        </a>
                        <h3 class="mobile-product-title">
                            <a href="<?php echo $product_url; ?>">
                                <?php echo $product_name; ?>
                            </a>
                        </h3>
                        <span class="prodline"></span>
                        <div class="mobile-product-price">
                            <?php
                            // Check if sale_price exists and is not empty
                            if (!empty($product['sale_price']) && $product['is_on_sale']) {
                                echo '<span class="r-regular-price old-price">Rs. ' . number_format($product['regular_price']) . '</span> ';
                                echo '<span class="r-sale-price new-price">Rs. ' . number_format($product['sale_price']) . '</span>';
                            } else {
                                echo '<span class="r-regular-price">Rs. ' . number_format($product['regular_price']) . '</span>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
        </div> <!-- End of .product-grid-wrapper -->
            <?php else : ?>
                <p>No products found in this brand.</p>
            <?php endif; ?>
    </div> <!-- End of .product-grid-container -->
    </div>
<?php endforeach; ?>
<?php include_once __DIR__ . '/ad/feed2.php'; ?>
<div class="content-container">
    <h2>Understanding the Agitation: <span>What’s Causing the Pricing Chaos?</span></h2>
<p>One of the main reasons for the uncertainty in mobile phone prices in Pakistan is the volatile exchange rate. Mobile phones are generally imported from countries like China, South Korea, and the United States, and as the Pakistani Rupee fluctuates against major currencies, the cost of importing phones increases, directly impacting retail prices.</p>
<p>Furthermore, the addition of taxes, duties, and the lack of regulatory control on prices by the government further contribute to this volatility. In fact, recent studies on mobile pricing in Pakistan highlight how the government-imposed taxes on mobile phones are a significant contributor to the price hike. These taxes are not only limited to smartphones but also to mobile accessories, making it a costly affair for consumers.</p>
<p>For instance, a recent article on mobile pricing trends in Pakistan <a href="https://profit.pakistantoday.com.pk/2024/07/26/mobile-phones-set-to-become-more-expensive-in-pakistan/">Research Article on Mobile Rates in Pakistan</a> delves deeper into how the rise in taxes and currency depreciation have forced consumers to pay a premium for their favorite devices.</p>
<p>Another factor is the rapid technological advancements in smartphones. Every year, new models are released, and with each new iteration, manufacturers increase prices due to enhanced features, better cameras, and faster processors. These factors create a gap in the market between older and newer models, leaving customers torn between getting the latest model or purchasing a budget-friendly option.</p>

<h2>The Solution: <span>Finding Affordable and Reliable Mobile Phones in Pakistan</span></h2>
<p>At Phones Dukan, we understand the challenges of navigating through fluctuating mobile phone prices. Our mission is to offer you the latest mobile phones at competitive prices, ensuring you get the best value for your money. Here’s how we provide a solution to the problem:</p>
<ul>
    <li><strong>Transparent Pricing:</strong> We offer clear pricing without hidden fees or confusing charges. Our prices are updated regularly, reflecting the latest market trends, and we ensure that you get an accurate cost for the model you choose. By shopping on Phones Dukan, you can avoid the confusion of varying prices from different retailers.</li>
    <li><strong>Wide Range of Options:</strong> Whether you’re looking for a budget-friendly phone or a premium device, we have a wide selection of smartphones to suit every need. From trusted brands like Samsung, Huawei, and Xiaomi to the latest iPhones, we ensure that our customers can find the perfect mobile phone based on their preferences and budget. If you're on a budget, explore our collection of <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-30000/">Best Mobiles Under 30000</a> to find top-rated phones at an affordable price.</li>
    <li><strong>Latest Models at Competitive Prices:</strong> Despite the rising costs of mobile phones in Pakistan, Phones Dukan remains committed to offering the most competitive prices for the latest models. Our relationships with trusted suppliers allow us to secure phones at lower prices, and we pass those savings on to our customers.</li>
    <li><strong>Detailed Product Information:</strong> We believe in transparency, and our product listings are designed to give you all the information you need before making a purchase. Our website includes specifications, customer reviews, and detailed descriptions of each phone, making it easier for you to compare different models and make an informed decision.</li>
    <li><strong>Easy Payment Options:</strong> With flexible payment plans, we ensure that everyone has access to high-quality mobile phones, even if they’re on a tight budget. We also offer cash on delivery and easy installment plans, making the purchasing process smooth and convenient for our customers.</li>
</ul>

<h2>How Mobile Pricing Trends Are <span>Shaping the Market in Pakistan</span></h2>
<p>The mobile phone market in Pakistan has been evolving rapidly, with several trends emerging in the past few years. Let’s take a closer look at these trends to understand how they influence mobile pricing and consumer behavior:</p>
<ul>
    <li><strong>The Rise of Budget Smartphones:</strong> With increasing demand for affordable smartphones, budget-friendly devices have become more popular in Pakistan. Brands like Xiaomi, Realme, and Infinix have gained significant market share due to their affordable yet feature-packed smartphones. These devices typically cost between <span>PKR 20,000</span> to <span>PKR 40,000</span> and are a hit among first-time smartphone users. For those looking for smartphones in the price range of <a href="https://www.phonesdukan.com/mobiles-price-list/best-mobiles-under-40000/">Best Mobiles Under 40000</a>, you can easily find devices that offer great performance without exceeding your budget.</li>
    <li><strong>Premium Smartphones for the Affluent Market:</strong> On the other end of the spectrum, high-end smartphones from Apple, Samsung, and other premium brands continue to dominate the market. With prices often crossing the <span>PKR 100,000</span> mark, these devices cater to customers who demand cutting-edge technology, high-quality cameras, and superior performance. However, due to the price hike and import duties, the affordability of these models has been affected.</li>
    <li><strong>Pre-Owned Mobile Phones:</strong> Another growing trend is the demand for pre-owned or refurbished mobile phones. With rising prices, many consumers are opting for second-hand or refurbished phones, which offer a more affordable alternative to brand-new models. These phones are typically tested and restored to near-new condition, making them an attractive option for budget-conscious consumers.</li>
    <li><strong>Emerging 5G Technology:</strong> With the advent of 5G technology, mobile phone brands are starting to introduce 5G-enabled devices in the Pakistani market. While these phones are more expensive than their 4G counterparts, they promise faster internet speeds and enhanced connectivity. As 5G becomes more widespread in Pakistan, the prices of these devices are expected to remain high, especially in the early stages.</li>
    <li><strong>Local and International Brands Competing for Market Share:</strong> The competitive landscape is constantly changing, with both local and international brands vying for a piece of the Pakistani mobile market. As new players enter the market, pricing becomes more competitive, which benefits consumers looking for affordable options.</li>
</ul>
    </div>

    <!-- Why Choose Us Section -->
    <div class="why-choose-us">
    <h2>Why Choose Us – <span>Our Mobile:</span></h2>
        <div class="why-choose-us-content">
            <div class="usp-box">
                <i class="fas fa-shield-alt"></i>
                <div class="usp-content">
                    <h4>Daily Updates</h4>
                    <p>Stay informed with accurate and updated prices from trusted dealers.</p>
                </div>
            </div>
            <div class="usp-box">
                <i class="fas fa-truck"></i>
                <div class="usp-content">
                    <h4>Comprehensive Range</h4>
                    <p>From flagship models to budget-friendly options, find mobiles that suit your needs and budget.</p>
                </div>
            </div>
            <div class="usp-box">
                <i class="fas fa-headphones-alt"></i>
                <div class="usp-content">
                    <h4>Specs and Comparisons</h4>
                    <p>Explore detailed specifications, reviews, and comparisons to pick the perfect smartphone.</p>
                </div>
            </div>
            <div class="usp-box">
                <i class="fas fa-dollar-sign"></i>
                <div class="usp-content">
                    <h4>Exclusive Deals</h4>
                    <p>Enjoy special discounts and offers on your favorite brands.</p>
                </div>
            </div>
        </div>
                <p class="p-note">Ready to Find Your Perfect Mobile? <a href="https://www.phonesdukan.com" class="cta-button">Visit Phones Dukan</a> and explore a wide range of smartphones at affordable prices!</p>
    </div>
    <?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>