<?php
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../config/mail.php';

class User {
    private $conn;

    // Constructor to initialize DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Generate a salt for OTP hashing
    private function generateSalt() {
        return bin2hex(random_bytes(16));  // Generate a random salt for added security
    }

    public function register($full_name, $email, $password, $phone) {
        // Check if the email already exists and fetch user details
        $query = "SELECT user_id, is_verified FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Generate OTP and expiration
        $otp = rand(100000, 999999);
        $otp_expires_at = date('Y-m-d H:i:s', strtotime('+1 minute'));
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_timestamp'] = time();
    
        // If user already exists
        if ($existingUser) {
            if ($existingUser['is_verified'] == 0) {
                // Update OTP and resend
                $updateQuery = "UPDATE users SET otp_code = :otp_code, otp_expires_at = :otp_expires_at WHERE email = :email";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':otp_code', $otp);
                $updateStmt->bindParam(':otp_expires_at', $otp_expires_at);
                $updateStmt->bindParam(':email', $email);
    
                if ($updateStmt->execute()) {
                    $this->sendOTP($email, $otp);
                    return true;
                } else {
                    return "Failed to update OTP. Please try again.";
                }
            } else {
                return "This email is already registered. Please use a different email.";
            }
        }
    
        // Validate phone number
        if (!$this->isValidPhoneNumber($phone)) {
            return "Invalid phone number. It must start with '03' and be 11 digits long.";
        }
    
        // Register new user
        $salt = $this->generateSalt();
        $query = "INSERT INTO users (full_name, email, password_hash, phone, user_role, salt, otp_code, otp_expires_at, is_verified, created_at)
                  VALUES (:full_name, :email, :password_hash, :phone, 'customer', :salt, :otp_code, :otp_expires_at, 0, NOW())";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $full_name = htmlspecialchars(strip_tags($full_name));
        $email = htmlspecialchars(strip_tags($email));
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $phone = htmlspecialchars(strip_tags($phone));

        // Bind parameters
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':salt', $salt);
        $stmt->bindParam(':otp_code', $otp);
        $stmt->bindParam(':otp_expires_at', $otp_expires_at);
    
        // Execute and send OTP
        if ($stmt->execute()) {
            $this->sendOTP($email, $otp);
            return true;
        }
    
        return "Error registering the user. Please try again.";
    }
    
    
    // Helper function to validate phone number
    private function isValidPhoneNumber($phone) {
        // Check if phone number starts with '03' and is exactly 11 digits long
        return preg_match('/^03\d{9}$/', $phone);
    }
    

    // Send OTP email to the user
    public function sendOTP($email, $otp) {
        $body  = "<tr><td style='padding:36px 40px;'>";
        $body .= "<h2 style='margin:0 0 12px 0;font-size:22px;color:#111111;font-family:Arial,sans-serif;'>Verify Your Email Address</h2>";
        $body .= "<p style='margin:0 0 24px 0;font-size:15px;color:#555555;line-height:1.7;font-family:Arial,sans-serif;'>";
        $body .= "Thanks for signing up with <strong style='color:#111111;'>Phones Dukan</strong>. Enter the code below to verify your account. This code expires in <strong style='color:#111111;'>1 minute</strong>.";
        $body .= "</p>";

        // OTP box
        $body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom:24px;'>";
        $body .= "<tr><td style='background:#f8f8f8;border:2px dashed #f7d117;border-radius:8px;padding:32px 20px;text-align:center;'>";
        $body .= "<p style='margin:0 0 12px 0;font-size:11px;color:#999999;letter-spacing:3px;text-transform:uppercase;font-family:Arial,sans-serif;'>Verification Code</p>";
        $body .= "<div style='font-size:48px;font-weight:700;letter-spacing:16px;color:#111111;font-family:Courier New,Courier,monospace;line-height:1;padding-left:16px;'>$otp</div>";
        $body .= "<p style='margin:14px 0 0 0;font-size:12px;color:#cc0000;font-weight:600;font-family:Arial,sans-serif;'>Expires in 1 minute</p>";
        $body .= "</td></tr></table>";

        // Notice
        $body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>";
        $body .= "<tr><td style='background:#fffbf0;border-left:4px solid #f7d117;padding:14px 18px;border-radius:0 6px 6px 0;'>";
        $body .= "<p style='margin:0;font-size:13px;color:#777777;font-family:Arial,sans-serif;line-height:1.6;'>";
        $body .= "If you did not create a Phones Dukan account, you can safely ignore this email. Never share this code with anyone.";
        $body .= "</p></td></tr></table>";
        $body .= "</td></tr>";

        try {
            $mail = createMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Verify Your Email - Phones Dukan';
            $mail->Body    = emailShell($body);
            $mail->AltBody = "Your Phones Dukan verification code is: {$otp}\n\nThis code expires in 1 minute.\nDo not share this code with anyone.\n\nIf you did not sign up, please ignore this email.";
            $mail->send();
            error_log("OTP email sent successfully to: $email");
            return true;
        } catch (\Exception $e) {
            error_log("OTP email failed for $email: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateAndSendOTP($email, $otp) {
        // Check if the account is locked
        $query = "SELECT is_locked, failed_attempts FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result['is_locked']) {
            // Return an error if the account is locked
            return "Your account is locked. Please contact support.";
        }
    
        // Set expiration time for 1 minute
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 minute'));
        
        // Update OTP and expiration in the database
        $query = "UPDATE users SET otp_code = :otp, otp_expires_at = :expires, last_otp_sent_at = NOW() WHERE email = :email AND is_verified = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':otp', $otp);
        $stmt->bindParam(':expires', $expiresAt);
        $stmt->bindParam(':email', $email);
    
        // Check if the update was successful
        if ($stmt->execute()) {
            // Send the OTP to the user's email
            $this->sendOTP($email, $otp);
            return true;
        }
        
        return false;
    }
    
    

    public function verifyOTP($email, $otp) {
        try {
            // Start a transaction to ensure all updates are executed together
            $this->conn->beginTransaction();
    
            // Retrieve the stored OTP, expiration time, failed attempts, and lock status from the database
            $query = "SELECT otp_code, otp_expires_at, failed_attempts, is_locked FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$result) {
                throw new Exception('No user found for the provided email.');
            }
    
            // Debugging: Show what was fetched from the database
            echo "Fetched Data: " . print_r($result, true) . "<br>";
    
            // If account is locked, return a message
            if ($result['is_locked']) {
                return "Your account is locked due to too many failed OTP attempts.";
            }
    
            // Check if the OTP entered matches the stored OTP
            if ($otp === $result['otp_code']) {
                // Debugging: Show that OTP matched
                echo "OTP matched: " . $otp . " == " . $result['otp_code'] . "<br>";
    
                // OTP is valid, update the user's verification status
                $updateQuery = "UPDATE users SET is_verified = 1, failed_attempts = 0 WHERE email = :email";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':email', $email);
    
                // Debugging: Check if the query is executed successfully
                if (!$updateStmt->execute()) {
                    throw new Exception('Error updating user verification status.');
                } else {
                    echo "User verification status updated successfully.<br>";
                }
    
                // Debugging: Check if is_verified was updated correctly
                $checkQuery = "SELECT is_verified FROM users WHERE email = :email";
                $checkStmt = $this->conn->prepare($checkQuery);
                $checkStmt->bindParam(':email', $email);
                $checkStmt->execute();
                $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                echo "is_verified status after update: " . $checkResult['is_verified'] . "<br>";  // Should be 1
    
                // Now delete OTP and expiration time after successful verification
                $deleteQuery = "UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE email = :email";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->bindParam(':email', $email);
    
                // Debugging: Check if OTP is deleted from the database
                if (!$deleteStmt->execute()) {
                    throw new Exception('Error deleting OTP.');
                } else {
                    echo "OTP and expiration time deleted successfully.<br>";
                }
    
                // Commit the transaction after all operations
                $this->conn->commit();
    
                // Set the session to reflect the verification
                $_SESSION['is_verified'] = 1;  // Store the verified status in session
    
                // Debugging: Check session variable
                echo "Session variable is_verified: " . $_SESSION['is_verified'] . "<br>";
    
                // Redirect to dashboard
                header("Location: /login");
                exit();  // Ensure that script execution stops here
    
                return true;  // OTP successfully verified and deleted
            } else {
                // OTP is incorrect
                $failedAttempts = $result['failed_attempts'] + 1;
    
                // Update failed attempts count
                $updateAttemptsQuery = "UPDATE users SET failed_attempts = :failed_attempts WHERE email = :email";
                $updateAttemptsStmt = $this->conn->prepare($updateAttemptsQuery);
                $updateAttemptsStmt->bindParam(':failed_attempts', $failedAttempts);
                $updateAttemptsStmt->bindParam(':email', $email);
                $updateAttemptsStmt->execute();
    
                // Debugging: Log the number of failed attempts
                echo "Failed attempts before update: $failedAttempts<br>";
    
                return "Incorrect OTP. You have $failedAttempts failed attempts.";
            }
        } catch (Exception $e) {
            // Rollback the transaction if anything fails
            $this->conn->rollBack();
            
            // Log the error message for further debugging
            echo "Error: " . $e->getMessage() . "<br>";
            return false;
        }
    }
    
    public function login($email, $password) {
        // Prepare a query to check if the user exists
        $query = "SELECT user_id, full_name, email, phone, user_role, is_verified, password_hash FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Check if user exists
        if ($result) {
            // Verify the password
            if (password_verify($password, $result['password_hash'])) {
                // Check if the user is verified
                if ($result['is_verified'] == 1) {
                    // Store user session data
                    $_SESSION['user_id'] = $result['user_id'];        // User ID
                    $_SESSION['user_name'] = $result['full_name'];    // Full Name
                    $_SESSION['email'] = $result['email'];            // Email
                    $_SESSION['phone'] = $result['phone'];            // Phone Number
                    $_SESSION['user_role'] = $result['user_role'];    // User Role (e.g. admin, customer)
                    $_SESSION['is_verified'] = $result['is_verified']; // Verification status
    
                    return true;  // Successful login
                } else {
                    return "Account not verified. Please check your email for verification.";
                }
            } else {
                return "Incorrect password.";
            }
        } else {
            return "No user found with that email.";
        }
    }
    

    public function sendPasswordResetEmail($email, $reset_token) {
        $resetLink = 'https://phonesdukan.com/reset-password?token=' . urlencode($reset_token);

        $body  = "<tr><td style='padding:36px 40px;'>";
        $body .= "<h2 style='margin:0 0 12px 0;font-size:22px;color:#111111;font-family:Arial,sans-serif;'>Reset Your Password</h2>";
        $body .= "<p style='margin:0 0 28px 0;font-size:15px;color:#555555;line-height:1.7;font-family:Arial,sans-serif;'>";
        $body .= "We received a request to reset your <strong style='color:#111111;'>Phones Dukan</strong> account password. Click the button below to set a new password. This link expires in <strong style='color:#111111;'>5 minutes</strong>.";
        $body .= "</p>";

        // CTA button
        $body .= "<table role='presentation' cellpadding='0' cellspacing='0' border='0' style='margin:0 0 28px 0;'>";
        $body .= "<tr><td style='background:#f7d117;border-radius:6px;'>";
        $body .= "<a href='$resetLink' style='display:inline-block;padding:14px 36px;font-size:15px;font-weight:700;color:#111111;text-decoration:none;font-family:Arial,sans-serif;letter-spacing:0.5px;'>";
        $body .= "Reset My Password &rarr;</a></td></tr></table>";

        // Fallback link
        $body .= "<p style='margin:0 0 4px 0;font-size:12px;color:#999999;font-family:Arial,sans-serif;'>Button not working? Copy and paste this link into your browser:</p>";
        $body .= "<p style='margin:0 0 28px 0;font-size:12px;color:#888888;font-family:Arial,sans-serif;word-break:break-all;'>$resetLink</p>";

        // Security notice
        $body .= "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'>";
        $body .= "<tr><td style='background:#fffbf0;border-left:4px solid #f7d117;padding:14px 18px;border-radius:0 6px 6px 0;'>";
        $body .= "<p style='margin:0;font-size:13px;color:#777777;font-family:Arial,sans-serif;line-height:1.6;'>";
        $body .= "<strong style='color:#111111;'>Security Notice:</strong> Phones Dukan will never ask for your password via email. If you did not request this reset, please ignore this email.";
        $body .= "</p></td></tr></table>";
        $body .= "</td></tr>";

        try {
            $mail = createMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request - Phones Dukan';
            $mail->Body    = emailShell($body);
            $mail->AltBody = "Reset your Phones Dukan password by visiting:\n\n{$resetLink}\n\nThis link expires in 5 minutes.\n\nIf you did not request this, please ignore this email.";
            $mail->send();
            error_log("Password reset email sent to: $email");
            return true;
        } catch (\Exception $e) {
            error_log("Password reset email failed for $email: " . $e->getMessage());
            return false;
        }
    }

}
?>
