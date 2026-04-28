<?php
require_once dirname(__DIR__, 3) . '/app/Controllers/ReviewController.php';

$reviewController = new ReviewController(); // instantiate controller
$reviews = $reviewController->getApprovedByProduct($product['product_id']);
?>

<div class="reviews-container">
    <h3>Customer Reviews</h3>
    <?php if (!empty($reviews)) : ?>
        <?php foreach ($reviews as $review) : ?>
            <div class="review">
                <strong><?= htmlspecialchars($review['author']) ?></strong> 
                <span class="rating">⭐ <?= $review['rating'] ?>/5</span>
                <p><?= htmlspecialchars($review['content']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>No reviews yet. Be the first to review!</p>
    <?php endif; ?>
</div>
