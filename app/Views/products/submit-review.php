<?php
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/app/Models/ReviewModel.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $reviewContent = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
    $authorName = isset($_POST['author']) ? htmlspecialchars($_POST['author']) : '';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
    $return_url = isset($_POST['return_url']) ? (string)$_POST['return_url'] : '/';

    // Keep redirects local-only and fallback safely.
    if ($return_url === '' || strpos($return_url, '://') !== false || strpos($return_url, '//') === 0) {
        $return_url = '/';
    }
    if ($return_url[0] !== '/') {
        $return_url = '/' . ltrim($return_url, '/');
    }

    // Validate rating (default to 5 if not valid)
    $rating = (isset($_POST['rating']) && $_POST['rating'] >= 1 && $_POST['rating'] <= 5)
        ? (int)$_POST['rating']
        : 5;

    // Initialize the review model
    $reviewModel = new ReviewModel();

    // Get logged-in user ID (if any)
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $is_guest = $user_id ? 0 : 1;

    // Fetch product name using your new model function
    $product_name = $reviewModel->getProductNameById($product_id);

    // Prepare review data
    $reviewData = [
        'product_id' => $product_id,
        'content' => $reviewContent,
        'author' => $authorName,
        'email' => $email,
        'rating' => $rating,
        'user_id' => $user_id,
        'is_guest' => $is_guest
    ];

    // Insert review into database
    if ($reviewModel->addReview($reviewData)) {
        $_SESSION['review'] = [
            'author' => $authorName,
            'content' => $reviewContent,
            'email' => $email,
            'product_name' => $product_name,
            'return_url' => $return_url
        ];
    } else {
        $_SESSION['error'] = 'There was an issue with submitting your review. Please try again.';
    }
}
?>

<title>Thank You for Your Review – Phones Dukan</title>

<div class="rs-page">
    <div class="rs-container">

        <?php if (isset($_SESSION['review'])): ?>

            <div class="rs-card">

                <!-- Success icon -->
                <div class="rs-icon-wrap rs-icon-wrap--success">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>

                <h1 class="rs-heading">Thank you for your review!</h1>
                <p class="rs-sub">
                    Your review has been submitted and is pending approval.
                    We appreciate your feedback.
                </p>

                <!-- Review preview -->
                <div class="rs-preview">
                    <span class="rs-preview-product">
                        <?= htmlspecialchars($_SESSION['review']['product_name'] ?? 'Product') ?>
                    </span>
                    <p class="rs-preview-author">
                        <?= htmlspecialchars($_SESSION['review']['author'] ?? 'Anonymous') ?>
                    </p>
                    <p class="rs-preview-content">
                        "<?= nl2br(htmlspecialchars($_SESSION['review']['content'] ?? '')) ?>"
                    </p>
                </div>

                <!-- Action buttons -->
                <div class="rs-actions">
                    <button class="rs-btn rs-btn--primary" onclick="history.back()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Back to Product
                    </button>
                    <a href="<?= htmlspecialchars($_SESSION['review']['return_url'] ?? '/') ?>" class="rs-btn rs-btn--secondary">
                        Continue Shopping
                    </a>
                </div>

            </div>

        <?php else: ?>

            <div class="rs-card rs-card--error">

                <!-- Error icon -->
                <div class="rs-icon-wrap rs-icon-wrap--error">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>

                <h1 class="rs-heading">Something went wrong</h1>
                <p class="rs-sub">
                    <?= htmlspecialchars($_SESSION['error'] ?? 'There was an issue submitting your review. Please try again.') ?>
                </p>

                <div class="rs-actions">
                    <button class="rs-btn rs-btn--primary" onclick="history.back()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                        Try Again
                    </button>
                    <a href="/" class="rs-btn rs-btn--secondary">Go Home</a>
                </div>

            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
