<?php
/**
 * Professional customer reviews list for single product page.
 * Expects $reviews, $averageRating, $reviewCount from product.php.
 */
if (!isset($reviews) || !is_array($reviews)) {
    require_once dirname(__DIR__, 3) . '/app/Controllers/ReviewController.php';
    $reviewController = new ReviewController();
    $reviews = $reviewController->getApprovedByProduct((int) ($product['product_id'] ?? 0));
}

$reviewCount = isset($reviewCount) ? (int) $reviewCount : count($reviews);
$averageRating = isset($averageRating) ? (float) $averageRating : 0.0;
if ($reviewCount > 0 && $averageRating <= 0) {
    $totalRating = array_sum(array_map(static fn ($r) => (int) ($r['rating'] ?? 0), $reviews));
    $averageRating = round($totalRating / $reviewCount, 1);
}

$ratingBuckets = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $reviewRow) {
    $r = (int) ($reviewRow['rating'] ?? 0);
    if ($r >= 1 && $r <= 5) {
        $ratingBuckets[$r]++;
    }
}

if (!function_exists('pdReviewStarsHtml')) {
    function pdReviewStarsHtml(float $rating, string $size = 'md'): string
    {
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        $px = $size === 'lg' ? 18 : 14;
        $html = '<span class="pd-rev-stars pd-rev-stars--' . htmlspecialchars($size) . '" aria-label="' . htmlspecialchars(number_format($rating, 1)) . ' out of 5">';
        for ($i = 0; $i < $full; $i++) {
            $html .= '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" fill="#f7cf04" stroke="none" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }
        if ($half) {
            $html .= '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" fill="none" stroke="#f7cf04" stroke-width="2" aria-hidden="true"><defs><linearGradient id="halfStarFill"><stop offset="50%" stop-color="#f7cf04"/><stop offset="50%" stop-color="transparent"/></linearGradient></defs><path fill="url(#halfStarFill)" stroke="#f7cf04" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }
        for ($i = 0; $i < $empty; $i++) {
            $html .= '<svg width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" fill="#e5e7eb" stroke="none" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }
        $html .= '</span>';
        return $html;
    }
}
?>

<div class="pd-reviews" id="customer-reviews">
    <div class="pd-reviews-summary">
        <div class="pd-reviews-score">
            <div class="pd-reviews-score-value"><?= $reviewCount > 0 ? number_format($averageRating, 1) : '0.0' ?></div>
            <?= pdReviewStarsHtml($averageRating, 'lg') ?>
            <div class="pd-reviews-score-count">
                <?= $reviewCount > 0
                    ? ('Based on ' . $reviewCount . ' review' . ($reviewCount === 1 ? '' : 's'))
                    : 'No reviews yet' ?>
            </div>
        </div>

        <div class="pd-reviews-bars" aria-label="Rating breakdown">
            <?php for ($star = 5; $star >= 1; $star--):
                $count = $ratingBuckets[$star];
                $pct = $reviewCount > 0 ? round(($count / $reviewCount) * 100) : 0;
            ?>
                <div class="pd-reviews-bar-row">
                    <span class="pd-reviews-bar-label"><?= $star ?> star</span>
                    <div class="pd-reviews-bar-track">
                        <div class="pd-reviews-bar-fill" style="width: <?= (int) $pct ?>%;"></div>
                    </div>
                    <span class="pd-reviews-bar-count"><?= (int) $count ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="pd-reviews-list">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review):
                $author = trim((string) ($review['author'] ?? 'Customer'));
                $initial = strtoupper(substr($author !== '' ? $author : 'C', 0, 1));
                $rating = (int) ($review['rating'] ?? 0);
                $content = trim(html_entity_decode((string) ($review['content'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $created = !empty($review['created_at']) ? strtotime((string) $review['created_at']) : false;
                $dateLabel = $created ? date('d M Y', $created) : '';
                $isGuest = !empty($review['is_guest']);
            ?>
                <article class="pd-review-card">
                    <div class="pd-review-card-top">
                        <div class="pd-review-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></div>
                        <div class="pd-review-meta">
                            <div class="pd-review-author-row">
                                <strong class="pd-review-author"><?= htmlspecialchars($author) ?></strong>
                                <?php if (!$isGuest): ?>
                                    <span class="pd-review-badge">Verified buyer</span>
                                <?php endif; ?>
                            </div>
                            <div class="pd-review-rating-row">
                                <?= pdReviewStarsHtml((float) $rating) ?>
                                <?php if ($dateLabel !== ''): ?>
                                    <time class="pd-review-date" datetime="<?= htmlspecialchars(date('Y-m-d', $created)) ?>"><?= htmlspecialchars($dateLabel) ?></time>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($content !== ''): ?>
                        <p class="pd-review-text"><?= nl2br(htmlspecialchars($content)) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="pd-reviews-empty">
                <div class="pd-reviews-empty-icon" aria-hidden="true">★</div>
                <h4>No reviews yet</h4>
                <p>Be the first to share your experience with this product.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
