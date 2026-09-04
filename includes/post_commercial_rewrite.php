<?php
/**
 * Commercial post rewrite helpers — remove passionate fluff,
 * strengthen product-selling intent, and attach shop recommendations.
 */

if (!function_exists('pdDepassionatePostHtml')) {
    function pdDepassionatePostHtml(string $html): string
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove casual/passionate openers often used in old posts.
        $patterns = [
            '/<p>\s*(Hey there!?|Hey,?|Hello,?|Hi there!?|Ever wondered|Are you tired|Have you ever)[^<]{0,220}<\/p>/iu',
            '/<p>\s*Hello,\s+[^<]{0,80}users!?[^<]{0,180}<\/p>/iu',
            '/\?\?\?/',
            '/\x{2014}|\x{2013}/u',
        ];
        $replacements = [
            '',
            '',
            '',
            '-',
        ];
        $html = preg_replace($patterns, $replacements, $html) ?? $html;

        // Soften overly casual phrases inside paragraphs.
        $phraseMap = [
            '/\bgo-to way\b/iu' => 'practical option',
            '/\bfantastic way\b/iu' => 'useful option',
            '/\blove staying online\b/iu' => 'need reliable internet',
            '/\bbreaking the bank\b/iu' => 'overspending',
            '/\bdaily grind\b/iu' => 'everyday work',
            '/\bgaming marathons\b/iu' => 'long gaming sessions',
        ];
        foreach ($phraseMap as $pattern => $rep) {
            $html = preg_replace($pattern, $rep, $html) ?? $html;
        }

        return trim($html);
    }
}

if (!function_exists('pdCommercialPostTitle')) {
    function pdCommercialPostTitle(string $title, string $slug = ''): string
    {
        $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $title = str_replace(['???', '—', '–'], ['-', '-', '-'], $title);
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;

        $map = [
            'l-214-vs-l-220-earbuds' => 'Login L-214 vs L-220 Earbuds Comparison - Price, Specs & Which to Buy in Pakistan ' . date('Y'),
            'iphone-17-series-review' => 'iPhone 17 Series Price in Pakistan ' . date('Y') . ' - Specs, PTA Tax & Where to Buy',
            'iphone-pta-tax' => 'iPhone PTA Tax in Pakistan ' . date('Y') . ' - Rates for iPhone 16, 15, 14 & How to Pay',
            'pta-tax-calculator' => 'PTA Tax Calculator Pakistan ' . date('Y') . ' - Check Mobile Import Tax Before You Buy',
            'how-to-pay-pta-tax-in-pakistan' => 'How to Pay PTA Tax in Pakistan ' . date('Y') . ' - Step-by-Step Guide for Imported Phones',
            'zong-internet-packages' => 'Zong Internet Packages ' . date('Y') . ' - Daily, Weekly & Monthly Data Bundles',
            'telenor-sms-packages' => 'Telenor SMS Packages ' . date('Y') . ' - Daily, Weekly & Monthly Bundles',
            'zong-sms-packages' => 'Zong SMS Packages ' . date('Y') . ' - Daily, Weekly & Monthly Bundles',
            'jazz-sms-packages' => 'Jazz SMS Packages ' . date('Y') . ' - Daily, Weekly & Monthly Bundles',
            'ufone-sms-packages' => 'Ufone SMS Packages ' . date('Y') . ' - Daily, Weekly & Monthly Bundles',
            'telenor-call-packages' => 'Telenor Call Packages ' . date('Y') . ' - Daily, Weekly & Monthly Plans',
            'ufone-call-packages' => 'Ufone Call Packages ' . date('Y') . ' - Daily, Weekly & Monthly Plans',
            'jazz-call-packages' => 'Jazz Call Packages ' . date('Y') . ' - Daily, Weekly & Monthly Plans',
            'zong-call-packages-hourly-daily-weekly-monthly' => 'Zong Call Packages ' . date('Y') . ' - Hourly, Daily, Weekly & Monthly Plans',
            'ufone-number-check' => 'Ufone Number Check Code ' . date('Y') . ' - How to Find Your Ufone Number',
            'jazz-balance-save-code' => 'Jazz Balance Save Code ' . date('Y') . ' - Protect Prepaid Balance Easily',
            'telenor-number-check-code' => 'Telenor Number Check Code ' . date('Y') . ' - How to Find Your Telenor Number',
            'zong-number-check-code' => 'Zong Number Check Code ' . date('Y') . ' - How to Find Your Zong Number',
            'pakistan-national-super-app-deep' => 'Pakistan National Super App DEEP - What It Means for Digital Services',
            '8171-cnic-check-online-ehsaas-program' => '8171 CNIC Check Online ' . date('Y') . ' - Ehsaas / BISP Eligibility Guide',
        ];

        if ($slug !== '' && isset($map[$slug])) {
            return $map[$slug];
        }

        // Generic cleanup: strip "Hey" style leftovers and ensure year/Pakistan where useful.
        $title = preg_replace('/^(Your Ultimate Guide to|Complete Guide to|Easy Guide to)\s+/iu', '', $title) ?? $title;
        return trim($title);
    }
}

if (!function_exists('pdCommercialPostExcerpt')) {
    function pdCommercialPostExcerpt(string $title, string $slug = ''): string
    {
        $month = date('F Y');
        if (stripos($slug, 'pta') !== false || stripos($title, 'PTA') !== false) {
            return "Updated PTA tax guide for {$month}. Check import tax, payment steps, and buy PTA-ready mobiles from Phones Dukan with clear pricing.";
        }
        if (stripos($slug, 'iphone') !== false) {
            return "iPhone buying guide for Pakistan ({$month}). Compare models, PTA tax impact, and current prices before you order from Phones Dukan.";
        }
        if (stripos($slug, 'earbud') !== false) {
            return "Compare wireless earbuds on specs, battery, and value. Shop verified models at Phones Dukan with updated Pakistan prices for {$month}.";
        }
        if (preg_match('/(jazz|zong|ufone|telenor)/i', $slug . ' ' . $title)) {
            return "Clear package details for {$month}. After you stay connected, explore mobiles, earbuds, and chargers at Phones Dukan.";
        }
        return "Practical guide updated for {$month}. Browse mobiles and accessories at Phones Dukan when you are ready to buy.";
    }
}

if (!function_exists('pdBuildShopCtaHtml')) {
    function pdBuildShopCtaHtml(string $shopUrl = '/'): string
    {
        $shopUrl = htmlspecialchars($shopUrl, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<section class="pd-shop-cta" style="margin:28px 0;padding:20px;border:1px solid #e5e7eb;border-radius:12px;background:#fffbeb;">
  <h2 style="margin:0 0 8px;font-size:1.25rem;font-weight:600;color:#111;">Shop mobiles &amp; accessories at Phones Dukan</h2>
  <p style="margin:0 0 14px;color:#374151;line-height:1.55;">Looking for verified prices, PTA-ready phones, earbuds, chargers, and power banks? Browse our live catalog and order with fast delivery across Pakistan.</p>
  <p style="margin:0;">
    <a href="{$shopUrl}" style="display:inline-block;background:#111;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;">Browse products</a>
    <a href="{$shopUrl}mobiles/" style="display:inline-block;margin-left:8px;background:#f7cf04;color:#111;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;">Shop mobiles</a>
  </p>
</section>
HTML;
    }
}

if (!function_exists('pdFindRelatedProductsForPost')) {
    /**
     * @return list<array<string,mixed>>
     */
    function pdFindRelatedProductsForPost(PDO $db, string $title, string $slug, int $limit = 4): array
    {
        require_once dirname(__DIR__) . '/includes/functions.php';

        $hay = strtolower($title . ' ' . $slug);
        $keywords = [];
        if (str_contains($hay, 'iphone') || str_contains($hay, 'apple')) {
            $keywords = ['iphone', 'apple'];
        } elseif (str_contains($hay, 'earbud') || str_contains($hay, 'l-214') || str_contains($hay, 'l-220')) {
            $keywords = ['earbud', 'login', 'anc'];
        } elseif (str_contains($hay, 'pta') || str_contains($hay, 'mobile tax')) {
            $keywords = ['samsung', 'infinix', 'vivo', 'mobile'];
        } else {
            $keywords = ['charger', 'power bank', 'earbud', 'cable'];
        }

        $likeParts = [];
        $params = [];
        foreach ($keywords as $i => $kw) {
            $key = ':k' . $i;
            $likeParts[] = "p.product_name LIKE {$key}";
            $params[$key] = '%' . $kw . '%';
        }
        $whereLike = implode(' OR ', $likeParts);

        $sql = "SELECT p.product_id, p.product_name, p.product_slug, p.regular_price, p.sale_price,
                       b.slug AS brand_slug, c.slug AS category_slug, sc.slug AS subcategory_slug,
                       i.image_url
                FROM products p
                LEFT JOIN brands b ON b.brand_id = p.brand_id
                LEFT JOIN categories c ON c.category_id = p.category_id
                LEFT JOIN categories sc ON sc.category_id = p.subcategory_id
                LEFT JOIN product_images i ON i.product_id = p.product_id AND i.is_primary = 1
                WHERE p.product_status = 1 AND ({$whereLike})
                ORDER BY p.updated_at DESC
                LIMIT " . (int) $limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('pdRenderRelatedProductsBlock')) {
    /**
     * @param list<array<string,mixed>> $products
     */
    function pdRenderRelatedProductsBlock(array $products): string
    {
        if (!$products) {
            return '';
        }
        require_once dirname(__DIR__) . '/includes/functions.php';

        $cards = '';
        foreach ($products as $p) {
            $path = buildProductPathFromRow($p);
            $url = htmlspecialchars(url(ltrim($path, '/')), ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars((string) $p['product_name'], ENT_QUOTES, 'UTF-8');
            $priceVal = (!empty($p['sale_price']) && (float) $p['sale_price'] > 0)
                ? (float) $p['sale_price']
                : (float) ($p['regular_price'] ?? 0);
            $price = $priceVal > 0 ? ('Rs. ' . number_format($priceVal, 0)) : '';
            $img = '';
            if (!empty($p['image_url']) && function_exists('normalizeMediaUrl')) {
                $img = htmlspecialchars((string) normalizeMediaUrl((string) $p['image_url']), ENT_QUOTES, 'UTF-8');
            }
            $imgHtml = $img !== ''
                ? '<img src="' . $img . '" alt="' . $name . '" loading="lazy" style="width:100%;height:140px;object-fit:contain;background:#fff;">'
                : '';
            $cards .= '<a href="' . $url . '/" style="display:block;border:1px solid #e5e7eb;border-radius:10px;padding:10px;text-decoration:none;color:#111;background:#fff;">'
                . $imgHtml
                . '<div style="margin-top:8px;font-size:14px;line-height:1.4;font-weight:400;">' . $name . '</div>'
                . ($price !== '' ? '<div style="margin-top:6px;font-weight:700;">' . $price . '</div>' : '')
                . '<div style="margin-top:8px;font-size:13px;color:#111;">View product →</div>'
                . '</a>';
        }

        return '<section class="pd-related-products" style="margin:28px 0;">'
            . '<h2 style="margin:0 0 12px;font-size:1.25rem;font-weight:600;">Recommended products at Phones Dukan</h2>'
            . '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">'
            . $cards
            . '</div></section>';
    }
}

if (!function_exists('runCommercialPostRewrite')) {
    /**
     * @return array{changed:int,total:int,samples:list<array{id:int,old:string,new:string}>}
     */
    function runCommercialPostRewrite(PDO $db, bool $apply = false): array
    {
        $rows = $db->query(
            'SELECT id, title, slug, content, excerpt FROM posts ORDER BY id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $updatePost = $db->prepare(
            'UPDATE posts
             SET title = :title, excerpt = :excerpt, content = :content, updated_at = NOW()
             WHERE id = :id'
        );
        $updateSeo = $db->prepare(
            'UPDATE post_seo
             SET meta_title = :mt, meta_description = :md, updated_at = NOW()
             WHERE post_id = :id'
        );
        $insertSeo = $db->prepare(
            'INSERT INTO post_seo (post_id, meta_title, meta_description, meta_keywords)
             VALUES (:id, :mt, :md, :mk)'
        );

        $changed = 0;
        $samples = [];
        $shopHome = function_exists('url') ? url() : '/';

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $oldTitle = (string) $row['title'];
            $slug = (string) $row['slug'];
            $newTitle = pdCommercialPostTitle($oldTitle, $slug);
            $newExcerpt = pdCommercialPostExcerpt($newTitle, $slug);

            $content = pdDepassionatePostHtml((string) $row['content']);
            // Remove previously injected CTA blocks to avoid duplicates on re-run.
            $content = preg_replace('/<section class="pd-shop-cta"[\s\S]*?<\/section>/i', '', $content) ?? $content;
            $content = preg_replace('/<section class="pd-related-products"[\s\S]*?<\/section>/i', '', $content) ?? $content;

            $related = pdFindRelatedProductsForPost($db, $newTitle, $slug, 4);
            $content .= "\n" . pdBuildShopCtaHtml($shopHome);
            $content .= "\n" . pdRenderRelatedProductsBlock($related);

            $isChanged = true;
            $changed++;
            if (count($samples) < 12) {
                $samples[] = ['id' => $id, 'old' => $oldTitle, 'new' => $newTitle];
            }

            if ($apply && $isChanged) {
                $updatePost->execute([
                    ':title' => $newTitle,
                    ':excerpt' => $newExcerpt,
                    ':content' => $content,
                    ':id' => $id,
                ]);

                $metaTitle = mb_substr($newTitle . ' | Phones Dukan', 0, 60);
                $metaDesc = mb_substr($newExcerpt, 0, 155);
                $updateSeo->execute([
                    ':mt' => $metaTitle,
                    ':md' => $metaDesc,
                    ':id' => $id,
                ]);
                if ($updateSeo->rowCount() === 0) {
                    try {
                        $insertSeo->execute([
                            ':id' => $id,
                            ':mt' => $metaTitle,
                            ':md' => $metaDesc,
                            ':mk' => 'phones dukan, buy online pakistan',
                        ]);
                    } catch (Throwable $e) {
                        // ignore duplicate
                    }
                }
            }
        }

        return [
            'changed' => $changed,
            'total' => count($rows),
            'samples' => $samples,
        ];
    }
}
