<?php
ob_start();
require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/app/Models/UserModel.php';  // Make sure this path is correct

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Generate a unique reset token
        $token = bin2hex(random_bytes(16));  // Generate a random 16-byte token

        // Set expiration time to 5 minutes from now
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Save the token and expiration time in the database
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['user_id'];

        // Update the database with reset token and expiration
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_token_expires_at = :expires_at WHERE user_id = :user_id");
        $updateStmt->bindParam(":token", $token);
        $updateStmt->bindParam(":expires_at", $expires_at);
        $updateStmt->bindParam(":user_id", $user_id);
        $updateStmt->execute();

        // Send reset password email
        $userModel = new User($conn);
        $userModel->sendPasswordResetEmail($email, $token);

        $message = "A password reset link has been sent to your email.";
    } else {
        $message = "No account found with that email address.";
    }
}
?>

<div class="pd-auth-page">
    <div class="pd-auth-shell">
        <section class="pd-auth-content" aria-label="Forgot password guidance">
            <h1 class="pd-auth-heading">
                Reset Your Password
                <span class="pd-auth-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
            </h1>
            <p class="pd-auth-subtext">
                Enter your email and we’ll send you a secure link to reset your password.
            </p>

            <div class="pd-auth-visual" aria-hidden="true">
                <svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg" role="img">
                    <rect x="35" y="32" width="530" height="236" rx="22" fill="#ffffff" stroke="#e8edf4"/>
                    <rect x="80" y="86" width="210" height="138" rx="14" fill="#f8fafc" stroke="#e5e7eb"/>
                    <rect x="108" y="112" width="154" height="12" rx="6" fill="#e5e7eb"/>
                    <rect x="108" y="134" width="134" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="108" y="154" width="145" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="108" y="176" width="95" height="30" rx="15" fill="#facc15"/>
                    <circle cx="417" cy="129" r="70" fill="#fff8cc"/>
                    <rect x="388" y="124" width="58" height="62" rx="10" fill="#111827"/>
                    <path d="M397 124v-10a20 20 0 0 1 40 0v10" stroke="#111827" stroke-width="8" fill="none" stroke-linecap="round"/>
                    <circle cx="417" cy="152" r="7" fill="#facc15"/>
                </svg>
            </div>

            <ul class="pd-auth-features">
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Secure password reset</span>
                </li>
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Quick email delivery</span>
                </li>
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Easy account recovery</span>
                </li>
            </ul>
            <p class="pd-auth-trust">Built with customer security and convenience in mind.</p>
        </section>

        <section class="pd-auth-form-col">
            <div class="pd-auth-card">
                <h2 class="pd-auth-form-title">Forgot Password</h2>

                <?php if (isset($message)) : ?>
                    <p class="pd-auth-alert" role="alert"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>

                <form action="" method="POST" class="pd-auth-form">
                    <div class="pd-auth-field">
                        <label for="forgot-email">Email</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/>
                                    <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input id="forgot-email" type="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <button type="submit" class="pd-auth-btn">Send Reset Link</button>
                </form>

                <p class="pd-auth-bottom-link">
                    <a href="/login">Back to login</a>
                </p>
            </div>
        </section>
    </div>
</div>

<style>
.pd-auth-page {
    background: linear-gradient(145deg, #f5f7fb 0%, #ffffff 55%, #f2f5fb 100%);
    padding: 40px 16px 56px;
}

.pd-auth-shell {
    max-width: 1160px;
    margin: 0 auto;
    display: grid;
    gap: 24px;
    align-items: stretch;
}

.pd-auth-content,
.pd-auth-card {
    background: #fff;
    border: 1px solid #e9edf4;
    border-radius: 16px;
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
    transition: box-shadow 0.25s ease, transform 0.25s ease;
}

.pd-auth-content:hover,
.pd-auth-card:hover {
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    transform: translateY(-2px);
}

.pd-auth-content {
    padding: 28px;
}

.pd-auth-heading {
    margin: 0;
    color: #111827;
    font-size: clamp(28px, 4vw, 38px);
    line-height: 1.15;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.pd-auth-heading-icon {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #facc15;
    flex: 0 0 20px;
}

.pd-auth-heading-icon svg {
    width: 18px;
    height: 18px;
}

.pd-auth-subtext {
    margin: 12px 0 0;
    color: #6b7280;
    font-size: 15px;
    line-height: 1.65;
    max-width: 620px;
}

.pd-auth-visual {
    margin-top: 18px;
    border: 1px solid #ebeff5;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
}

.pd-auth-visual svg {
    width: 100%;
    height: auto;
    max-height: 240px;
    display: block;
}

.pd-auth-features {
    list-style: none;
    margin: 16px 0 0;
    padding: 0;
    display: grid;
    gap: 12px;
}

.pd-auth-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #1f2937;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.35;
}

.pd-auth-check {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #facc15;
    color: #ffffff;
    flex: 0 0 24px;
}

.pd-auth-check svg {
    width: 14px;
    height: 14px;
}

.pd-auth-trust {
    margin: 14px 0 0;
    color: #6b7280;
    font-size: 12px;
}

.pd-auth-form-col {
    display: flex;
}

.pd-auth-card {
    width: 100%;
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.pd-auth-form-title {
    margin: 0 0 18px;
    color: #111827;
    font-size: 28px;
}

.pd-auth-alert {
    margin: 0 0 14px;
    border: 1px solid #fde68a;
    background: #fffbeb;
    color: #111827;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 14px;
}

.pd-auth-form {
    display: grid;
    gap: 14px;
}

.pd-auth-field label {
    display: block;
    margin-bottom: 6px;
    color: #1f2937;
    font-size: 14px;
    font-weight: 600;
}

.pd-auth-input-wrap {
    position: relative;
}

.pd-auth-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pd-auth-icon svg {
    width: 18px;
    height: 18px;
}

.pd-auth-input-wrap input {
    width: 100%;
    height: 50px;
    border-radius: 10px;
    border: 1px solid #d7dee9;
    padding: 0 12px 0 42px;
    font-size: 15px;
    color: #111827;
    background: #fff;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.pd-auth-input-wrap:focus-within .pd-auth-icon {
    color: #facc15;
}

.pd-auth-input-wrap input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.22);
}

.pd-auth-btn {
    width: 100%;
    height: 50px;
    border: none;
    border-radius: 10px;
    background: #facc15;
    color: #111111;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
}

.pd-auth-btn:hover {
    background: #e8b900;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(232, 185, 0, 0.35);
}

.pd-auth-btn:active {
    transform: translateY(0);
}

.pd-auth-bottom-link {
    margin: 16px 0 0;
    color: #4b5563;
    font-size: 14px;
}

.pd-auth-bottom-link a {
    color: #374151;
    text-decoration: none;
}

.pd-auth-bottom-link a:hover {
    color: #111827;
    text-decoration: underline;
    text-decoration-color: #facc15;
}

@media (min-width: 992px) {
    .pd-auth-shell {
        grid-template-columns: minmax(0, 11fr) minmax(0, 9fr);
    }
}

@media (max-width: 991px) {
    .pd-auth-page {
        padding: 30px 12px 40px;
    }

    .pd-auth-shell {
        grid-template-columns: 1fr;
    }

    .pd-auth-content,
    .pd-auth-card {
        padding: 20px;
        border-radius: 14px;
    }

    .pd-auth-visual svg {
        max-height: 190px;
    }
}
</style>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
