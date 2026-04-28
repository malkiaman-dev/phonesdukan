<?php
ob_start();
require_once dirname(__DIR__, 3) . '/includes/header.php';
// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /my-account");
    exit();
}

require_once dirname(__DIR__, 3) . '/database/db.php';  // Include database connection
require_once __DIR__ . '/../../Controllers/UserController.php';  // Ensure correct path for UserController.php

// Initialize database connection
$database = new Database();
$db = $database->getConnection();  // Get the database connection

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Create an instance of the UserController and pass the database connection
    $controller = new UserController($db);

    // Call the login method in the UserController
    $loginResult = $controller->login($email, $password);

    // If the login was successful
    if ($loginResult === true) {
        // Redirect to the dashboard
        header("Location: /my-account");
        exit();
    } else {
        // If login fails, set the message with the error returned
        $message = $loginResult;
    }
}
?>
<div class="login-form-wrapper">
    <div class="login-container">
        <h2>Login</h2>

        <!-- Displaying error messages -->
        <?php if (isset($message)) { echo "<p class='error-message'>$message</p>"; } ?>

        <!-- Login Form -->
        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Email" required><br>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" placeholder="Password" required><br>
            </div>

            <div class="form-group">
                <button type="submit">Login</button>
            </div>
        </form>

        <!-- Forgot Password Link -->
        <div class="forgot-password-link">
            <p><a href="/forgot-password">Forgot Password?</a></p>
        </div>

        <!-- Register Link -->
        <div class="register-link">
            <p>Don't have an account? <a href="/register">Register here</a></p>
        </div>
    </div>
</div>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>