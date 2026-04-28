<?php
ob_start();
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/app/Models/UserModel.php';  // Make sure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Generate a unique reset token
        $token = bin2hex(random_bytes(16));  // Generate a random 16-byte token

        // Set expiration time to 5 minutes from now
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Save the token and expiration time in the database
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['user_id'];

        // Update the database with reset token and expiration
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires_at = :expires_at WHERE user_id = :user_id");
        $updateStmt->bindParam(":token", $token);
        $updateStmt->bindParam(":expires_at", $expires_at);
        $updateStmt->bindParam(":user_id", $user_id);
        $updateStmt->execute();

        // Send reset password email
        $userModel = new User($conn);
        $userModel->sendPasswordResetEmail($email, $token);

        $message = "A password reset link has been sent to your email.";
    } else {
        $message = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        .forgot-form-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%; /* Ensures the form is vertically centered */
    width: 100%; /* Ensures the form is horizontally centered */
    margin: 20px 0;
}
.forgot-container {
    background-color: #fff;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
    width: 400px;
    border-radius: 8px;
}

.forgot-container h2 {
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

.forgot-container input {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 15px;
}

.forgot-container input:focus {
    outline: none;
    border-color: #4CAF50;  /* Green border on focus */
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
    .forgot-form-wrapper {
        margin: 0px;

    }
}
    </style>
</head>
<body>
<div class="forgot-form-wrapper">
<div class="forgot-container">
    <h2>Forgot Password</h2>

    <?php if (isset($message)) { echo "<p>$message</p>"; } ?>

    <form action="" method="POST">
        <label for="email">Enter your email address:</label>
        <input type="email" name="email" placeholder="Email" required><br>

        <button type="submit">Send Reset Link</button>
    </form>
</div>
</div>
</body>
</html>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
