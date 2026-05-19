<?php
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id  = $_SESSION['user_id'] ?? 0;
$error    = '';
$success  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password  = $_POST['current_password']  ?? '';
    $new_password      = $_POST['new_password']      ?? '';
    $confirm_password  = $_POST['confirm_password']  ?? '';

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current_password, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :user_id");
        $upd->bindParam(':hash',    $hash);
        $upd->bindParam(':user_id', $user_id);
        if ($upd->execute()) {
            $success = "Password updated successfully!";
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<div class="sec-wrap">
    <h1 class="db-page-title">Security</h1>

    <div class="sec-card">
        <div class="sec-header">
            <div class="sec-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <h2>Change Password</h2>
                <p>Keep your account secure with a strong password.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
        <div class="sec-msg sec-msg--error">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php elseif (!empty($success)): ?>
        <div class="sec-msg sec-msg--success">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="sec-form">

            <div class="sec-field">
                <label for="current_password">Current Password</label>
                <div class="sec-input-wrap">
                    <span class="sec-icon">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                    <button type="button" class="sec-eye" data-target="current_password" aria-label="Toggle">
                        <?= eyeSVG() ?>
                    </button>
                </div>
            </div>

            <div class="sec-field">
                <label for="new_password">New Password</label>
                <div class="sec-input-wrap">
                    <span class="sec-icon">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" required>
                    <button type="button" class="sec-eye" data-target="new_password" aria-label="Toggle">
                        <?= eyeSVG() ?>
                    </button>
                </div>
            </div>

            <div class="sec-field">
                <label for="confirm_password">Confirm New Password</label>
                <div class="sec-input-wrap">
                    <span class="sec-icon">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" required>
                    <button type="button" class="sec-eye" data-target="confirm_password" aria-label="Toggle">
                        <?= eyeSVG() ?>
                    </button>
                </div>
            </div>

            <button type="submit" class="sec-btn">Update Password</button>
        </form>

        <div class="sec-tips">
            <p class="sec-tips-title">Password tips</p>
            <ul>
                <li>Use at least 8 characters</li>
                <li>Mix uppercase, lowercase, numbers and symbols</li>
                <li>Avoid using your name or email address</li>
            </ul>
        </div>
    </div>
</div>

<?php
function eyeSVG() {
    return '
    <svg class="eye-on" viewBox="0 0 24 24" fill="none">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
    </svg>
    <svg class="eye-off" viewBox="0 0 24 24" fill="none" style="display:none">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>';
}
?>

<style>
.sec-wrap { max-width: 520px; }

.sec-card {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 4px 18px rgba(15,23,42,0.06);
}

.sec-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
}

.sec-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #facc15;
}

.sec-icon-wrap svg { width: 24px; height: 24px; }

.sec-header h2 { margin: 0 0 4px; font-size: 17px; font-weight: 700; color: #111827; }
.sec-header p  { margin: 0; font-size: 13px; color: #6b7280; }

.sec-msg {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
}

.sec-msg svg { width: 18px; height: 18px; flex-shrink: 0; }
.sec-msg--success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.sec-msg--error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.sec-form { display: grid; gap: 18px; }

.sec-field label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.sec-input-wrap { position: relative; }

.sec-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.sec-icon svg { width: 18px; height: 18px; }

.sec-input-wrap input {
    width: 100%;
    height: 48px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 0 44px 0 42px;
    font-size: 15px;
    color: #111827;
    background: #fff;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.sec-input-wrap input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250,204,21,0.18);
}

.sec-eye {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: #9ca3af;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.sec-eye:hover { color: #374151; }
.sec-eye svg   { width: 18px; height: 18px; pointer-events: none; }

.sec-btn {
    width: 100%;
    height: 48px;
    background: #111827;
    color: #facc15;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 4px;
    transition: background 0.2s;
}

.sec-btn:hover { background: #1f2937; }

.sec-tips {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #f3f4f6;
}

.sec-tips-title {
    margin: 0 0 8px;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
}

.sec-tips ul {
    margin: 0;
    padding-left: 18px;
    display: grid;
    gap: 4px;
}

.sec-tips ul li {
    font-size: 13px;
    color: #6b7280;
}
</style>

<script>
(function () {
    document.querySelectorAll('.sec-eye').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id    = btn.getAttribute('data-target');
            var input = document.getElementById(id);
            var on    = btn.querySelector('.eye-on');
            var off   = btn.querySelector('.eye-off');
            if (!input) return;
            var show       = input.type === 'password';
            input.type     = show ? 'text' : 'password';
            on.style.display  = show ? 'none' : '';
            off.style.display = show ? ''     : 'none';
        });
    });
})();
</script>
