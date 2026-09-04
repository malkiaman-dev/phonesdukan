<?php
class Email {
    public function sendOTP($to, $otp) {
        $config = $this->getConfig();
        $subject = "Your OTP Code";
        $message = "Your OTP code is: $otp. It will expire in 1 minute.";
        $headers = "From: " . $config['from'] . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $smtpHost = $config['host'];
        $smtpPort = (int)$config['port'];
        $smtpUser = $config['user'];
        $smtpPass = $config['pass'];

        // Fallback for local environments when SMTP credentials are not configured.
        if ($smtpUser === '' || $smtpPass === '') {
            return mail($to, $subject, $message, $headers);
        }

        $transport = $config['encryption'] === 'tls' ? 'tcp://' : 'ssl://';
        $smtpConnection = fsockopen($transport . $smtpHost, $smtpPort, $errno, $errstr, 30);

        if (!$smtpConnection) {
            error_log("Error connecting to SMTP server: $errstr ($errno)");
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

    private function getConfig() {
        $isLocal = $this->isLocalEnvironment();

        $defaultFrom = $this->env('MAIL_FROM', 'no-reply@phonesdukan.com');
        $defaultHost = $this->env('MAIL_HOST', 'smtp.hostinger.com');
        $defaultPort = $this->env('MAIL_PORT', '465');
        $defaultEncryption = $this->env('MAIL_ENCRYPTION', 'ssl');

        return [
            'from' => $defaultFrom,
            'host' => $defaultHost,
            'port' => $defaultPort,
            'encryption' => $defaultEncryption,
            'user' => $this->env('MAIL_USERNAME', 'no-reply@yourdomain.com'),
            'pass' => $this->env('MAIL_PASSWORD', '@Azmeryal@123#'),
            'is_local' => $isLocal,
        ];
    }

    private function env($key, $default = null) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        return $default;
    }

    private function isLocalEnvironment() {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

        if ($host === '' && PHP_SAPI === 'cli') {
            return true;
        }

        return $host === 'localhost'
            || strpos($host, '127.0.0.1') !== false
            || strpos($host, '.local') !== false;
    }
}

?>
