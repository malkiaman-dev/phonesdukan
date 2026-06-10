<?php
require_once dirname(__DIR__, 3) . '/database/db.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT full_name, email, phone, profile_photo FROM users WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$user_name    = isset($user['full_name'])    ? htmlspecialchars($user['full_name'])    : '';
$user_email   = isset($user['email'])        ? htmlspecialchars($user['email'])        : '';
$user_phone   = isset($user['phone'])        ? htmlspecialchars($user['phone'])        : '';
$profile_photo = $user['profile_photo'] ?? '';

$msg_type = '';
$message  = '';

// ── Handle profile photo upload ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['profile_photo'];
    $allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $maxBytes = 2 * 1024 * 1024; // 2 MB

    if (!in_array($file['type'], $allowed)) {
        $message  = "Only JPG, PNG, WebP, or GIF images are allowed.";
        $msg_type = 'error';
    } elseif ($file['size'] > $maxBytes) {
        $message  = "Photo must be smaller than 2 MB.";
        $msg_type = 'error';
    } else {
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'user_' . $user_id . '.' . $ext;
        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/profiles/';
        $destPath  = $uploadDir . $newName;

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Remove old photos for this user (any extension)
        foreach (glob($uploadDir . 'user_' . $user_id . '.*') as $old) {
            @unlink($old);
        }

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $photoUrl = 'public/uploads/profiles/' . $newName;
            $upd = $conn->prepare("UPDATE users SET profile_photo = :photo WHERE user_id = :user_id");
            $upd->bindParam(':photo',   $photoUrl);
            $upd->bindParam(':user_id', $user_id);
            $upd->execute();
            $profile_photo = $photoUrl;
            $message  = "Profile photo updated!";
            $msg_type = 'success';
        } else {
            $message  = "Failed to save the photo. Please try again.";
            $msg_type = 'error';
        }
    }
}

// ── Handle profile info update ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
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

<h1 class="db-page-title">Edit Profile</h1>

<div class="pf-shell">

    <!-- Left: info + avatar card -->
    <section class="pf-guide" aria-label="Profile guidance">

        <!-- Clickable avatar with camera overlay -->
        <form action="" method="POST" enctype="multipart/form-data" id="photoForm">
            <div class="pf-avatar-wrap" title="Click to change photo">
                <div class="pf-guide-avatar" id="avatarPreview">
                    <?php if ($profile_photo): ?>
                        <img src="<?= url(htmlspecialchars($profile_photo)) ?>?v=<?= time() ?>" alt="Profile photo" id="avatarImg">
                    <?php else: ?>
                        <span id="avatarInitials"><?= $initials ?></span>
                    <?php endif; ?>
                </div>
                <div class="pf-avatar-overlay" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <circle cx="12" cy="13" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span>Change</span>
                </div>
                <input type="file" name="profile_photo" id="photoInput" accept="image/jpeg,image/png,image/webp,image/gif" aria-label="Upload profile photo">
            </div>
        </form>

        <p class="pf-guide-name"><?= $user_name ?></p>
        <p class="pf-guide-email"><?= $user_email ?></p>

        <div class="pf-guide-divider"></div>

        <h2 class="pf-guide-title">Your Profile</h2>
        <p class="pf-guide-sub">Keep your information up to date so we can provide you with the best shopping experience.</p>

        <ul class="pf-tips-list">
            <li>
                <span class="pf-tip-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>Use your <strong>real name</strong> for order delivery</span>
            </li>
            <li>
                <span class="pf-tip-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>A valid <strong>phone number</strong> helps us reach you about orders</span>
            </li>
            <li>
                <span class="pf-tip-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>Your <strong>email</strong> is used for login and cannot be changed</span>
            </li>
            <li>
                <span class="pf-tip-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>Phone must start with <strong>03</strong> and be 11 digits</span>
            </li>
        </ul>

        <p class="pf-guide-trust">Max photo size: 2 MB. Supported: JPG, PNG, WebP.</p>
    </section>

    <!-- Right: form card -->
    <section class="pf-form-col">
        <div class="pf-card">
            <h2 class="pf-form-title">Update Information</h2>

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
                        <span class="pf-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="text" id="full_name" name="full_name" value="<?= $user_name ?>" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="pf-field">
                    <label for="email_display">Email Address</label>
                    <div class="pf-input-wrap">
                        <span class="pf-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </span>
                        <input type="email" id="email_display" value="<?= $user_email ?>" disabled>
                    </div>
                    <p class="pf-hint">Email cannot be changed.</p>
                </div>

                <div class="pf-field">
                    <label for="phone">Phone Number</label>
                    <div class="pf-input-wrap">
                        <span class="pf-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.86 19.86 0 0 1 3.08 4.18 2 2 0 0 1 5.07 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L9.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <input type="text" id="phone" name="phone" value="<?= $user_phone ?>" placeholder="03XXXXXXXXX" required>
                    </div>
                </div>

                <button type="submit" class="pf-btn">Save Changes</button>
            </form>
        </div>
    </section>

</div>

<style>
/* ── Two-column shell ─────────────────────────────────────────────────── */
.pf-shell {
    display: grid;
    grid-template-columns: minmax(0, 10fr) minmax(0, 9fr);
    gap: 24px;
    align-items: start;
}

/* ── Left guidance card ───────────────────────────────────────────────── */
.pf-guide {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 24px rgba(15,23,42,0.07);
    text-align: center;
}

/* ── Avatar with overlay ──────────────────────────────────────────────── */
.pf-avatar-wrap {
    position: relative;
    width: 90px;
    height: 90px;
    margin: 0 auto 14px;
    cursor: pointer;
}

.pf-guide-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #facc15;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: 700;
    color: #111827;
    overflow: hidden;
    border: 3px solid #e9edf4;
    transition: border-color 0.2s;
}

.pf-guide-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.pf-avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0,0,0,0.52);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    opacity: 0;
    transition: opacity 0.22s ease;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.4px;
}

.pf-avatar-overlay svg { width: 20px; height: 20px; }

.pf-avatar-wrap:hover .pf-avatar-overlay,
.pf-avatar-wrap:focus-within .pf-avatar-overlay { opacity: 1; }
.pf-avatar-wrap:hover .pf-guide-avatar { border-color: #facc15; }

/* hide native file input but keep it clickable via the whole wrap */
#photoInput {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    border-radius: 50%;
}

.pf-guide-name {
    margin: 0 0 4px;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.pf-guide-email {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    word-break: break-all;
}

.pf-guide-divider {
    height: 1px;
    background: #f3f4f6;
    margin: 20px 0;
}

.pf-guide-title {
    margin: 0 0 8px;
    font-size: 17px;
    font-weight: 700;
    color: #111827;
    text-align: left;
}

.pf-guide-sub {
    margin: 0 0 20px;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.65;
    text-align: left;
}

.pf-tips-list {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
    display: grid;
    gap: 12px;
    text-align: left;
}

.pf-tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    color: #374151 !important;
    font-style: normal !important;
    line-height: 1.45;
}

.pf-tips-list li span,
.pf-tips-list li strong {
    color: #374151 !important;
    font-style: normal !important;
    font-family: inherit !important;
}

.pf-tip-check {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #facc15;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1px;
}

.pf-tip-check svg { width: 13px; height: 13px; color: #111827; }

.pf-guide-trust {
    margin: 0;
    font-size: 12px;
    color: #9ca3af;
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
    text-align: left;
}

/* ── Right form card ──────────────────────────────────────────────────── */
.pf-form-col { display: flex; }

.pf-card {
    width: 100%;
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 24px rgba(15,23,42,0.07);
}

.pf-form-title {
    margin: 0 0 20px;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
}

.pf-msg {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 18px;
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
    height: 50px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 0 14px 0 42px;
    font-size: 15px;
    color: #111827;
    background: #fff;
    box-sizing: border-box;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.pf-input-wrap:focus-within .pf-icon { color: #facc15; }

.pf-input-wrap input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250,204,21,0.2);
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
    height: 50px;
    background: #111827;
    color: #facc15;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 4px;
    transition: background 0.25s ease, transform 0.2s ease;
}

.pf-btn:hover  { background: #1f2937; transform: translateY(-1px); }
.pf-btn:active { transform: translateY(0); }

/* ── Responsive ───────────────────────────────────────────────────────── */
@media (max-width: 860px) {
    .pf-shell {
        grid-template-columns: 1fr;
    }
    .pf-guide { text-align: left; }
    .pf-avatar-wrap { margin: 0 0 14px; }
}
</style>

<script>
(function () {
    var input   = document.getElementById('photoInput');
    var form    = document.getElementById('photoForm');
    var preview = document.getElementById('avatarPreview');

    if (!input || !form) return;

    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;

        // Client-side size check
        if (file.size > 2 * 1024 * 1024) {
            alert('Photo must be smaller than 2 MB.');
            input.value = '';
            return;
        }

        // Show instant local preview before upload
        var reader = new FileReader();
        reader.onload = function (e) {
            // Replace content with img or update existing img
            var img = preview.querySelector('img');
            var span = preview.querySelector('span');
            if (img) {
                img.src = e.target.result;
            } else {
                if (span) span.remove();
                var newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.alt = 'Profile photo';
                newImg.id  = 'avatarImg';
                preview.appendChild(newImg);
            }
        };
        reader.readAsDataURL(file);

        // Submit the photo form automatically
        form.submit();
    });
})();
</script>
