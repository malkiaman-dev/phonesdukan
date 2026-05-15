<?php
/**
 * SeoHelper — centralised SEO utilities for PhonesDukan.
 *
 * Usage:
 *   // In a category/brand/product view, set $breadcrumbs before including header.php:
 *   $breadcrumbs = SeoHelper::categoryBreadcrumbs('mobiles', 'Mobiles');
 *   $breadcrumbs = SeoHelper::brandBreadcrumbs('mobiles', 'Mobiles', 'samsung', 'Samsung');
 *   $breadcrumbs = SeoHelper::productBreadcrumbs($category_slug, $category_name, $brand_slug, $brand_name, $product_name);
 *
 *   // In a post/blog view:
 *   $breadcrumbs = SeoHelper::blogBreadcrumbs($category_slug, $category_name, $post_title);
 */

class SeoHelper
{
    private const BASE = 'https://www.phonesdukan.com/';

    // ── Breadcrumb builders ───────────────────────────────────────────────

    /** Homepage-only breadcrumb (used on the home page itself). */
    public static function homeBreadcrumbs(): array
    {
        return [
            ['name' => 'Home', 'url' => self::BASE],
        ];
    }

    /** Category page, e.g. /mobiles/ */
    public static function categoryBreadcrumbs(string $categorySlug, string $categoryName): array
    {
        return [
            ['name' => 'Home',         'url' => self::BASE],
            ['name' => $categoryName,  'url' => self::BASE . $categorySlug . '/'],
        ];
    }

    /** Brand page, e.g. /mobiles/samsung/ */
    public static function brandBreadcrumbs(
        string $categorySlug, string $categoryName,
        string $brandSlug,    string $brandName
    ): array {
        return [
            ['name' => 'Home',         'url' => self::BASE],
            ['name' => $categoryName,  'url' => self::BASE . $categorySlug . '/'],
            ['name' => $brandName,     'url' => self::BASE . $categorySlug . '/' . $brandSlug . '/'],
        ];
    }

    /** Product page — last item has no URL (current page). */
    public static function productBreadcrumbs(
        string $categorySlug, string $categoryName,
        string $brandSlug,    string $brandName,
        string $productName,  string $productSlug
    ): array {
        return [
            ['name' => 'Home',         'url' => self::BASE],
            ['name' => $categoryName,  'url' => self::BASE . $categorySlug . '/'],
            ['name' => $brandName,     'url' => self::BASE . $categorySlug . '/' . $brandSlug . '/'],
            ['name' => $productName,   'url' => self::BASE . $categorySlug . '/' . $brandSlug . '/' . $productSlug . '/'],
        ];
    }

    /** Blog post page. */
    public static function blogBreadcrumbs(
        string $categorySlug, string $categoryName,
        string $postTitle,    string $postSlug
    ): array {
        return [
            ['name' => 'Home',          'url' => self::BASE],
            ['name' => 'Blog',          'url' => self::BASE . 'blog/'],
            ['name' => $categoryName,   'url' => self::BASE . 'blog/' . $categorySlug . '/'],
            ['name' => $postTitle,      'url' => self::BASE . 'blog/' . $categorySlug . '/' . $postSlug . '/'],
        ];
    }

    // ── Meta title / description generators ──────────────────────────────

    /**
     * Build a high-CTR product page title.
     * Pattern: {Product Name} Price in Pakistan {Month Year} | Phones Dukan
     */
    public static function productTitle(string $productName, ?string $seoTitle = null): string
    {
        if (!empty($seoTitle)) {
            return $seoTitle;
        }
        return $productName . ' Price in Pakistan ' . date('F Y') . ' | Phones Dukan';
    }

    /**
     * Build a product meta description.
     * Injects price when available.
     */
    public static function productDescription(
        string $productName,
        string $brandName,
        ?float  $price       = null,
        ?string $seoDesc     = null
    ): string {
        if (!empty($seoDesc)) {
            return $seoDesc;
        }
        $priceStr = $price && $price > 0
            ? 'Starting from Rs. ' . number_format($price, 0) . '. '
            : '';
        return "Buy {$productName} in Pakistan at the best price. {$priceStr}"
            . "PTA-approved {$brandName} mobile with official warranty. "
            . "Fast delivery across Pakistan. Shop now at Phones Dukan.";
    }

    /**
     * Build a category page title.
     * Pattern: {Category} Prices in Pakistan {Month Year} | Phones Dukan
     */
    public static function categoryTitle(string $categoryName): string
    {
        return $categoryName . ' Prices in Pakistan ' . date('F Y') . ' | Phones Dukan';
    }

    public static function categoryDescription(string $categoryName): string
    {
        return "Explore the latest {$categoryName} in Pakistan " . date('F Y') . ". "
            . "Compare prices from top brands, PTA-approved devices, "
            . "verified specs, and fast delivery across Pakistan. Shop at Phones Dukan.";
    }

    /**
     * Build a brand page title.
     * Pattern: {Brand} {Category} Price in Pakistan {Month Year} | Phones Dukan
     */
    public static function brandTitle(string $brandName, string $categoryName): string
    {
        return "{$brandName} {$categoryName} Price in Pakistan " . date('F Y') . ' | Phones Dukan';
    }

    public static function brandDescription(string $brandName, string $categoryName): string
    {
        return "Buy the latest {$brandName} {$categoryName} in Pakistan. "
            . "All PTA-approved {$brandName} models with prices, specs, and fast delivery. "
            . "Best deals on {$brandName} phones at Phones Dukan.";
    }

    // ── FAQ Schema builder ────────────────────────────────────────────────

    /**
     * Generate a FAQPage JSON-LD block ready to embed in a <script> tag.
     *
     * @param array<array{question:string,answer:string}> $faqs
     */
    public static function faqSchema(array $faqs): string
    {
        $items = array_map(fn($faq) => [
            '@type'          => 'Question',
            'name'           => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $faq['answer'],
            ],
        ], $faqs);

        return json_encode([
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ── Article Schema builder (for blog posts) ───────────────────────────

    /**
     * @param array{title:string,description:string,url:string,image:string,datePublished:string,dateModified:string,authorName:string} $post
     */
    public static function articleSchema(array $post): string
    {
        return json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'Article',
            'headline'        => $post['title'],
            'description'     => $post['description'],
            'url'             => $post['url'],
            'image'           => $post['image'],
            'datePublished'   => $post['datePublished'],
            'dateModified'    => $post['dateModified'] ?? $post['datePublished'],
            'author'          => [
                '@type' => 'Organization',
                'name'  => 'Phones Dukan',
                'url'   => self::BASE,
            ],
            'publisher'       => [
                '@type' => 'Organization',
                '@id'   => 'https://www.phonesdukan.com/#organization',
                'name'  => 'Phones Dukan',
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => self::BASE . 'public/assets/images/phonesdukan_logo.webp',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $post['url'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ── Open Graph image helper ───────────────────────────────────────────

    /**
     * Return the best available OG image URL, falling back to the store logo.
     */
    public static function ogImage(?string $productImageUrl = null): string
    {
        if (!empty($productImageUrl)) {
            return $productImageUrl;
        }
        return self::BASE . 'public/assets/images/phonesdukan_logo.webp';
    }
}
