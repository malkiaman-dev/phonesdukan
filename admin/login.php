<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Create a database instance and get the connection
$database = new Database();
$conn = $database->getConnection(); // Fixes the "Undefined variable $conn" error

// Check if the user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . url('admin/dashboard.php'));
    exit();
}

$loginBackground = url('public/uploads/login.avif');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($conn) { // Ensure connection is established
        $stmt = $conn->prepare("SELECT id, name, password, role FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role']; // Add this
        
            header("Location: " . url('admin/dashboard.php'));
            exit();
        }
         else {
            $error = "Invalid credentials!";
        }
    } else {
        die("Database connection failed.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Phones Dukan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        /* Full page background */
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }

        .bg-image {
            background-image: url('<?= htmlspecialchars($loginBackground, ENT_QUOTES, 'UTF-8'); ?>');
            background-size: cover;
            background-position: center;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Form container */
        .login-container {
            background-color: rgba(255, 255, 255, 0.8); /* White background with some transparency */
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }

        h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .alert {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 5px;
            padding: 12px;
            font-size: 1rem;
        }

        .btn {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-size: 1.2rem;
            color: white;
            border-radius: 5px;
            width: 100%;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Adjustments for smaller screens */
        @media (max-width: 576px) {
            .login-container {
                padding: 20px;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Full screen background container -->
    <div class="bg-image">

        <!-- Login form container -->
        <div class="login-container">

            <h2>Admin Login</h2>

            <!-- Display error message if login fails -->
            <?php if (isset($error)) { ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <!-- Login form -->
            <form method="POST">
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </div>

</body>

</html>
