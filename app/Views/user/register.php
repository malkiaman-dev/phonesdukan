<?php
ob_start();
require_once __DIR__ . '/../../Controllers/UserController.php';
require_once dirname(__DIR__, 3) . '/includes/header.php';
$controller = new UserController();

// Check if user is already logged in and redirect to dashboard if true
if (isset($_SESSION['user_id'])) {
    header("Location: /my-account");  // Redirect logged-in users to the dashboard
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];

    // Call register method from the controller and pass the details
    $registered = $controller->register($full_name, $email, $password, $phone);

    // If registration is successful or OTP was resent for unverified user
    if ($registered === true) {
        // Store email in session
        $_SESSION['email'] = $email;
        $_SESSION['otp_sent'] = true;

        // Redirect to OTP verification page
        header("Location: /verify");
        exit();
    } else {
        // Show the returned error string from register() function
        $message = $registered;
    }
}

?>

<!-- Registration Form -->
<div class="registration-form-wrapper">
    <div class="registration-form">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="text" name="phone" id="phone" placeholder="Phone" required maxlength="11" 
           pattern="^03\d{9}$" oninput="validatePhone()" 
           title="Phone number must start with 03 and have 11 digits" />            <button type="submit">Register</button>
        </form>
        <script>
    function validatePhone() {
        const phoneInput = document.getElementById('phone');
        // Allow only digits and limit to 11 digits
        phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '').slice(0, 11);

        // Optionally, restrict the phone number to start with '03'
        if (phoneInput.value.length >= 2 && !phoneInput.value.startsWith('03')) {
            phoneInput.setCustomValidity('Phone number must start with "03"');
        } else {
            phoneInput.setCustomValidity('');
        }
    }
</script>
        <?php if (isset($message)): ?>
            <p class="error-message"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Login Link -->
        <p>Already have an account? <a href="/login" class="login-link">Login here</a></p>
    </div>
</div>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>