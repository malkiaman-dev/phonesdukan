<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Ensure $product_id is available
if (!isset($product_id) || $product_id <= 0):
    ?>
    <p style="color: red; font-weight: bold;">Review form is unavailable — invalid product ID.</p>
<?php
    return;  // Stop further execution of this file, but don't break the page
endif;

// Check if user is logged in and has a valid username
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn && isset($_SESSION['user_name']) && $_SESSION['user_name'] !== 'Guest' ? htmlspecialchars($_SESSION['user_name']) : 'Guest';
$userEmail = isset($_SESSION['email']) && !empty($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
$returnUrl = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';

// Debugging: Print session data to verify
// echo '<pre>';
// print_r($_SESSION);  // Debugging line
// echo '</pre>';
?>

<div class="review-form-container">
    <h3>Write a Review</h3>
    
    <?php if ($isLoggedIn): ?>
    <p class="logged-in-as">
        Logged in as <?= $userName ?>. 
        <a href="/my-account/?tab=profile">Edit your profile</a>. 
        <a href="/logout">Log out?</a> 
        <span class="required-field-message">
            Required fields are marked <span class="required">*</span>
        </span>
    </p>
    <?php else: ?>
    <?php endif; ?>

    <form action="/submit-review" method="post">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product_id) ?>">
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">

        <!-- Review Content -->
        <label for="content">Your Review:</label>
        <textarea name="content" id="content" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        
        <?php if ($isLoggedIn): ?>
        <input type="hidden" name="author" value="<?= $userName ?>"> <!-- Hidden field for author -->
        <input type="hidden" name="email" value="<?= $userEmail ?>"> <!-- Hidden field for email -->
        <?php else: ?>
            
        <!-- Name & Email Fields (Only for guests) -->
        <div class="form-row">
            <div>
                <label for="author">Name:</label>
                <input type="text" name="author" id="author" required value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
            </div>

            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <?php endif; ?>

        <!-- Star Rating -->
        <label>Rating:</label>
        <div class="star-rating">
            <input type="radio" id="star5" name="rating" value="5">
            <label for="star5">★</label>
            
            <input type="radio" id="star4" name="rating" value="4">
            <label for="star4">★</label>
            
            <input type="radio" id="star3" name="rating" value="3">
            <label for="star3">★</label>
            
            <input type="radio" id="star2" name="rating" value="2">
            <label for="star2">★</label>
            
            <input type="radio" id="star1" name="rating" value="1">
            <label for="star1">★</label>
        </div>

        <!-- Hidden Rating Field to ensure it is always sent -->
        <input type="hidden" name="rating" id="hidden-rating" value="1"> <!-- Default rating if none selected -->
        
        <input type="hidden" name="is_guest" value="<?= $isLoggedIn ? '0' : '1' ?>">
        
        <!-- Submit Button -->
        <button type="submit">Submit Review</button>
    </form>
</div>
