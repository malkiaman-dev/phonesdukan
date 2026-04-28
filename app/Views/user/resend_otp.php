<?php
ob_start();
require_once dirname(__DIR__, 3) . '/app/config/session.php';  // session_start() is handled in session.php
require_once dirname(__DIR__, 3) . '/app/Models/UserModel.php';  // Ensure User model is included
require_once dirname(__DIR__, 3) . '/includes/header.php';
// At this point, the session is already started by session.php
// No need to call session_start() again

// Check if email exists in the session
if (!isset($_SESSION['email'])) {
    // If email is not found in session, redirect to the login page
    header("Location: /login");
    exit();  // Stop further code execution
}

$email = $_SESSION['email'];  // Get email from session

// Get the database connection
require_once dirname(__DIR__, 3) . '/database/db.php';
$database = new Database();
$db = $database->getConnection();

// Initialize User model
$userModel = new User($db);

// Check if the account is locked
$query = "SELECT is_locked FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

// If the account is locked, display a message and stop the process
if ($result['is_locked']) {
    echo "Your account is locked due to too many failed OTP attempts. Please contact support.";
    exit();  // Stop further execution
}

// Generate a new OTP
$otp = rand(100000, 999999);  // Generate a new OTP

// Store the new OTP and its timestamp in session
$_SESSION['otp'] = $otp;
$_SESSION['otp_timestamp'] = time();  // Store the current timestamp for OTP

// Send the new OTP to the user's email
$userModel->sendOTP($email, $otp); // Send OTP using the User model

// Display a success message
echo "A new OTP has been sent to your email.";
?>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>