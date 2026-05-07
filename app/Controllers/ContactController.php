<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
require_once dirname(__DIR__, 2) . '/app/Models/ContactModel.php';

class ContactController {
    public $error = '';
    public $success = '';

    public function handleFormSubmission() {
        // CSRF token check
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
                // Sanitize and validate form data
                $name = htmlspecialchars(trim($_POST['name']));
                $email = htmlspecialchars(trim($_POST['email']));
                $subject = htmlspecialchars(trim($_POST['subject']));
                $message = htmlspecialchars(trim($_POST['message']));

                // Validate fields
                if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                    $this->error = "Please fill in all fields.";
                    return;
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "Invalid email address.";
                    return;
                }

                // Sanitize message (avoid XSS in the message content)
                $message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

                try {
                    // Create database connection
                    $database = new Database();
                    $conn = $database->getConnection();
                    
                    // Save the message using the ContactModel (secure with prepared statements)
                    $contactModel = new ContactModel($conn);
                    $successDb = $contactModel->saveMessage($name, $email, $subject, $message);

                    // If message saved in database, try to send the email
                    if ($successDb) {
                        $to = "info@phonesdukan.com";
                        $emailSubject = "Contact Form: " . htmlspecialchars($subject);
                        $emailBody = "Name: " . htmlspecialchars($name) . "\nEmail: " . htmlspecialchars($email) . "\n\nMessage:\n" . $message;
                        
                        // Updated headers for better email delivery
                        $headers  = "From: Phones Dukan <info@phonesdukan.com>\r\n";
                        $headers .= "Reply-To: " . htmlspecialchars($email) . "\r\n";
                        $headers .= "MIME-Version: 1.0\r\n";
                        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                        // Try to send email notification, but never break user flow if local SMTP is unavailable.
                        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
                        $isLocalHost = $host === 'localhost'
                            || strpos($host, 'localhost:') === 0
                            || strpos($host, '127.0.0.1') !== false
                            || strpos($host, '::1') !== false;

                        $mailSent = false;
                        if (!$isLocalHost && function_exists('mail')) {
                            $mailSent = @mail($to, $emailSubject, $emailBody, $headers);
                        }

                        if (!$mailSent) {
                            error_log("Contact form mail notification failed for {$email} (subject: {$subject}).");
                        }

                        // Set success message and redirect (PRG) with explicit sent flag for reliable display.
                        $_SESSION['success'] = "Your message has been sent successfully.";
                        $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/contact-us/'), PHP_URL_PATH) ?: '/contact-us/';
                        header('Location: ' . $requestPath . '?sent=1');
                        exit();
                    } else {
                        $this->error = "Failed to save message in database.";
                    }
                } catch (PDOException $e) {
                    // Database error
                    $this->error = "Database error: " . $e->getMessage();
                }
            } else {
                // Invalid CSRF token
                $this->error = 'Invalid CSRF token. Please try again.';
            }
        }
    }
}
?>
