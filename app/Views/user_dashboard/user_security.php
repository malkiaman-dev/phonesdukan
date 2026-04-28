<?php
require_once dirname(__DIR__, 3) . '/database/db.php';  // Include database connection

// Initialize the database connection
$database = new Database();
$conn = $database->getConnection();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'] ?? 0;

// Initialize variables for errors and success messages
$error = "";
$success = "";

// Check if the form is submitted to change the password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch the current password hash from the database
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the current password is correct
    if (password_verify($current_password, $user['password_hash'])) {
        // Check if new password and confirm password match
        if ($new_password === $confirm_password) {
            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id");
            $update_stmt->bindParam(':password_hash', $new_password_hash);
            $update_stmt->bindParam(':user_id', $user_id);

            if ($update_stmt->execute()) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating the password. Please try again.";
            }
        } else {
            $error = "New password and confirm password do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <style>
        .security-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .form-container {
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            border-radius: 8px;
        }
        .form-container h2 {
            text-align: center;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
        }
        .form-container button:hover {
            background-color: #2980b9;
        }
        .form-container p {
            text-align: center;
            color: green;
            font-weight: bold;
        }
        .form-container .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="security-container">
    <div class="form-container">
        <h2>Change Password</h2>

        <!-- Display success or error message -->
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php elseif (!empty($success)): ?>
            <p><?php echo $success; ?></p>
        <?php endif; ?>

        <!-- Password Change Form -->
        <form action="" method="POST">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Update Password</button>
        </form>
    </div>
</div>

</body>
</html>
