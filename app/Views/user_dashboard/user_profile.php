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

$stmt = $conn->prepare("SELECT full_name, phone FROM users WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$user_name = isset($user['full_name']) ? htmlspecialchars($user['full_name']) : '';
$user_phone = isset($user['phone']) ? htmlspecialchars($user['phone']) : '';

// Check if the form is submitted to update the profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = $_POST['full_name'];
    $new_phone = $_POST['phone'];

    // Validate phone number (should start with 03 and be 11 digits long)
    if (preg_match('/^03\d{9}$/', $new_phone)) {
        // Update user details in the database
        $update_stmt = $conn->prepare("UPDATE users SET full_name = :full_name, phone = :phone WHERE user_id = :user_id");
        $update_stmt->bindParam(':full_name', $new_name);
        $update_stmt->bindParam(':phone', $new_phone);
        $update_stmt->bindParam(':user_id', $user_id);

        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
        } else {
            $message = "Error updating profile. Please try again.";
        }
    } else {
        $message = "Phone number must start with 03 and be 11 digits long.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        .profile_container {
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
            color: red;
            font-weight: bold;
        }
        .form-container .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="profile_container">
    <div class="form-container">
        <h2>Edit Profile</h2>

        <!-- Display success or error message -->
        <?php if (isset($message)): ?>
            <p class="<?php echo isset($error) ? 'error' : ''; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Profile Update Form -->
        <form action="" method="POST">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?= $user_name ?>" required>

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" value="<?= $user_phone ?>" required>

            <button type="submit">Update Profile</button>
        </form>
    </div>
</div>

</body>
</html>
