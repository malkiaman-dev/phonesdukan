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

        $resetTimeLeft = max(0, strtotime($expires_at) - time());
        if ($resetTimeLeft > 0) {
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

<?php $timeLeft = isset($resetTimeLeft) ? $resetTimeLeft : 0; ?>

<style>
.rp-wrapper {
    display:flex; justify-content:center; align-items:center;
    min-height:60vh; padding:32px 16px; background:#f0f0f0; box-sizing:border-box;
}
.rp-card {
    background:#fff; padding:36px 32px; border-radius:12px;
    box-shadow:0 4px 24px rgba(0,0,0,.12); border-top:4px solid #f7d117;
    width:100%; max-width:420px;
}
.rp-card h2 {
    margin:0 0 24px; font-size:22px; font-weight:700; color:#111; text-align:center;
}
/* Timer bar */
.rp-timer-wrap { margin:0 0 20px; }
.rp-timer-bar-bg {
    height:6px; background:#e8e8e8; border-radius:3px; overflow:hidden; margin-bottom:6px;
}
.rp-timer-bar {
    height:6px; background:#f7d117; border-radius:3px;
    transition:width 0.9s linear, background 0.4s;
    width:100%;
}
.rp-timer-text {
    font-size:12px; color:#888; text-align:center; letter-spacing:.5px;
}
/* Expired */
.rp-expired {
    display:none; align-items:center; gap:8px; background:#fef2f2;
    color:#cc0000; font-size:13px; font-weight:600;
    padding:12px 16px; border-radius:6px; border-left:3px solid #cc0000; margin-bottom:20px;
}
/* Messages */
.rp-msg { font-size:13px; padding:10px 14px; border-radius:6px; margin-bottom:16px; }
.rp-msg--error   { background:#fef2f2; color:#cc0000; border-left:3px solid #cc0000; }
.rp-msg--info    { background:#fffbf0; color:#555; border-left:3px solid #f7d117; }
/* Form */
.rp-label { display:block; font-size:13px; font-weight:600; color:#111; margin-bottom:6px; }
.rp-input {
    width:100%; padding:13px 14px; font-size:15px; border:2px solid #e0e0e0;
    border-radius:8px; background:#f8f8f8; color:#111; box-sizing:border-box;
    transition:border-color .2s;
}
.rp-input:focus { outline:none; border-color:#f7d117; background:#fffbf0; }
.rp-btn {
    width:100%; padding:13px; background:#111; color:#f7d117; border:none;
    border-radius:8px; font-size:15px; font-weight:700; cursor:pointer;
    letter-spacing:.5px; margin-top:16px; transition:background .2s;
}
.rp-btn:hover { background:#222; }
@media(max-width:480px){ .rp-card{padding:28px 20px;} }
</style>

<div class="rp-wrapper">
  <div class="rp-card">
    <h2>Reset Password</h2>

    <?php if ($timeLeft > 0): ?>

      <!-- Progress bar timer -->
      <div class="rp-timer-wrap">
        <div class="rp-timer-bar-bg">
          <div class="rp-timer-bar" id="rpBar"></div>
        </div>
        <p class="rp-timer-text" id="rpTimerText">Link expires in <strong id="rpSeconds"><?= $timeLeft ?></strong>s</p>
      </div>

      <!-- Expired banner (shown by JS) -->
      <div class="rp-expired" id="rpExpired">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        This link has expired — please request a new password reset
      </div>

      <?php if (isset($message)): ?>
        <p class="rp-msg rp-msg--info"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <form action="" method="POST" id="rpForm">
        <label class="rp-label" for="new_password">New Password</label>
        <input class="rp-input" type="password" id="new_password" name="new_password"
               placeholder="Enter your new password" required minlength="8">
        <button class="rp-btn" type="submit" id="rpSubmit">Set New Password</button>
      </form>

    <?php else: ?>

      <!-- Already expired server-side -->
      <div class="rp-expired" style="display:flex;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= isset($message) ? htmlspecialchars($message) : 'This link has expired — please request a new password reset.' ?>
      </div>
      <div style="text-align:center;margin-top:8px;">
        <a href="/forgot-password" style="color:#f7d117;font-weight:600;font-size:14px;">Request New Link</a>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php if ($timeLeft > 0): ?>
<script>
(function () {
  const TOTAL   = <?= $timeLeft ?>;
  let   left    = TOTAL;

  const bar     = document.getElementById('rpBar');
  const secs    = document.getElementById('rpSeconds');
  const text    = document.getElementById('rpTimerText');
  const expired = document.getElementById('rpExpired');
  const form    = document.getElementById('rpForm');
  const submit  = document.getElementById('rpSubmit');

  function setExpired() {
    bar.style.width      = '0%';
    bar.style.background = '#cc0000';
    text.style.display   = 'none';
    expired.style.display = 'flex';
    form.style.display   = 'none';
  }

  function tick() {
    if (left <= 0) { setExpired(); return; }

    const pct = (left / TOTAL) * 100;
    bar.style.width = pct + '%';
    bar.style.background = left > 60 ? '#f7d117' : left > 20 ? '#f59e0b' : '#ef4444';
    secs.textContent = left;
    left--;
  }

  tick();
  const interval = setInterval(function () {
    tick();
    if (left < 0) clearInterval(interval);
  }, 1000);
})();
</script>
<?php endif; ?>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>