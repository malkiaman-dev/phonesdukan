<?php
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name  = isset($user['full_name']) ? htmlspecialchars($user['full_name']) : '';
$user_email = isset($user['email'])     ? htmlspecialchars($user['email'])     : '';
$user_phone = isset($user['phone'])     ? htmlspecialchars($user['phone'])     : '';

$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name  = trim($_POST['full_name'] ?? '');
    $new_phone = trim($_POST['phone']     ?? '');

    if (preg_match('/^03\d{9}$/', $new_phone)) {
        $upd = $conn->prepare("UPDATE users SET full_name = :full_name, phone = :phone WHERE user_id = :user_id");
        $upd->bindParam(':full_name', $new_name);
        $upd->bindParam(':phone',     $new_phone);
        $upd->bindParam(':user_id',   $user_id);

        if ($upd->execute()) {
            $message  = "Profile updated successfully!";
            $msg_type = 'success';
            $user_name  = htmlspecialchars($new_name);
            $user_phone = htmlspecialchars($new_phone);
        } else {
            $message  = "Error updating profile. Please try again.";
            $msg_type = 'error';
        }
    } else {
        $message  = "Phone number must start with 03 and be exactly 11 digits.";
        $msg_type = 'error';
    }
}

$initials = '';
foreach (explode(' ', $user_name) as $part) {
    $initials .= strtoupper(mb_substr($part, 0, 1));
}
$initials = mb_substr($initials, 0, 2);
?>

<div class="pf-wrap">
    <h1 class="db-page-title">Edit Profile</h1>

    <div class="pf-card">
        <!-- Avatar -->
        <div class="pf-avatar-row">
            <div class="pf-avatar"><?= $initials ?></div>
            <div>
                <p class="pf-av-name"><?= $user_name ?></p>
                <p class="pf-av-email"><?= $user_email ?></p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="pf-msg pf-msg--<?= $msg_type ?>">
            <?php if ($msg_type === 'success'): ?>
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="pf-form">
            <div class="pf-field">
                <label for="full_name">Full Name</label>
                <div class="pf-input-wrap">
                    <span class="pf-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="text" id="full_name" name="full_name" value="<?= $user_name ?>" placeholder="Enter your full name" required>
                </div>
            </div>

            <div class="pf-field">
                <label for="email_display">Email Address</label>
                <div class="pf-input-wrap">
                    <span class="pf-icon">
                        <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </span>
                    <input type="email" id="email_display" value="<?= $user_email ?>" disabled>
                </div>
                <p class="pf-hint">Email cannot be changed.</p>
            </div>

            <div class="pf-field">
                <label for="phone">Phone Number</label>
                <div class="pf-input-wrap">
                    <span class="pf-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.86 19.86 0 0 1 3.08 4.18 2 2 0 0 1 5.07 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L9.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input type="text" id="phone" name="phone" value="<?= $user_phone ?>" placeholder="03XXXXXXXXX" required>
                </div>
            </div>

            <button type="submit" class="pf-btn">Save Changes</button>
        </form>
    </div>
</div>

<style>
.pf-wrap { max-width: 560px; }

.pf-card {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 4px 18px rgba(15,23,42,0.06);
}

.pf-avatar-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f3f4f6;
}

.pf-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #facc15;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    flex-shrink: 0;
}

.pf-av-name  { margin: 0 0 2px; font-size: 16px; font-weight: 700; color: #111827; }
.pf-av-email { margin: 0; font-size: 13px; color: #6b7280; }

.pf-msg {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
}

.pf-msg svg { width: 18px; height: 18px; flex-shrink: 0; }

.pf-msg--success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.pf-msg--error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

.pf-form { display: grid; gap: 18px; }

.pf-field label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.pf-input-wrap { position: relative; }

.pf-icon {
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

.pf-icon svg { width: 18px; height: 18px; }

.pf-input-wrap input {
    width: 100%;
    height: 48px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 0 14px 0 42px;
    font-size: 15px;
    color: #111827;
    background: #fff;
    box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.pf-input-wrap input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250,204,21,0.18);
}

.pf-input-wrap input:disabled {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

.pf-hint {
    margin: 5px 0 0;
    font-size: 12px;
    color: #9ca3af;
}

.pf-btn {
    width: 100%;
    height: 48px;
    background: #111827;
    color: #facc15;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 4px;
}

.pf-btn:hover { background: #1f2937; }
</style>
