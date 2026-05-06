<?php
// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: login.php");
    exit();
}

// Ensure the controller is included
require_once dirname(__DIR__, 1) . '/app/Controllers/AdminReviewController.php';
require_once dirname(__DIR__, 1) . '/includes/functions.php';
// Include the sidebar only if the admin is logged in
include __DIR__ . '/admin_sidebar.php';

// Include the header
include __DIR__ . '/admin_header.php';
// Initialize the controller
$controller = new AdminReviewController();

// Check if we have an id in the query string
if (isset($_GET['id'])) {
    $review = $controller->getReviewById($_GET['id']);
    if (!$review) {
        // If no review found, redirect back to manage reviews page
        header('Location: ' . url('admin/manage-reviews.php?error=Review not found'));
        exit;
    }
} else {
    // If no id is provided, redirect to manage reviews page
    header('Location: ' . url('admin/manage-reviews.php?error=Invalid review ID'));
    exit;
}

// Handle the form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = $_POST['author'];
    $email = $_POST['email'];
    $content = $_POST['content'];
    $rating = $_POST['rating'];

    // Update the review using the controller's update method
    if ($controller->updateReview($_POST['id'], $author, $email, $content, $rating)) {
        // On success, set success message and refresh review data
        $success_message = 'Review updated successfully';
        $review = $controller->getReviewById($_POST['id']); // Refresh review data to display updated values
    } else {
        // If update fails, set error message
        $error_message = 'Update failed';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --light-yellow: #fffbeb;
            --white: #ffffff;
            --bg: #f8fafc;
            --border: #e5e7eb;
            --muted: #6b7280;
        }

        body { background: var(--bg); color: var(--black); }

        .er-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px;
        }

        .er-header,
        .er-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(17, 17, 17, 0.06);
        }

        .er-header {
            padding: 20px 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .er-title { margin: 0; font-size: 1.8rem; letter-spacing: -0.02em; }
        .er-subtitle { margin: 6px 0 0; color: var(--muted); font-size: 0.92rem; }

        .er-card { padding: 22px; }

        .er-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-group { margin-bottom: 0; }
        .form-group.full { grid-column: 1 / -1; }
        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--black);
        }

        input, textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            color: var(--black);
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        input {
            height: 48px;
            padding: 0 14px;
        }

        textarea {
            min-height: 120px;
            padding: 14px;
            resize: vertical;
        }

        input:focus, textarea:focus,
        input:focus-visible, textarea:focus-visible {
            outline: none !important;
            border-color: var(--yellow);
            border-radius: 10px;
            box-shadow: 0 0 0 3px rgba(250,204,21,0.18);
        }

        .er-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .btn {
            height: 44px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid var(--black);
            background: var(--black);
            color: #fff !important;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: color .15s ease, transform .15s ease;
        }

        .btn:hover {
            color: var(--yellow) !important;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: #fff;
            color: var(--black) !important;
            border-color: var(--border);
        }
        .btn-outline:hover {
            color: var(--black) !important;
            border-color: var(--yellow);
            background: var(--light-yellow);
            transform: translateY(-1px);
        }

        .er-toast {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 4000;
            min-width: 260px;
            max-width: 380px;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
            font-weight: 800;
            border: 1px solid var(--border);
            box-shadow: 0 16px 30px rgba(17, 17, 17, 0.15);
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }
        .er-toast.is-show { opacity: 1; transform: translateY(0); }
        .er-toast-success { background: #111111; color: #ffffff; border-color: #111111; }
        .er-toast-error { background: var(--light-yellow); color: var(--black); border-color: var(--yellow); }

        @media (max-width: 900px) {
            .er-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="er-wrap">
        <div class="er-header">
            <div>
                <h2 class="er-title">Edit Review</h2>
                <p class="er-subtitle">Update review details and keep moderation records consistent.</p>
            </div>
        </div>

        <div class="er-card">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($review['id']); ?>">

                <div class="er-grid">
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($review['author']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($review['email']); ?>" required>
                    </div>
                    <div class="form-group full">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="5" required><?php echo htmlspecialchars($review['content']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <input type="number" id="rating" name="rating" min="1" max="5" value="<?php echo htmlspecialchars($review['rating']); ?>" required>
                    </div>
                </div>

                <div class="er-actions">
                    <button type="submit" class="btn">Update Review</button>
                    <a href="<?php echo htmlspecialchars(url('admin/manage-reviews.php')); ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($success_message || $error_message || isset($_GET['error'])): ?>
        <div id="erToast" class="er-toast <?php echo ($success_message ? 'er-toast-success' : 'er-toast-error'); ?>">
            <?php echo htmlspecialchars($success_message ?: ($error_message ?: $_GET['error'])); ?>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('erToast');
        if (toast) {
            requestAnimationFrame(() => toast.classList.add('is-show'));
            setTimeout(() => toast.classList.remove('is-show'), 3200);
        }
    });
    </script>
</body>
</html>