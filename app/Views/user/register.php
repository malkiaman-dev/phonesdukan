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

<div class="pd-auth-page">
    <div class="pd-auth-shell">
        <section class="pd-auth-content" aria-label="Register guidance">
            <h1 class="pd-auth-heading">
                Create Your Account
                <span class="pd-auth-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
            </h1>
            <p class="pd-auth-subtext">
                Join PhonesDukan to enjoy faster checkout, order tracking, and exclusive deals.
            </p>

            <div class="pd-auth-visual" aria-hidden="true">
                <svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg" role="img">
                    <rect x="35" y="30" width="530" height="240" rx="22" fill="#ffffff" stroke="#e8edf4"/>
                    <circle cx="185" cy="130" r="78" fill="#fff8cc"/>
                    <path d="M185 96a25 25 0 1 1 25 25h-50a25 25 0 1 1 25-25z" fill="#111827"/>
                    <rect x="146" y="154" width="78" height="46" rx="11" fill="#111827"/>
                    <path d="M159 171h52M159 184h38" stroke="#facc15" stroke-width="4" stroke-linecap="round"/>
                    <rect x="294" y="70" width="220" height="160" rx="14" fill="#f8fafc" stroke="#e5e7eb"/>
                    <rect x="320" y="96" width="166" height="12" rx="6" fill="#e5e7eb"/>
                    <rect x="320" y="119" width="140" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="320" y="138" width="154" height="10" rx="5" fill="#e5e7eb"/>
                    <rect x="320" y="162" width="96" height="30" rx="15" fill="#facc15"/>
                </svg>
            </div>

            <ul class="pd-auth-features">
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Faster checkout experience</span>
                </li>
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Track all your orders</span>
                </li>
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Get exclusive offers</span>
                </li>
                <li>
                    <span class="pd-auth-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5l4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>Save your delivery details</span>
                </li>
            </ul>
            <p class="pd-auth-trust">Simple, secure, and designed for smoother shopping.</p>
        </section>

        <section class="pd-auth-form-col">
            <div class="pd-auth-card">
                <h2 class="pd-auth-form-title">Register</h2>

                <?php if (isset($message)) : ?>
                    <p class="pd-auth-alert" role="alert"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>

                <form method="POST" class="pd-auth-form">
                    <div class="pd-auth-field">
                        <label for="register-name">Full Name</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input id="register-name" type="text" name="full_name" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="pd-auth-field">
                        <label for="register-email">Email</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/>
                                    <path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input id="register-email" type="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="pd-auth-field">
                        <label for="register-password">Password</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <input id="register-password" type="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="pd-auth-field">
                        <label for="phone">Phone</label>
                        <div class="pd-auth-input-wrap">
                            <span class="pd-auth-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="7" y="2.5" width="10" height="19" rx="2.5" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="18" r="1" fill="currentColor"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                placeholder="03XXXXXXXXX"
                                required
                                maxlength="11"
                                pattern="^03\d{9}$"
                                oninput="validatePhone()"
                                title="Phone number must start with 03 and have 11 digits"
                            >
                        </div>
                    </div>

                    <button type="submit" class="pd-auth-btn">Register</button>
                </form>

                <p class="pd-auth-bottom-link">
                    Already have an account? <a href="/login" class="login-link">Login</a>
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

<script>
function validatePhone() {
    const phoneInput = document.getElementById('phone');

    // Allow only digits and limit to 11 digits
    phoneInput.value = phoneInput.value.replace(/[^0-9]/g, '').slice(0, 11);

    // Restrict the phone number to start with '03'
    if (phoneInput.value.length >= 2 && !phoneInput.value.startsWith('03')) {
        phoneInput.setCustomValidity('Phone number must start with "03"');
    } else {
        phoneInput.setCustomValidity('');
    }
}
</script>

<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>