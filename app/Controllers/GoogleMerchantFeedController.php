<?php
// Start output buffering
ob_start();

require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
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
        // GoogleMerchantFeedModel.php defines a feed-specific ProductModel($db)
        $productModel = new ProductModel($this->db);
        $products = $productModel->getAll();

        if (empty($products)) {
            ob_end_clean();
            header('Content-Type: text/plain');
            echo 'No products available.';
            return;
        }

        $base_url = 'https://www.phonesdukan.com';
        $gNs = 'http://base.google.com/ns/1.0';

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Phones Dukan');
        $channel->addChild('link', $base_url);
        $channel->addChild('description', 'Shop top-rated mobiles, smartwatches, and accessories at Phones Dukan – Pakistan’s trusted store. Best prices, quality, and fast delivery. Explore now!');

        $usedIds = [];

        foreach ($products as $product) {
            $productId = (string) ($product['id'] ?? '');
            if ($productId === '' || isset($usedIds[$productId])) {
                continue;
            }
            $usedIds[$productId] = true;

            $priceValue = isset($product['price']) ? (float) $product['price'] : 0.0;
            $saleValue = isset($product['sale_price']) ? (float) $product['sale_price'] : 0.0;
            if ($priceValue <= 0 && $saleValue <= 0) {
                continue;
            }

            $brandSlug = trim((string) ($product['brand_slug'] ?? ''), '/');
            $categorySlug = trim((string) ($product['category_slug'] ?? ''), '/');
            $productSlug = trim((string) ($product['slug'] ?? ''), '/');
            $subcategorySlug = trim((string) ($product['subcategory_slug'] ?? ''), '/');

            if ($brandSlug === '' || $categorySlug === '' || $productSlug === '') {
                continue;
            }

            if ($subcategorySlug !== '') {
                $product_url = $base_url . '/' . rawurlencode($brandSlug) . '/' . rawurlencode($categorySlug) . '/' . rawurlencode($subcategorySlug) . '/' . rawurlencode($productSlug);
            } else {
                $product_url = $base_url . '/' . rawurlencode($brandSlug) . '/' . rawurlencode($categorySlug) . '/' . rawurlencode($productSlug);
            }

            $category_name = isset($product['category_name'])
                ? htmlspecialchars((string) $product['category_name'], ENT_QUOTES, 'UTF-8')
                : 'Unknown Category';

            $item = $channel->addChild('item');
            $item->addChild('g:id', htmlspecialchars($productId, ENT_QUOTES, 'UTF-8'), $gNs);
            $item->addChild('g:title', isset($product['name']) ? htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') : 'Unknown Product', $gNs);
            $item->addChild('g:link', htmlspecialchars($product_url, ENT_QUOTES, 'UTF-8'), $gNs);

            $image_link = isset($product['image_url']) ? (string) $product['image_url'] : '/default-image.jpg';
            if (strpos($image_link, 'http') !== 0) {
                $image_link = $base_url . '/' . ltrim($image_link, '/');
            }
            $image_link = str_replace(' ', '%20', $image_link);
            $item->addChild('g:image_link', htmlspecialchars($image_link, ENT_QUOTES, 'UTF-8'), $gNs);

            $price = number_format($priceValue > 0 ? $priceValue : $saleValue, 2, '.', '');
            $item->addChild('g:price', "{$price} PKR", $gNs);

            if ($saleValue > 0 && $priceValue > 0 && $saleValue < $priceValue) {
                $item->addChild('g:sale_price', number_format($saleValue, 2, '.', '') . ' PKR', $gNs);
            }

            $item->addChild('g:condition', 'new', $gNs);
            $availability = (!empty($product['stock_quantity']) && (int) $product['stock_quantity'] > 0) ? 'in stock' : 'out of stock';
            $item->addChild('g:availability', $availability, $gNs);
            $item->addChild(
                'g:brand',
                isset($product['brand_name']) ? htmlspecialchars((string) $product['brand_name'], ENT_QUOTES, 'UTF-8') : 'Unknown Brand',
                $gNs
            );

            $description = isset($product['description'])
                ? htmlspecialchars(strip_tags((string) $product['description']), ENT_QUOTES, 'UTF-8')
                : 'No description available';
            $item->addChild('g:description', substr($description, 0, 5000), $gNs);
            $item->addChild('g:product_type', $category_name, $gNs);

            $shipping = $item->addChild('g:shipping', null, $gNs);
            $shipping->addChild('g:country', 'PK', $gNs);
            $shipping->addChild('g:service', 'Standard', $gNs);
            $shipping->addChild('g:price', '0.00 PKR', $gNs);
        }

        ob_end_clean();
        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml->asXML();
    }
}

$controller = new GoogleMerchantFeedController();
$controller->generateFeed();
