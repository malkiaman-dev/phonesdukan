<?php
ob_start();
require_once dirname(__DIR__, 3) . '/includes/header.php';

// If the user is already logged in, redirect them to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: /my-account");
    exit();
}

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once __DIR__ . '/../../Controllers/UserController.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Create an instance of the UserController and pass the database connection
    $controller = new UserController($db);

    // Call the login method in the UserController
    $loginResult = $controller->login($email, $password);

    // If the login was successful
    if ($loginResult === true) {
        // Redirect to the dashboard
        header("Location: /my-account");
        exit();
    } else {
        // If login fails, set the message with the error returned
        $message = $loginResult;
    }
}
?>

<div class="pd-auth-page">
    <div class="pd-auth-shell">
        <section class="pd-auth-content" aria-label="Login guidance">
            <h1 class="pd-auth-heading">Welcome Back 👋</h1>
            <p class="pd-auth-subtext">
                Login to access your orders, track deliveries, and manage your account easily.
            </p>

            <div class="pd-auth-visual" aria-hidden="true">
                <svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg" role="img">
                    <rect x="30" y="28" width="540" height="244" rx="22" fill="#ffffff" stroke="#e8edf4"/>
                    <rect x="70" y="65" width="215" height="168" rx="14" fill="#f8fafc" stroke="#e5e7eb"/>
                    <rect x="102" y="93" width="152" height="14" rx="7" fill="#e5e7eb"/>
                    <rect x="102" y="121" width="120" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="102" y="141" width="140" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="102" y="166" width="92" height="28" rx="14" fill="#facc15"/>
                    <circle cx="405" cy="132" r="74" fill="#fff8cc"/>
                    <path d="M405 104a20 20 0 1 1 20 20h-40a20 20 0 1 1 20-20z" fill="#111827"/>
                    <rect x="374" y="151" width="62" height="53" rx="10" fill="#111827"/>
                    <path d="M385 168h40M385 181h29" stroke="#facc15" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>

            <ul class="pd-auth-features">
                <li><span class="pd-auth-check">✓</span><span>Track your orders in real-time</span></li>
                <li><span class="pd-auth-check">✓</span><span>Manage your account details</span></li>
                <li><span class="pd-auth-check">✓</span><span>Quick checkout experience</span></li>
                <li><span class="pd-auth-check">✓</span><span>Secure and reliable access</span></li>
            </ul>
            <p class="pd-auth-trust">Trusted shopping support for customers across Pakistan.</p>
        </section>

        <section class="pd-auth-form-col">
            <div class="pd-auth-card">
                <h2 class="pd-auth-form-title">Login</h2>

                <?php if (isset($message)) : ?>
                    <p class="pd-auth-alert" role="alert"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>

                <form action="" method="POST" class="pd-auth-form">
                    <div class="pd-auth-field">
                        <label for="login-email">Email</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">✉</span>
                            <input id="login-email" type="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="pd-auth-field">
                        <label for="login-password">Password</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">🔒</span>
                            <input id="login-password" type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="pd-auth-links-row">
                        <a href="/forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="pd-auth-btn">Login</button>
                </form>

                <p class="pd-auth-bottom-link">
                    Don't have an account? <a href="/register">Register</a>
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
}

.pd-auth-subtext {
    margin: 12px 0 0;
    color: #4b5563;
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
    gap: 8px;
}

.pd-auth-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #111111;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.35;
}

.pd-auth-check {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #facc15;
    color: #111111;
    flex: 0 0 20px;
    font-size: 13px;
    font-weight: 700;
}

.pd-auth-trust {
    margin: 14px 0 0;
    color: #6b7280;
    font-size: 13px;
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
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #b91c1c;
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
    font-size: 14px;
}

.pd-auth-input-wrap input {
    width: 100%;
    height: 50px;
    border-radius: 10px;
    border: 1px solid #d7dee9;
    padding: 0 12px 0 36px;
    font-size: 15px;
    color: #111827;
    background: #fff;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.pd-auth-input-wrap input:focus {
    outline: none;
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.22);
}

.pd-auth-links-row {
    display: flex;
    justify-content: flex-start;
    margin-top: -4px;
}

.pd-auth-links-row a,
.pd-auth-bottom-link a {
    color: #374151;
    text-decoration: none;
    transition: color 0.2s ease, text-decoration-color 0.2s ease;
}

.pd-auth-links-row a:hover,
.pd-auth-bottom-link a:hover {
    color: #111827;
    text-decoration: underline;
    text-decoration-color: #facc15;
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