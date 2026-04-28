<?php
// Set session lifetime (before session_start)
$session_lifetime = 21600; // 6 hours

// Only set session configurations if session is NOT started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', $session_lifetime);
    session_set_cookie_params([
        'lifetime' => $session_lifetime,  // Cookie lifetime
        'path' => '/',                    // Make sure the cookie is available for the whole domain
        'domain' => '',                   // You can specify your domain, e.g., '.example.com' if needed
        'secure' => isset($_SERVER['HTTPS']), // Set to true if you're using HTTPS
        'httponly' => true,               // Prevent JavaScript access to the session cookie
        'samesite' => 'Strict'            // Ensure the cookie is only sent for same-site requests
    ]);
    session_start(); // Now start the session
}

// Ensure essential session variables exist
$_SESSION['cart'] = $_SESSION['cart'] ?? []; // Initialize empty cart if not set
$_SESSION['session_id'] = $_SESSION['session_id'] ?? session_id(); // Track session ID

// User authentication tracking
$_SESSION['user_id'] = $_SESSION['user_id'] ?? null;
$_SESSION['user_name'] = $_SESSION['user_name'] ?? "Guest";
$_SESSION['user_email'] = $_SESSION['user_email'] ?? "Not available"; // Ensure email is set for tracking
$_SESSION['user_phone'] = $_SESSION['user_phone'] ?? "Not available";

// Ensure the session email is set properly after login or registration
$_SESSION['email'] = $_SESSION['email'] ?? "Not available"; // Adding email session tracking
// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Secure CSRF token
}
?>
