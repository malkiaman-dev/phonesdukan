<?php
ob_start();
require_once dirname(__DIR__, 3) . '/app/Models/UserModel.php';  // Include UserModel
require_once dirname(__DIR__, 3) . '/database/db.php';  // Include database connection
require_once dirname(__DIR__, 3) . '/includes/header.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);  // Create User model instance

// Ensure email is set in session (user must have completed the registration process)
if (!isset($_SESSION['email'])) {
    // If the user hasn't completed registration (email is not set in session), redirect to register page
    header("Location: /register");
    exit();
}

// Ensure OTP has been sent (user must have clicked register and completed the process)
if (!isset($_SESSION['otp_sent'])) {
    // If OTP is not sent, the user shouldn't be accessing this page
    header("Location: /register");
    exit();
}

// Ensure the user is not already verified
if (isset($_SESSION['is_verified']) && $_SESSION['is_verified'] == 1) {
    // If the user is already verified, redirect them to the dashboard
    header("Location: /login");
    exit();
}

$email = $_SESSION['email'];  // Get the email from the session
$currentTime = time();  // Get current timestamp

// If no OTP timestamp is set, initialize OTP process
if (!isset($_SESSION['otp_timestamp'])) {
    $_SESSION['otp_timestamp'] = $currentTime;  // Set the timestamp
    $_SESSION['otp'] = rand(100000, 999999);  // Generate new OTP
    $_SESSION['otp_sent'] = true;  // Mark OTP as sent
    $userModel->updateAndSendOTP($email, $_SESSION['otp']);  // Send OTP to user's email
}

// Handle form submission (user submits OTP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $inputOtp = $_POST['otp'];  // Get entered OTP
    $otpTimestamp = $_SESSION['otp_timestamp'];  // Get stored timestamp
    $otpStored = $_SESSION['otp'];  // Get OTP stored in session

    // Check if the OTP is entered within 1 minute
    if (($currentTime - $otpTimestamp) <= 60) {
        // OTP is valid if it matches the one in the session
        if ($inputOtp == $otpStored) {
            // OTP matched, proceed with verification (update database and session)
            $updateSuccess = $userModel->verifyOTP($email, $inputOtp);  // Call to verifyOTP function

            if ($updateSuccess) {
                $_SESSION['is_verified'] = 1;  // Store the verified status in session

                // Redirect to the dashboard after successful OTP verification
                header("Location: /login");
                exit();
            } else {
                // If database update failed, show error
                $error = "Error verifying the OTP, please try again.";
            }
        } else {
            // OTP mismatch within 1 minute
            $error = "Wrong OTP. Please try again.";
        }
    } else {
        // OTP expired (more than 1 minute)
        $_SESSION['otp_timestamp'] = $currentTime;  // Reset timestamp for future OTP validation
        $error = "OTP expired. Request a new one after 1 minute.";
    }
}

// Handle Resend OTP action
if (isset($_POST['resend_otp'])) {
    $_SESSION['otp'] = rand(100000, 999999);
    $_SESSION['otp_timestamp'] = time();  // Update timestamp for new OTP
    $userModel->updateAndSendOTP($email, $_SESSION['otp']);
    $success = "A new OTP has been sent to your email.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <script>
        // Calculate the time remaining for the button to be enabled (1 minute countdown)
        let otpTimestamp = <?php echo $_SESSION['otp_timestamp']; ?>;
        let currentTime = <?php echo $currentTime; ?>;
        let timeRemaining = 60 - (currentTime - otpTimestamp);

        // Function to update the countdown timer
        function updateTimer() {
            if (timeRemaining > 0) {
                document.getElementById('timer').innerText = "Time remaining: " + timeRemaining + " seconds";
                timeRemaining--;
            } else {
                document.getElementById('timer').innerText = "You can now resend the OTP.";
                document.getElementById('resendButton').disabled = false; // Enable the resend button
            }
        }

        // Start the timer
        setInterval(updateTimer, 1000);
    </script>
</head>
<body>
    <div class="verify-form-wrapper">
<div class="verify-container">
        <h2>Enter OTP</h2>

        <?php if (isset($error)) { echo "<p class='error-message'>$error</p>"; } ?>
        <?php if (isset($success)) { echo "<p class='success-message'>$success</p>"; } ?>

        <!-- OTP Form -->
        <form method="POST">
            <div class="form-group">
                <label for="otp">OTP: </label>
                <input type="text" name="otp" id="otp" required>
            </div>
            <div class="form-group">
                <button type="submit">Verify OTP</button>
            </div>
        </form>

        <!-- Timer for OTP -->
        <div id="timer">
            <!-- Timer will display here -->
        </div>

        <!-- Resend OTP Form -->
        <div class="resend-otp">
            <form method="POST">
                <button type="submit" name="resend_otp" id="resendButton" disabled>Resend OTP</button>
            </form>
        </div>
    </div>
    </div>
</body>
</html>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>