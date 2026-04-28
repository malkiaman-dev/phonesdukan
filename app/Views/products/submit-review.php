<?php
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/app/Models/ReviewModel.php'; // Include ReviewModel

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $reviewContent = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
    $authorName = isset($_POST['author']) ? htmlspecialchars($_POST['author']) : '';
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';

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
            'product_name' => $product_name // Save product name instead of ID
        ];
    } else {
        $_SESSION['error'] = 'There was an issue with submitting your review. Please try again.';
    }
}
?>

<title>Thank You for Your Review</title>
<div class="thank-you-message">
<?php if (isset($_SESSION['review'])): ?>
    <h3>Thank you for your review, <?= htmlspecialchars($_SESSION['review']['author']); ?>!</h3>
    <p>Your review for product "<strong><?= htmlspecialchars($_SESSION['review']['product_name']); ?></strong>" has been submitted successfully.</p>
    <h4>Your Review:</h4>
    <p><?= nl2br(htmlspecialchars($_SESSION['review']['content'])); ?></p>
<?php else: ?>
    <p>There was an issue with submitting your review. Please try again.</p>
<?php endif; ?>
</div>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
