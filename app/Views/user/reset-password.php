<?php
ob_start();
require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/includes/header.php';
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Check if the token is valid and not expired
    $stmt = $conn->prepare("SELECT user_id, reset_token_expires_at FROM users WHERE reset_token = :token");
    $stmt->bindParam(":token", $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $expires_at = $user['reset_token_expires_at'];

        if (strtotime($expires_at) > time()) {
            // Token is valid, allow password reset
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $new_password = $_POST['new_password'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in the database
                $updateStmt = $conn->prepare("UPDATE users SET password_hash = :password_hash, reset_token = NULL, reset_token_expires_at = NULL WHERE user_id = :user_id");
                $updateStmt->bindParam(":password_hash", $hashed_password);
                $updateStmt->bindParam(":user_id", $user['user_id']);
                $updateStmt->execute();

                // Password reset successful
                $message = "Your password has been reset successfully.";

                // Redirect to the login page after successful password reset
                header("Location: /login");
                exit();  // Ensure the rest of the script doesn't execute
            }
        } else {
            $message = "This reset link has expired.";
        }
    } else {
        $message = "Invalid reset token.";
    }
} else {
    $message = "No reset token found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        .reset-form-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%; /* Ensures the form is vertically centered */
    width: 100%; /* Ensures the form is horizontally centered */
    margin: 20px 0;
}
.reset-container {
    background-color: #fff;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    width: 400px;
    border-radius: 8px;
}

.reset-container h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #333;
}

label {
    display: block;
    font-size: 14px;
    margin-bottom: 5px;
    color: #333;
}

.reset-container input {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}

.reset-container input:focus {
    outline: none;
    border-color: #4CAF50;
}

button {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .reset-form-wrapper {
        margin: 0px;

    }
}

    </style>
</head>
<body>
<div class="reset-form-wrapper">
<div class="reset-container">
    <h2>Reset Password</h2>

    <?php if (isset($message)) { echo "<p>$message</p>"; } ?>

    <?php if (!isset($message) || strpos($message, 'successfully') !== false): ?>
        <form action="" method="POST">
            <label for="new_password">Enter New Password:</label>
            <input type="password" name="new_password" required><br>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
    </div>
    </div>
</body>
</html>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>