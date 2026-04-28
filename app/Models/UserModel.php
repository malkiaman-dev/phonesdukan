<?php
require_once __DIR__ . '/../../database/db.php'; // Ensure correct path to db.php

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
        $query = "INSERT INTO users (full_name, email, password_hash, phone, user_role, otp_code, otp_expires_at, is_verified, created_at) 
                  VALUES (:full_name, :email, :password_hash, :phone, 'user', :otp_code, :otp_expires_at, 0, NOW())";
    
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
        // SMTP settings for Hostinger (or another provider)
        $smtpHost = 'smtp.hostinger.com';
        $smtpPort = 465;  // Port for SSL
        $smtpUser = 'info@phonesdukan.com';  // Your email address
        $smtpPass = '@Azmeryal@123#';  // Your email password (or app-specific password)
    
        // Create a connection to the SMTP server using SSL
        $smtpConnection = fsockopen('ssl://' . $smtpHost, $smtpPort, $errno, $errstr, 30);
        if (!$smtpConnection) {
            // Log the error if the connection fails
            error_log("Error connecting to SMTP server: $errstr ($errno)");
            return false;
        }
        
        // Send HELO command to SMTP server
        $this->sendCommand($smtpConnection, "HELO " . $smtpHost);
        
        // Authentication
        $this->sendCommand($smtpConnection, "AUTH LOGIN");
        $this->sendCommand($smtpConnection, base64_encode($smtpUser));  // Send encoded username
        $this->sendCommand($smtpConnection, base64_encode($smtpPass));  // Send encoded password
        
        // Send email headers and message
        $subject = "Your OTP Code";
        $message = "Your OTP code is: $otp. It will expire in 1 minute.";
        $headers = "From: no-reply@phonesdukan.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";  // Ensure proper character encoding
        $headers .= "Reply-To: no-reply@phonesdukan.com\r\n";  // Set the reply-to header
    
        // Send MAIL FROM command
        $this->sendCommand($smtpConnection, "MAIL FROM:<$smtpUser>");
        // Send RCPT TO command (Recipient email)
        $this->sendCommand($smtpConnection, "RCPT TO:<$email>");
        // Send DATA command to start the email content
        $this->sendCommand($smtpConnection, "DATA");
        
        // Send the email headers and message body
        $emailContent = "Subject: $subject\r\n" . $headers . "\r\n\r\n$message\r\n.";
        $this->sendCommand($smtpConnection, $emailContent);
    
        // Close connection
        fclose($smtpConnection);
    
        // Log success message
        error_log("OTP email sent successfully to: $email");
    
        return true;  // Indicate success
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
    
    

    // Helper function to send commands to SMTP server and read responses
    private function sendCommand($connection, $command) {
        // Write the command to the SMTP server
        fputs($connection, $command . "\r\n");
        // Read the response from the server
        $response = fgets($connection, 512);
        // Log the server's response for debugging purposes
        error_log("SMTP Response: $response");
        return $response;
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
    

        // Send Password Reset Email to the user
public function sendPasswordResetEmail($email, $reset_token) {
    // SMTP settings for Hostinger (or another provider)
    $smtpHost = 'smtp.hostinger.com';
    $smtpPort = 465;  // Port for SSL
    $smtpUser = 'info@phonesdukan.com';  // Your email address
    $smtpPass = '@Azmeryal@123#';  // Your email password (or app-specific password)

    // Create a connection to the SMTP server using SSL
    $smtpConnection = fsockopen('ssl://' . $smtpHost, $smtpPort, $errno, $errstr, 30);
    if (!$smtpConnection) {
        // Log the error if the connection fails
        error_log("Error connecting to SMTP server: $errstr ($errno)");
        return false;
    }

    // Send HELO command to SMTP server
    $this->sendCommand($smtpConnection, "HELO " . $smtpHost);

    // Authentication
    $this->sendCommand($smtpConnection, "AUTH LOGIN");
    $this->sendCommand($smtpConnection, base64_encode($smtpUser));  // Send encoded username
    $this->sendCommand($smtpConnection, base64_encode($smtpPass));  // Send encoded password

    // Send email headers and message
    $subject = "Password Reset Request";
    $message = "Click the link below to reset your password. The link will expire in 5 minutes.\n\n";
    $message .= "Reset Link: /reset-password?token=$reset_token";
    $headers = "From: no-reply@phonesdukan.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";  // Ensure proper character encoding
    $headers .= "Reply-To: no-reply@phonesdukan.com\r\n";  // Set the reply-to header

    // Send MAIL FROM command
    $this->sendCommand($smtpConnection, "MAIL FROM:<$smtpUser>");
    // Send RCPT TO command (Recipient email)
    $this->sendCommand($smtpConnection, "RCPT TO:<$email>");
    // Send DATA command to start the email content
    $this->sendCommand($smtpConnection, "DATA");

    // Send the email headers and message body
    $emailContent = "Subject: $subject\r\n" . $headers . "\r\n\r\n$message\r\n.";
    $this->sendCommand($smtpConnection, $emailContent);

    // Close connection
    fclose($smtpConnection);

    // Log success message
    error_log("Password reset email sent successfully to: $email");

    return true;  // Indicate success
}

}
?>
