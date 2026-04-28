<?php
class Email {
    public function sendOTP($to, $otp) {
        $subject = "Your OTP Code";
        $message = "Your OTP code is: $otp. It will expire in 1 minute.";
        $headers = "From: no-reply@phonesdukan.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // SMTP configuration for Hostinger
        $smtpHost = 'smtp.hostinger.com';
        $smtpPort = 465;  // Port for SSL
        $smtpUser = 'no-reply@yourdomain.com';  // Your Hostinger email address
        $smtpPass = '@Azmeryal@123#';  // Your email password (or app-specific password)

        // Create a connection to the SMTP server using SSL
        $smtpConnection = fsockopen('ssl://' . $smtpHost, $smtpPort, $errno, $errstr, 30);

        if (!$smtpConnection) {
            echo "Error connecting to SMTP server: $errstr ($errno)";
            return false;
        }

        // Sending HELO command
        $this->sendCommand($smtpConnection, "HELO " . $smtpHost);
        
        // Authentication
        $this->sendCommand($smtpConnection, "AUTH LOGIN");
        $this->sendCommand($smtpConnection, base64_encode($smtpUser));  // Send encoded username
        $this->sendCommand($smtpConnection, base64_encode($smtpPass));  // Send encoded password
        
        // Send email headers and message
        $this->sendCommand($smtpConnection, "MAIL FROM:<$smtpUser>");
        $this->sendCommand($smtpConnection, "RCPT TO:<$to>");
        $this->sendCommand($smtpConnection, "DATA");
        $this->sendCommand($smtpConnection, "Subject: $subject\r\n" . $headers . "\r\n\r\n$message\r\n.");

        // Close connection
        fclose($smtpConnection);
        return true;
    }

    // Helper function to send commands to SMTP server and read responses
    private function sendCommand($connection, $command) {
        fputs($connection, $command . "\r\n");
        $response = fgets($connection, 512);
        // Optional: You can log the server response here for debugging
        return $response;
    }
}

?>
