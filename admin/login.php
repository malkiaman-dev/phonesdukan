<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Create a database instance and get the connection
$database = new Database();
$conn = $database->getConnection(); // Fixes the "Undefined variable $conn" error

// Check if the user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: " . url('admin/dashboard.php'));
    exit();
}

$loginBackground = url('public/uploads/login.avif');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($conn) { // Ensure connection is established
        $stmt = $conn->prepare("SELECT id, name, password, role FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role']; // Add this
        
            header("Location: " . url('admin/dashboard.php'));
            exit();
        }
         else {
            $error = "Invalid credentials!";
        }
    } else {
        die("Database connection failed.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Phones Dukan</title>
    <style>
        :root {
            --black: #111111;
            --yellow: #facc15;
            --yellow-hover: #eab308;
            --white: #ffffff;
            --bg-light-gray: #f5f5f5;
            --border: #e5e7eb;
            --text-muted: #6b7280;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #991b1b;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            font-family: "Inter", "Segoe UI", Roboto, -apple-system, BlinkMacSystemFont, "Helvetica Neue", Arial, sans-serif;
            background: var(--black);
            color: var(--black);
            overflow-x: hidden;
        }

        body {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 14% 18%, rgba(250, 204, 21, 0.20), transparent 34%),
                radial-gradient(circle at 86% 78%, rgba(250, 204, 21, 0.14), transparent 36%),
                linear-gradient(145deg, #0b0b0c 0%, #151519 46%, #0f0f11 100%);
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 36px 36px;
            opacity: 0.25;
            pointer-events: none;
        }

        .auth-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.94);
            box-shadow:
                0 28px 70px rgba(0, 0, 0, 0.35),
                0 1px 0 rgba(255, 255, 255, 0.7) inset;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 34px;
        }

        .brand-area {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .brand-logo {
            width: min(240px, 76%);
            height: auto;
            display: block;
            object-fit: contain;
        }

        h1 {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: var(--black);
            margin: 0 0 6px;
            letter-spacing: -0.03em;
        }

        .subtitle {
            margin: 0 0 28px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.96rem;
            line-height: 1.5;
        }

        .alert {
            margin-bottom: 18px;
            border-radius: 14px;
            border: 1px solid var(--danger-border);
            background: var(--danger-bg);
            color: var(--danger-text);
            font-size: 0.92rem;
            font-weight: 500;
            padding: 12px 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: inline-block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9ca3af;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-control {
            width: 100%;
            height: 54px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: var(--bg-light-gray);
            color: var(--black);
            font-size: 0.98rem;
            padding: 0 16px 0 46px;
            transition: border-color 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
            appearance: none;
            -webkit-appearance: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--yellow);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.22);
        }

        .form-control:focus + .password-toggle,
        .form-control:focus ~ .input-icon,
        .input-wrap:focus-within .input-icon {
            color: #4b5563;
        }

        .password-wrap .form-control {
            padding-right: 52px;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--black);
            background: rgba(17, 17, 17, 0.06);
        }

        .password-toggle:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.28);
        }

        .btn {
            border: 0;
            width: 100%;
            height: 54px;
            margin-top: 6px;
            border-radius: 14px;
            background: var(--black);
            color: var(--white);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: color 0.24s ease, transform 0.2s ease, box-shadow 0.22s ease;
            box-shadow: 0 12px 26px rgba(17, 17, 17, 0.28);
        }

        .btn:hover {
            color: var(--yellow);
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(17, 17, 17, 0.34);
        }

        .btn:active {
            transform: translateY(1px) scale(0.995);
            box-shadow: 0 10px 20px rgba(17, 17, 17, 0.24);
        }

        .btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.3), 0 10px 24px rgba(17, 17, 17, 0.22);
        }

        .btn[disabled] {
            opacity: 0.86;
            cursor: not-allowed;
            transform: none;
        }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.42);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn.is-loading .spinner {
            display: inline-block;
        }

        .btn.is-loading .btn-text {
            opacity: 0.95;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Autofill fixes */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--black);
            -webkit-box-shadow: 0 0 0 1000px var(--bg-light-gray) inset;
            transition: background-color 5000s ease-in-out 0s;
            border: 1px solid var(--border);
        }

        /* Modern scrollbar */
        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        *::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 999px;
            border: 2px solid #f3f4f6;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        @media (max-width: 576px) {
            .login-container {
                width: 92%;
                padding: 24px 20px;
                border-radius: 20px;
            }

            h1 {
                font-size: 1.7rem;
            }

            .subtitle {
                margin-bottom: 22px;
            }
        }
    </style>
</head>

<body>

    <main class="auth-shell">
        <div class="login-container">

            <div class="brand-area">
                <img class="brand-logo" src="/public/assets/images/phonesdukan_logo.png" alt="Phones Dukan Logo">
            </div>

            <h1>Admin Login</h1>
            <p class="subtitle">Sign in to manage your dashboard</p>

            <?php if (isset($error)) { ?>
                <div class="alert" role="alert" aria-live="polite">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php } ?>

            <form method="POST" id="adminLoginForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M4 6.75C4 5.7835 4.7835 5 5.75 5H18.25C19.2165 5 20 5.7835 20 6.75V17.25C20 18.2165 19.2165 19 18.25 19H5.75C4.7835 19 4 18.2165 4 17.25V6.75Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M5 7L12 12.25L19 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="email" type="email" name="email" class="form-control" placeholder="Enter your email" autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap password-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M7.5 10.5V8.25C7.5 5.76472 9.51472 3.75 12 3.75C14.4853 3.75 16.5 5.76472 16.5 8.25V10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <rect x="4.75" y="10.5" width="14.5" height="9.75" rx="2.25" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M12 14.25V16.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password" aria-controls="password">
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M2.25 12C3.82422 8.32172 7.49086 5.8125 12 5.8125C16.5091 5.8125 20.1758 8.32172 21.75 12C20.1758 15.6783 16.5091 18.1875 12 18.1875C7.49086 18.1875 3.82422 15.6783 2.25 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn" id="loginBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text">Login</span>
                </button>
            </form>
        </div>
    </main>

    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const form = document.getElementById('adminLoginForm');
            const submitButton = document.getElementById('loginBtn');
            const submitText = submitButton ? submitButton.querySelector('.btn-text') : null;

            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function () {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    passwordToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                });
            }

            if (form && submitButton && submitText) {
                form.addEventListener('submit', function () {
                    submitButton.disabled = true;
                    submitButton.classList.add('is-loading');
                    submitText.textContent = 'Signing in...';
                });
            }
        })();
    </script>

</body>

</html>
