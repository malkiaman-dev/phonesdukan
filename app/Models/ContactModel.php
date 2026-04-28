<?php

class ContactModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function saveMessage($name, $email, $subject, $message) {
        try {
            // Use prepared statements to prevent SQL injection
            $query = "INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);

            // Execute the query
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
