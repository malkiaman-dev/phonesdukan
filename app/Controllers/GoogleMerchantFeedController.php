<?php
// Start output buffering
ob_start();

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once __DIR__ . '/../Models/GoogleMerchantFeedModel.php';

class GoogleMerchantFeedController
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        if (!$this->db) {
            die('Database connection failed. Please check your database credentials.');
        }
    }

    public function generateFeed()
    {
        $productModel = new ProductModel($this->db);
        $products = $productModel->getAll();

        if (empty($products)) {
            ob_end_clean();
            header('Content-Type: text/plain');
            echo 'No products available.';
            return;
        }

        // Set up the XML structure for the Google Merchant Feed
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Phones Dukan');
        $channel->addChild('link', 'https://www.phonesdukan.com');
        $channel->addChild('description', 'Shop top-rated mobiles, smartwatches, and accessories at Phones Dukan – Pakistan’s trusted store. Best prices, quality, and fast delivery. Explore now!');

        // Track used product IDs to avoid duplicates
        $usedIds = [];

        // Loop through the products and add each to the XML feed
        foreach ($products as $product) {
            // Skip if product ID is already used
            if (in_array($product['id'], $usedIds)) {
                error_log("Duplicate product ID {$product['id']} skipped.");
                continue;
            }
            $usedIds[] = $product['id'];

            // Get product category_slug, brand_slug, and other product info dynamically
            $category_slug = isset($product['category_slug']) ? trim($product['category_slug'], '/') : '';
            $brand_slug = isset($product['brand_slug']) ? trim($product['brand_slug'], '/') : '';
            $product_slug = isset($product['slug']) ? trim($product['slug'], '/') : '';
            $category_name = isset($product['category_name']) ? htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') : 'Unknown Category';

            // Use production domain
            $base_url = 'https://www.phonesdukan.com';

            // Create clean product URL
            $product_url = $base_url . '/' . $category_slug . '/' . $brand_slug . '/' . $product_slug;

            // Initialize the XML item for the product
            $item = $channel->addChild('item');
            $item->addChild('g:id', htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'));
            $item->addChild('g:title', isset($product['name']) ? htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') : 'Unknown Product');
            $item->addChild('g:link', htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8'));

            // Image link - ensure it is absolute and accessible
            $image_link = isset($product['image_url']) ? $product['image_url'] : '/default-image.jpg';
            if (strpos($image_link, 'http') !== 0) {
                $image_link = $base_url . '/' . ltrim($image_link, '/');
            }
            // URL-encode the image link to handle special characters
            $image_link = str_replace(' ', '%20', $image_link);
            $item->addChild('g:image_link', htmlspecialchars($image_link, ENT_QUOTES, 'UTF-8'));

            // Product price - format as 9499.00 PKR
            $price = isset($product['price']) ? number_format((float)$product['price'], 2, '.', '') : '0.00';
            $item->addChild('g:price', "{$price} PKR");

            // Sale price (if applicable)
            if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
                $sale_price = number_format((float)$product['sale_price'], 2, '.', '');
                $item->addChild('g:sale_price', "{$sale_price} PKR");
            }

            // Product condition
            $item->addChild('g:condition', 'new');

            // Product availability
            $availability = $product['stock_quantity'] > 0 ? 'in stock' : 'out of stock';
            $item->addChild('g:availability', $availability);

            // Brand
            $item->addChild('g:brand', isset($product['brand_name']) ? htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8') : 'Unknown Brand');

            // Product description
            $description = isset($product['description']) ? htmlspecialchars(strip_tags($product['description']), ENT_QUOTES, 'UTF-8') : 'No description available';
            // Limit description to 5000 characters (Google's limit)
            $item->addChild('g:description', substr($description, 0, 5000));

            // Add the product category as the product type
            $item->addChild('g:product_type', $category_name);
        }

        // Clear output buffer and set XML header
        ob_end_clean();
        header('Content-Type: application/xml; charset=UTF-8');

        // Output the XML
        echo $xml->asXML();
    }
}

// Instantiate and call the controller
$controller = new GoogleMerchantFeedController();
$controller->generateFeed();