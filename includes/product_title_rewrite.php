<?php
/**
 * Shared helpers for Amazon/Daraz-style product title rewriting.
 */

if (!function_exists('buildProfessionalTitle')) {
    /**
     * @param array<string,mixed> $row
     */
    function buildProfessionalTitle(array $row): string
    {
        $name = normalizeName((string) ($row['product_name'] ?? ''));
        $brand = trim((string) ($row['brand_name'] ?? ''));
        $category = trim((string) ($row['category_name'] ?? ''));
        $blob = strip_tags((string) ($row['short_description'] ?? ''));
        if (trim($blob) === '') {
            $blob = strip_tags((string) ($row['product_description'] ?? ''));
        }
        $blob = html_entity_decode($blob, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $blob = str_replace(["\xC2\xA0", '???', '—', '–'], [' ', ' - ', '-', '-'], $blob);
        $blob = preg_replace('/\s+/u', ' ', $blob) ?? $blob;

        // If title was already rewritten, rebuild from the core segment only.
        if (substr_count($name, ' - ') >= 2) {
            $name = trim(explode(' - ', $name, 2)[0]);
        }

        $core = ensureBrandPrefix($name, $brand);
        $features = collectFeatures($blob, $core, $category);

        $parts = [$core];
        foreach ($features as $f) {
            if (similarIn($core . ' ' . implode(' ', $parts), $f)) {
                continue;
            }
            $parts[] = $f;
            if (count($parts) >= 5) {
                break;
            }
        }

        $title = implode(' - ', $parts);
        $title = preg_replace('/\s{2,}/', ' ', $title) ?? $title;
        $title = trim($title, ' -');
        return clipTitle($title, 155);
    }

    function normalizeName(string $name): string
    {
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = preg_replace('/\s*\|\s*/', ' | ', $name) ?? $name;
        $name = preg_replace('/\s{2,}/', ' ', $name) ?? $name;
        return trim($name, " \t|-");
    }

    function ensureBrandPrefix(string $name, string $brand): string
    {
        if ($brand === '' || strcasecmp($brand, 'Variety') === 0) {
            return $name;
        }
        if (stripos($name, $brand) === 0) {
            return $name;
        }
        if (stripos($name, $brand) !== false) {
            return $name;
        }
        return trim($brand . ' ' . $name);
    }

    function collectFeatures(string $blob, string $core, string $category): array
    {
        $features = [];

        $specRules = [
            '/\b(\d+(?:\.\d+)?\s*W)\b/i' => fn ($m) => strtoupper(str_replace(' ', '', $m[1])) . ' Fast Charging',
            '/\b(\d{4,5}\s*mAh)\b/i' => fn ($m) => preg_replace('/\s+/', '', $m[1]) . ' Capacity',
            '/\b(Bluetooth\s*[\d.]+)\b/i' => fn ($m) => 'Bluetooth ' . preg_replace('/[^0-9.]/', '', $m[1]),
            '/\b(IP\d{2})\b/i' => fn ($m) => strtoupper($m[1]) . ' Water Resistant',
            '/\b(ANC\s*\+?\s*ENC|ANC|ENC)\b/i' => fn ($m) => strtoupper(preg_replace('/\s+/', ' ', $m[1])) . ' Noise Cancellation',
            '/\b(GaN)\b/i' => fn () => 'GaN Technology',
            '/\b(Power Delivery|PD)\b/i' => fn () => 'Power Delivery Support',
            '/\b(Dual USB|2 USB|USB Dual)\b/i' => fn () => 'Dual USB Ports',
            '/\b(Type[- ]?C|USB[- ]?C)\b/i' => fn () => 'USB-C Connectivity',
            '/\b(Lightning)\b/i' => fn () => 'Lightning Compatible',
            '/\b(AMOLED|AOD)\b/i' => fn ($m) => strtoupper($m[1]) . ' Display',
            '/\b(\d+(?:\.\d+)?["\']\s*(?:AMOLED|HD|IPS)?)/i' => fn ($m) => trim($m[1]) . ' Display',
            '/\b(Wireless|Bluetooth)\s+(Charging|Speaker|Headphones?|Earbuds?)\b/i' => fn ($m) => titleCase($m[0]),
        ];

        foreach ($specRules as $pattern => $builder) {
            if (preg_match($pattern, $blob, $m) || preg_match($pattern, $core, $m)) {
                $feat = cleanFeature($builder($m));
                if ($feat && !similarIn($core, $feat)) {
                    $features[] = $feat;
                }
            }
        }

        $chunks = preg_split('/[\r\n•●▪]+|(?<=[.!?])\s+/u', $blob) ?: [];
        foreach ($chunks as $chunk) {
            $chunk = cleanFeature($chunk);
            if ($chunk === '' || mb_strlen($chunk) < 12) {
                continue;
            }
            if (preg_match('/^(brand|model|category|type|os|cpu|soc|sim|material|input|output)\b/i', $chunk)) {
                continue;
            }
            if (preg_match('/\b(soc|helio|mediatek|mt\d|android\s*\d+)\b/i', $chunk) && preg_match('/:/', $chunk)) {
                continue;
            }
            $chunk = compressBenefit($chunk);
            if ($chunk === '' || similarIn($core . ' ' . implode(' ', $features), $chunk)) {
                continue;
            }
            if (mb_strlen($chunk) > 42 && similarIn(implode(' ', $features), $chunk)) {
                continue;
            }
            $features[] = $chunk;
            if (count($features) >= 8) {
                break;
            }
        }

        usort($features, static function ($a, $b) {
            return mb_strlen($a) <=> mb_strlen($b);
        });
        $unique = [];
        foreach ($features as $f) {
            if (!similarIn(implode(' ', $unique), $f) && !similarIn($core, $f)) {
                $unique[] = $f;
            }
        }

        if (count($unique) < 2) {
            foreach (categoryFallbacks($category, $core) as $fb) {
                if (!similarIn($core . ' ' . implode(' ', $unique), $fb)) {
                    $unique[] = $fb;
                }
            }
        }

        return array_slice($unique, 0, 4);
    }

    function compressBenefit(string $text): string
    {
        $text = preg_replace('/^(introducing|experience|enjoy|get|the)\s+/i', '', $text) ?? $text;
        if (strpos($text, ':') !== false) {
            [$left, $right] = array_pad(explode(':', $text, 2), 2, '');
            $left = trim($left);
            $right = trim($right);
            if (mb_strlen($right) >= 8 && mb_strlen($right) <= 45) {
                $text = $right;
            } else {
                $text = $left;
            }
        }
        if (mb_strlen($text) > 48) {
            if (preg_match('/^(.{12,48}?)\s+(for|with|that|which|to|of)\b/i', $text, $m)) {
                $text = $m[1];
            } else {
                $text = mb_substr($text, 0, 45);
                $text = preg_replace('/\s+\S*$/u', '', $text) ?? $text;
            }
        }
        $text = cleanFeature($text);
        $replacements = [
            '/\bTpe\b/' => 'TPE',
            '/\bUsb\b/' => 'USB',
            '/\bPd\b/' => 'PD',
            '/\bAnc\b/' => 'ANC',
            '/\bEnc\b/' => 'ENC',
            '/\bRgb\b/' => 'RGB',
            '/\bMah\b/' => 'mAh',
            '/\bLed\b/' => 'LED',
            '/\bIphones?\b/' => 'iPhone',
            '/\bIos\b/' => 'iOS',
        ];
        foreach ($replacements as $pattern => $rep) {
            $text = preg_replace($pattern, $rep, $text) ?? $text;
        }
        return $text;
    }

    function categoryFallbacks(string $category, string $core): array
    {
        $hay = strtolower($category . ' ' . $core);
        $map = [
            'cable' => ['Fast Charging Support', 'Durable Build Quality', 'Reliable Data Sync'],
            'charger' => ['Fast Charging Output', 'Universal Compatibility', 'Travel Friendly Design'],
            'power bank' => ['High Capacity Battery', 'Fast Charging Support', 'Portable Everyday Design'],
            'earbud' => ['Wireless Bluetooth Audio', 'Clear Call Quality', 'Long Battery Life'],
            'headphone' => ['Immersive Sound', 'Comfort Fit Design', 'Clear Microphone'],
            'speaker' => ['Portable Wireless Sound', 'Powerful Bass', 'Long Playback Time'],
            'watch' => ['Smart Notifications', 'Fitness Tracking', 'All-Day Battery'],
            'handsfree' => ['Crystal Clear Audio', 'Built-In Microphone', 'Comfortable In-Ear Fit'],
            'glass' => ['9H Scratch Protection', 'Crystal Clear Screen', 'Easy Installation'],
            'tripod' => ['Stable Multi-Angle Support', 'Adjustable Height', 'Phone & Camera Ready'],
            'adapter' => ['Fast Charging Support', 'Compact Travel Design', 'Wide Device Compatibility'],
        ];
        foreach ($map as $key => $feats) {
            if (strpos($hay, $key) !== false) {
                return $feats;
            }
        }
        return ['Premium Quality', 'Reliable Everyday Performance', 'Value for Money'];
    }

    function cleanFeature(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text, " \t-|:;,.•");
        $text = preg_replace('/\s+(and|or|with|for|of|to)$/i', '', $text) ?? $text;
        return titleCase($text);
    }

    function titleCase(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $keep = ['PD', 'USB', 'USB-C', 'ANC', 'ENC', 'RGB', 'FM', 'GPS', 'AOD', 'IP68', 'IP67', 'GaN', 'AAA', 'HD', 'AMOLED', 'QC', 'TPE', 'mAh', 'W', 'iPhone', 'iOS', 'Type-C'];
        $words = preg_split('/\s+/', $text) ?: [];
        $out = [];
        foreach ($words as $w) {
            $plain = preg_replace('/[^A-Za-z0-9+.\-\']/', '', $w) ?? $w;
            foreach ($keep as $k) {
                if (strcasecmp($plain, $k) === 0) {
                    $out[] = $k;
                    continue 2;
                }
            }
            if (preg_match('/^[A-Za-z]{1,4}-?\d+[A-Za-z0-9]*$/', $plain)) {
                $out[] = strtoupper($plain) === $plain ? $plain : $w;
                continue;
            }
            if (preg_match('/^\d/', $plain)) {
                $out[] = $w;
                continue;
            }
            $out[] = mb_convert_case(mb_strtolower($w), MB_CASE_TITLE, 'UTF-8');
        }
        return implode(' ', $out);
    }

    function similarIn(string $haystack, string $needle): bool
    {
        $h = mb_strtolower($haystack);
        $n = mb_strtolower($needle);
        if ($n === '' || $h === '') {
            return false;
        }
        if (strpos($h, $n) !== false) {
            return true;
        }

        $concepts = [
            'fast charging', 'noise cancellation', 'dual usb', 'usb-c', 'power delivery',
            'wireless bluetooth', 'crystal clear', 'water resistant', 'long battery',
        ];
        foreach ($concepts as $concept) {
            if (strpos($n, $concept) !== false && strpos($h, $concept) !== false) {
                return true;
            }
        }

        $tokens = preg_split('/\s+/', $n) ?: [];
        $meaningful = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 3));
        if (!$meaningful) {
            return false;
        }
        $hits = 0;
        foreach ($meaningful as $t) {
            if (strpos($h, $t) !== false) {
                $hits++;
            }
        }
        return $hits >= max(2, (int) ceil(count($meaningful) * 0.75));
    }

    function clipTitle(string $title, int $max): string
    {
        if (mb_strlen($title) <= $max) {
            return $title;
        }
        $cut = mb_substr($title, 0, $max);
        $dash = mb_strrpos($cut, ' - ');
        if ($dash !== false && $dash > 50) {
            return trim(mb_substr($cut, 0, $dash));
        }
        $space = mb_strrpos($cut, ' ');
        return trim($space ? mb_substr($cut, 0, $space) : $cut);
    }
}

if (!function_exists('enrichProductTitleFromContext')) {
    /**
     * Expand a short product name into Amazon/Daraz-style title.
     * Does not change slug — call this after slug is already decided.
     *
     * @param array<string,mixed> $productData Must include product_name; ideally short_description / product_description
     */
    function enrichProductTitleFromContext(array &$productData, string $brandName = '', string $categoryName = ''): void
    {
        $row = [
            'product_name' => (string) ($productData['product_name'] ?? ''),
            'brand_name' => $brandName,
            'category_name' => $categoryName,
            'short_description' => (string) ($productData['short_description'] ?? ''),
            'product_description' => (string) ($productData['product_description'] ?? ''),
        ];
        $newTitle = buildProfessionalTitle($row);
        if ($newTitle !== '') {
            $productData['product_name'] = $newTitle;
        }
    }
}

if (!function_exists('runProductTitleRewrite')) {
    /**
     * @return array{changed:int,skipped:int,total:int,samples:list<array{id:int,old:string,new:string}>}
     */
    function runProductTitleRewrite(PDO $db, bool $apply = false, ?int $limit = null): array
    {
        $sql = "SELECT p.product_id, p.product_name, p.product_slug, p.short_description, p.product_description,
                       b.brand_name, c.category_name
                FROM products p
                LEFT JOIN brands b ON b.brand_id = p.brand_id
                LEFT JOIN categories c ON c.category_id = p.category_id
                ORDER BY p.product_id ASC";
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $updateProduct = $db->prepare('UPDATE products SET product_name = :name WHERE product_id = :id');
        $updateSeo = $db->prepare('UPDATE product_seo SET seo_title = :title WHERE product_id = :id');

        $changed = 0;
        $skipped = 0;
        $samples = [];

        foreach ($rows as $row) {
            $old = trim((string) $row['product_name']);
            $new = buildProfessionalTitle($row);
            $isChanged = ($new !== '' && strcasecmp($new, $old) !== 0);

            if (!$isChanged) {
                $skipped++;
                continue;
            }

            $changed++;
            if (count($samples) < 15) {
                $samples[] = [
                    'id' => (int) $row['product_id'],
                    'old' => $old,
                    'new' => $new,
                ];
            }

            if ($apply) {
                $updateProduct->execute([
                    ':name' => $new,
                    ':id' => (int) $row['product_id'],
                ]);
                try {
                    $updateSeo->execute([
                        ':title' => $new,
                        ':id' => (int) $row['product_id'],
                    ]);
                } catch (Throwable $e) {
                    // optional seo table
                }
            }
        }

        return [
            'changed' => $changed,
            'skipped' => $skipped,
            'total' => count($rows),
            'samples' => $samples,
        ];
    }
}
