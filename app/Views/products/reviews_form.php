<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($product_id) || (int) $product_id <= 0) {
    echo '<p class="pd-review-form-error">Review form is unavailable — invalid product ID.</p>';
    return;
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn && !empty($_SESSION['user_name']) && $_SESSION['user_name'] !== 'Guest'
    ? (string) $_SESSION['user_name']
    : 'Guest';
$userEmail = !empty($_SESSION['email']) ? (string) $_SESSION['email'] : '';
$returnUrl = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$submitAction = function_exists('url') ? url('submit-review') : '/submit-review';
?>

<div class="pd-review-form">
    <h3 class="pd-review-form-title">Write a review</h3>
    <p class="pd-review-form-sub">Share your experience to help other buyers.</p>

    <?php if ($isLoggedIn): ?>
        <p class="pd-review-form-logged">
            Reviewing as <strong><?= htmlspecialchars($userName) ?></strong>
        </p>
    <?php endif; ?>

    <form class="pd-review-form-el" action="<?= htmlspecialchars($submitAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <input type="hidden" name="product_id" value="<?= (int) $product_id ?>">
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="is_guest" value="<?= $isLoggedIn ? '0' : '1' ?>">

        <div class="pd-review-field">
            <span class="pd-review-label">Your rating <span class="required">*</span></span>
            <div class="star-rating" role="radiogroup" aria-label="Product rating">
                <input type="radio" id="star5" name="rating" value="5" required>
                <label for="star5" title="5 stars">★</label>
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4" title="4 stars">★</label>
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3" title="3 stars">★</label>
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2" title="2 stars">★</label>
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1" title="1 star">★</label>
            </div>
        </div>

        <div class="pd-review-field">
            <label class="pd-review-label" for="content">Your review <span class="required">*</span></label>
            <textarea name="content" id="content" rows="5" required
                      placeholder="What did you like or dislike? How is the quality and value?"></textarea>
        </div>

        <?php if ($isLoggedIn): ?>
            <input type="hidden" name="author" value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
            <div class="pd-review-grid">
                <div class="pd-review-field">
                    <label class="pd-review-label" for="author">Name <span class="required">*</span></label>
                    <input type="text" name="author" id="author" required maxlength="120" placeholder="Your name">
                </div>
                <div class="pd-review-field">
                    <label class="pd-review-label" for="email">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" required maxlength="190" placeholder="you@example.com">
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="pd-review-submit">Submit review</button>
    </form>
</div>
