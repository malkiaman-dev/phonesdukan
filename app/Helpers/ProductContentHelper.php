<?php

class ProductContentHelper
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'table', 'thead', 'tbody',
        'tfoot', 'tr', 'th', 'td', 'dl', 'dt', 'dd', 'div', 'span',
        'img', 'blockquote', 'hr', 'sup', 'sub', 'figure', 'figcaption',
    ];

    public static function renderDescription(string $html, ?string $adHtml = null, int $adAfterParagraph = 1): string
    {
        $html = self::sanitizeProductHtml($html);
        if ($html === '') {
            return '<div class="pd-rich-content pd-rich-content--empty"><p>No description available for this product.</p></div>';
        }

        if ($adHtml !== null && trim($adHtml) !== '') {
            $html = self::injectAdAfterParagraphs($html, $adAfterParagraph, $adHtml);
        }

        return '<div class="pd-rich-content pd-rich-content--amazon">' . $html . '</div>';
    }

    public static function renderSpecifications(string $html, array $product = []): string
    {
        $html = self::sanitizeProductHtml($html);
        $rows = self::extractSpecificationRows($html);
        $metaRows = self::buildProductMetaRows($product);

        if ($rows === [] && $html !== '') {
            return '<div class="pd-spec-sheet">'
                . self::renderMetaRows($metaRows)
                . '<div class="pd-spec-fallback pd-rich-content pd-rich-content--spec">' . $html . '</div>'
                . '</div>';
        }

        if ($rows === [] && $metaRows === []) {
            return '<div class="pd-spec-sheet pd-spec-sheet--empty"><p>No specifications available for this product.</p></div>';
        }

        $merged = self::mergeSpecificationRows($rows, $metaRows);

        return '<div class="pd-spec-sheet">'
            . self::renderSpecificationTable($merged)
            . '</div>';
    }

    /**
     * Parse one or many inline "Label: Value" pairs (fixes AI output on a single line).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function extractAllKeyValuePairs(string $text): array
    {
        $text = self::normalizeText(strip_tags($text));
        if ($text === '') {
            return [];
        }

        $rows = [];
        $lines = preg_split('/\R+/', $text) ?: [];

        if (count($lines) > 1) {
            foreach ($lines as $line) {
                $line = self::normalizeText($line);
                if ($line === '') {
                    continue;
                }
                $rows = array_merge($rows, self::extractKeyValuePairsFromSegment($line));
            }

            $rows = self::dedupeRows($rows);
            if (count($rows) >= 2) {
                return $rows;
            }
        }

        return self::extractKeyValuePairsFromSegment($text);
    }

    public static function sanitizeProductHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button)\b[^>]*\/?>/i', '', $html) ?? $html;

        $allowed = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $html = strip_tags($html, $allowed);

        if (!class_exists('DOMDocument')) {
            return $html;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="pd-root">' . $html . '</div>';
        if (!$doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return $html;
        }
        libxml_clear_errors();

        $root = $doc->getElementById('pd-root');
        if (!$root) {
            return $html;
        }

        self::sanitizeDomNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function extractSpecificationRows(string $html): array
    {
        $html = self::sanitizeProductHtml($html);
        if ($html === '') {
            return [];
        }

        if (!class_exists('DOMDocument')) {
            return self::parsePlainSpecificationLines(strip_tags($html));
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="pd-root">' . $html . '</div>';
        if (!$doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            return self::parsePlainSpecificationLines(strip_tags($html));
        }
        libxml_clear_errors();

        $root = $doc->getElementById('pd-root');
        if (!$root) {
            return [];
        }

        $rows = [];

        foreach ($root->getElementsByTagName('table') as $table) {
            foreach ($table->getElementsByTagName('tr') as $tr) {
                $cells = [];
                foreach ($tr->childNodes as $cell) {
                    if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                        $cells[] = self::normalizeText($cell->textContent);
                    }
                }
                if (count($cells) >= 2) {
                    $rows[] = ['label' => $cells[0], 'value' => implode(' ', array_slice($cells, 1))];
                }
            }
        }

        if ($rows !== []) {
            return self::dedupeRows($rows);
        }

        foreach ($root->getElementsByTagName('dl') as $dl) {
            $label = '';
            foreach ($dl->childNodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                $tag = strtolower($node->tagName);
                if ($tag === 'dt') {
                    $label = self::normalizeText($node->textContent);
                } elseif ($tag === 'dd' && $label !== '') {
                    $rows[] = ['label' => $label, 'value' => self::normalizeText($node->textContent)];
                    $label = '';
                }
            }
        }

        if ($rows !== []) {
            return self::dedupeRows($rows);
        }

        foreach (['ul', 'ol'] as $listTag) {
            foreach ($root->getElementsByTagName($listTag) as $list) {
                foreach ($list->getElementsByTagName('li') as $li) {
                    $parsed = self::parseListItemSpecification($li);
                    if ($parsed !== null) {
                        $rows[] = $parsed;
                    }
                }
            }
        }

        if ($rows !== []) {
            return self::dedupeRows($rows);
        }

        foreach (['p', 'div', 'span'] as $blockTag) {
            foreach ($root->getElementsByTagName($blockTag) as $node) {
                $text = self::normalizeText($node->textContent);
                if ($text !== '') {
                    $rows = array_merge($rows, self::extractKeyValuePairsFromSegment($text));
                }
            }
        }

        if ($rows !== []) {
            return self::dedupeRows($rows);
        }

        return self::extractAllKeyValuePairs(strip_tags($html));
    }

    private static function sanitizeDomNode(DOMNode $node): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }

        $remove = [];
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                $remove[] = $child;
                continue;
            }

            if ($tag === 'a') {
                $href = trim((string) $child->getAttribute('href'));
                if ($href === '' || preg_match('/^\s*javascript:/i', $href)) {
                    $child->removeAttribute('href');
                } else {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('rel', 'noopener noreferrer');
                    if (preg_match('/^https?:\/\//i', $href)) {
                        $child->setAttribute('target', '_blank');
                    }
                }
            }

            if ($tag === 'img') {
                $src = trim((string) $child->getAttribute('src'));
                if ($src === '' || preg_match('/^\s*javascript:/i', $src)) {
                    $remove[] = $child;
                    continue;
                }
                $child->setAttribute('src', $src);
                $child->setAttribute('loading', 'lazy');
                $child->setAttribute('decoding', 'async');
                if (!$child->hasAttribute('alt')) {
                    $child->setAttribute('alt', '');
                }
            }

            self::sanitizeDomNode($child);
        }

        foreach ($remove as $child) {
            $node->removeChild($child);
        }
    }

    private static function injectAdAfterParagraphs(string $html, int $afterIndex, string $adHtml): string
    {
        if (!preg_match_all('/<\/p>/i', $html, $matches, PREG_OFFSET_CAPTURE) || empty($matches[0])) {
            return $html . $adHtml;
        }

        $positions = $matches[0];
        if (!isset($positions[$afterIndex])) {
            return $html;
        }

        $offset = $positions[$afterIndex][1] + strlen($positions[$afterIndex][0]);

        return substr($html, 0, $offset) . $adHtml . substr($html, $offset);
    }

    /**
     * @param array<int, array{label: string, value: string}> $rows
     */
    private static function renderSpecificationTable(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $html = '<div class="pd-spec-group"><h3 class="pd-spec-group-title">Product Specifications</h3><div class="pd-spec-grid">';
        foreach ($rows as $row) {
            $html .= '<div class="pd-spec-row">'
                . '<div class="pd-spec-label">' . htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') . '</div>'
                . '<div class="pd-spec-value">' . htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') . '</div>'
                . '</div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param array<int, array{label: string, value: string}> $metaRows
     */
    private static function renderMetaRows(array $metaRows): string
    {
        if ($metaRows === []) {
            return '';
        }

        return self::renderSpecificationTable($metaRows);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function buildProductMetaRows(array $product): array
    {
        $rows = [];

        $map = [
            'Brand' => $product['brand_name'] ?? '',
            'Category' => $product['category_name'] ?? '',
            'Sub Category' => $product['subcategory_name'] ?? '',
            'SKU' => $product['product_sku'] ?? '',
            'Weight' => isset($product['weight_kg']) && is_numeric($product['weight_kg']) && (float) $product['weight_kg'] > 0
                ? rtrim(rtrim(number_format((float) $product['weight_kg'], 2, '.', ''), '0'), '.') . ' kg'
                : '',
            'Dimensions' => self::formatDimensions($product),
        ];

        foreach ($map as $label => $value) {
            $value = self::normalizeText((string) $value);
            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        return $rows;
    }

    private static function formatDimensions(array $product): string
    {
        $length = isset($product['length_cm']) && is_numeric($product['length_cm']) ? (float) $product['length_cm'] : 0;
        $width = isset($product['width_cm']) && is_numeric($product['width_cm']) ? (float) $product['width_cm'] : 0;
        $height = isset($product['height_cm']) && is_numeric($product['height_cm']) ? (float) $product['height_cm'] : 0;

        if ($length <= 0 && $width <= 0 && $height <= 0) {
            return '';
        }

        $parts = [];
        if ($length > 0) {
            $parts[] = 'L ' . self::trimNumber($length) . ' cm';
        }
        if ($width > 0) {
            $parts[] = 'W ' . self::trimNumber($width) . ' cm';
        }
        if ($height > 0) {
            $parts[] = 'H ' . self::trimNumber($height) . ' cm';
        }

        return implode(' × ', $parts);
    }

    private static function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * @param array<int, array{label: string, value: string}> $parsedRows
     * @param array<int, array{label: string, value: string}> $metaRows
     * @return array<int, array{label: string, value: string}>
     */
    private static function mergeSpecificationRows(array $parsedRows, array $metaRows): array
    {
        $merged = [];
        $seen = [];

        foreach ($parsedRows as $row) {
            $label = self::normalizeText($row['label'] ?? '');
            $value = self::normalizeText($row['value'] ?? '');
            if ($label === '' || $value === '') {
                continue;
            }
            $key = strtolower($label);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = ['label' => $label, 'value' => $value];
        }

        $metaSkipIfParsed = ['brand', 'category', 'sub category', 'sku', 'weight', 'dimensions'];

        foreach ($metaRows as $row) {
            $label = self::normalizeText($row['label'] ?? '');
            $value = self::normalizeText($row['value'] ?? '');
            if ($label === '' || $value === '') {
                continue;
            }
            $key = strtolower($label);
            if (isset($seen[$key])) {
                continue;
            }
            if (count($parsedRows) >= 3 && in_array($key, $metaSkipIfParsed, true)) {
                continue;
            }
            if ($key === 'brand' && strlen($value) > 80) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = ['label' => $label, 'value' => $value];
        }

        return $merged;
    }

    private static function parseListItemSpecification(DOMElement $li): ?array
    {
        $strong = null;
        foreach ($li->getElementsByTagName('strong') as $node) {
            $strong = $node;
            break;
        }
        if ($strong === null) {
            foreach ($li->getElementsByTagName('b') as $node) {
                $strong = $node;
                break;
            }
        }

        if ($strong instanceof DOMElement) {
            $label = self::normalizeText($strong->textContent);
            $label = rtrim($label, ':');
            $value = self::normalizeText(str_replace($strong->textContent, '', $li->textContent));
            if ($label !== '' && $value !== '') {
                return ['label' => $label, 'value' => $value];
            }
        }

        return self::parseKeyValueLine(self::normalizeText($li->textContent));
    }

    /**
     * @return array{label: string, value: string}|null
     */
    private static function parseKeyValueLine(string $line): ?array
    {
        $line = self::normalizeText($line);
        if ($line === '') {
            return null;
        }

        if (preg_match('/^(.{2,80}?)\s*[:：\-–—|]\s*(.+)$/u', $line, $matches)) {
            $label = self::normalizeText($matches[1]);
            $value = self::normalizeText($matches[2]);
            if ($label !== '' && $value !== '' && strlen($label) <= 80) {
                return ['label' => $label, 'value' => $value];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function getKnownSpecLabels(): array
    {
        return [
            'Battery Capacity', 'Playback Time', 'Frequency Response', 'Driver Size',
            'Noise Cancellation', 'Water Resistance', 'Bluetooth Version', 'Bluetooth Calling',
            'Audio Output', 'Fast Charging', 'Charging Case', 'Charging Protocol', 'Charging Port',
            'Sports Modes', 'Health Tracking', 'Output Power', 'Data Transfer', 'Connector Type',
            'Protection Level', 'Package Contents', 'Key Feature', 'RAM & Storage', 'Battery Life',
            'Low Latency', 'Screen Size', 'Refresh Rate', 'Main Camera', 'Selfie Camera',
            'Display', 'Processor', 'Connectivity', 'Compatibility', 'Dimensions', 'Sensitivity',
            'Impedance', 'Material', 'Category', 'Warranty', 'Bluetooth', 'Storage', 'Camera',
            'Battery', 'Processor', 'Weight', 'Brand', 'Model', 'Length', 'Capacity', 'Audio',
            'Design', 'Output', 'Ports', 'Color', 'OS', 'RAM', 'Type',
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function extractKeyValuePairsFromSegment(string $text): array
    {
        $text = self::normalizeText($text);
        if ($text === '') {
            return [];
        }

        $rows = self::extractByKnownSpecLabels($text);
        if (count($rows) >= 2) {
            return $rows;
        }

        if (!preg_match_all(
            '/(?:^|\s)([A-Za-z][A-Za-z0-9&]*(?:\s+(?:&|and|\/|\+|[A-Za-z0-9&\(\)\-\.]+)){0,4})\s*:\s*/u',
            ' ' . $text,
            $matches,
            PREG_OFFSET_CAPTURE
        ) || empty($matches[1])) {
            $single = self::parseKeyValueLine($text);
            return $single ? [$single] : $rows;
        }

        $labelCount = count($matches[1]);
        for ($i = 0; $i < $labelCount; $i++) {
            $label = self::normalizeText($matches[1][$i][0]);
            $colonPos = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $nextLabelPos = ($i + 1 < $labelCount) ? $matches[1][$i + 1][1] : strlen(' ' . $text);
            $value = self::normalizeText(substr(' ' . $text, $colonPos, $nextLabelPos - $colonPos));

            if ($label !== '' && $value !== '' && strlen($label) <= 50) {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        $rows = self::dedupeRows($rows);
        if ($rows !== []) {
            return $rows;
        }

        $single = self::parseKeyValueLine($text);
        return $single ? [$single] : [];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private static function extractByKnownSpecLabels(string $text): array
    {
        $labels = self::getKnownSpecLabels();
        usort($labels, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        $alternation = implode('|', array_map(static fn(string $l): string => preg_quote($l, '/'), $labels));

        if (!preg_match_all(
            '/(?:^|\s)(' . $alternation . ')\s*:\s*/iu',
            ' ' . $text,
            $matches,
            PREG_OFFSET_CAPTURE
        ) || count($matches[1]) < 1) {
            return [];
        }

        $rows = [];
        $usedPositions = [];
        $count = count($matches[1]);

        for ($i = 0; $i < $count; $i++) {
            $pos = $matches[0][$i][1];
            if (isset($usedPositions[$pos])) {
                continue;
            }
            $usedPositions[$pos] = true;

            $label = self::normalizeText($matches[1][$i][0]);
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end = strlen(' ' . $text);
            for ($j = $i + 1; $j < $count; $j++) {
                $nextPos = $matches[0][$j][1];
                if ($nextPos > $start) {
                    $end = $nextPos;
                    break;
                }
            }

            $value = self::normalizeText(substr(' ' . $text, $start, $end - $start));
            if ($label !== '' && $value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        usort($rows, static function (array $a, array $b) use ($text): int {
            return strpos($text, $a['label'] . ':') <=> strpos($text, $b['label'] . ':');
        });

        return self::dedupeRows($rows);
    }

    /**
     * @param array<int, array{label: string, value: string}> $rows
     * @return array<int, array{label: string, value: string}>
     */
    private static function dedupeRows(array $rows): array
    {
        $merged = [];
        $seen = [];

        foreach ($rows as $row) {
            $label = self::normalizeText($row['label'] ?? '');
            $value = self::normalizeText($row['value'] ?? '');
            if ($label === '' || $value === '') {
                continue;
            }
            $key = strtolower($label);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = ['label' => $label, 'value' => $value];
        }

        return $merged;
    }

    private static function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }
}
