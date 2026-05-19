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
                $verified = true;
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

<?php
$otpTimeLeft = max(0, 60 - ($currentTime - $_SESSION['otp_timestamp']));
?>
<?php if (!empty($verified)): ?>
<div class="verify-form-wrapper">
  <div class="verify-container">
    <div class="vt-success-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" fill="#f0fdf4" stroke="#22c55e" stroke-width="2"/>
        <path d="M7 12.5l3.5 3.5L17 8" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <h2 class="vt-success-title">Registration Successful!</h2>
    <p class="vt-success-text">Your account has been verified successfully. You can now log in to your account.</p>
    <p class="vt-redirect-note">Redirecting to login in <strong id="vtRedirectCount">3</strong> seconds…</p>
    <a href="/login" class="vt-login-btn">Go to Login</a>
  </div>
</div>
<style>
.vt-success-icon { margin: 0 auto 16px; width: 72px; height: 72px; }
.vt-success-icon svg { width: 72px; height: 72px; }
.vt-success-title { margin: 0 0 10px; font-size: 22px; font-weight: 700; color: #166534; }
.vt-success-text { margin: 0 0 16px; font-size: 14px; color: #4b5563; line-height: 1.6; }
.vt-redirect-note { font-size: 13px; color: #6b7280; margin: 0 0 20px; }
.vt-login-btn {
    display: inline-block;
    width: 100%;
    padding: 13px;
    background: #111111;
    color: #f7d117;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    text-align: center;
    box-sizing: border-box;
    transition: background 0.2s;
}
.vt-login-btn:hover { background: #222222; }
</style>
<script>
(function () {
    var count = 3;
    var el = document.getElementById('vtRedirectCount');
    var t = setInterval(function () {
        count--;
        if (el) el.textContent = count;
        if (count <= 0) { clearInterval(t); window.location.href = '/login'; }
    }, 1000);
})();
</script>
<?php else: ?>
<div class="verify-form-wrapper">
  <div class="verify-container">

    <h2>OTP Verification</h2>
    <p class="verify-hint">Code sent to <strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if (isset($error)): ?>
      <p class="verify-msg verify-msg--error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
      <p class="verify-msg verify-msg--success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <!-- Countdown ring -->
    <div class="vt-ring-wrap">
      <svg class="vt-svg" viewBox="0 0 100 100">
        <circle class="vt-bg"   cx="50" cy="50" r="40"/>
        <circle class="vt-ring" cx="50" cy="50" r="40" id="vtRing"
                stroke-dasharray="251.33" stroke-dashoffset="0"/>
      </svg>
      <div class="vt-center">
        <span class="vt-number" id="vtNumber"><?= $otpTimeLeft ?></span>
        <span class="vt-unit">sec</span>
      </div>
    </div>
    <p class="vt-status" id="vtStatus">Time remaining</p>

    <!-- OTP form (hidden when expired) -->
    <div id="otpFormWrap">
      <form method="POST" id="otpForm">
        <div class="form-group">
          <input type="text" name="otp" id="otpInput"
                 maxlength="6" placeholder="Enter 6-digit code"
                 autocomplete="one-time-code" inputmode="numeric" required>
        </div>
        <div class="form-group">
          <button type="submit" id="submitBtn">Verify OTP</button>
        </div>
      </form>
    </div>

    <!-- Expired banner (shown when timer hits 0) -->
    <div class="vt-expired" id="vtExpired" style="display:none;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2.5"
           stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      OTP Expired — request a new code below
    </div>

    <!-- Resend form -->
    <div class="resend-otp">
      <form method="POST">
        <button type="submit" name="resend_otp" id="resendButton" <?= $otpTimeLeft > 0 ? 'disabled' : '' ?>>
          Resend OTP
        </button>
      </form>
    </div>

  </div>
</div>
<?php endif; ?>

<?php if (empty($verified)): ?>
<script>
(function () {
  const TOTAL       = 60;
  const CIRC        = 251.33;
  let   timeLeft    = <?= $otpTimeLeft ?>;

  const ring        = document.getElementById('vtRing');
  const number      = document.getElementById('vtNumber');
  const status      = document.getElementById('vtStatus');
  const formWrap    = document.getElementById('otpFormWrap');
  const expiredBanner = document.getElementById('vtExpired');
  const resendBtn   = document.getElementById('resendButton');
  const otpInput    = document.getElementById('otpInput');
  const submitBtn   = document.getElementById('submitBtn');

  function setExpired() {
    ring.style.stroke          = '#cc0000';
    ring.style.strokeDashoffset = CIRC;
    number.textContent         = '0';
    status.textContent         = 'OTP Expired';
    status.style.color         = '#cc0000';
    formWrap.style.display     = 'none';
    expiredBanner.style.display = 'flex';
    resendBtn.disabled         = false;
  }

  function tick() {
    if (timeLeft <= 0) { setExpired(); return; }

    const fraction = timeLeft / TOTAL;
    ring.style.strokeDashoffset = CIRC * (1 - fraction);

    // Colour shifts: gold → orange → red as time runs low
    if (timeLeft > 20) {
      ring.style.stroke = '#f7d117';
    } else if (timeLeft > 10) {
      ring.style.stroke = '#f59e0b';
    } else {
      ring.style.stroke = '#ef4444';
    }

    number.textContent = timeLeft;
    timeLeft--;
  }

  tick(); // draw immediately
  const interval = setInterval(function () {
    tick();
    if (timeLeft < 0) clearInterval(interval);
  }, 1000);

  // If already expired on load
  if (timeLeft <= 0) setExpired();
})();
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>