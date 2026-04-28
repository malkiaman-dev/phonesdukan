<?php
class Database {
    private $host = "localhost";
    private $db_name = "u903950600_custom_pd";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        } catch (PDOException $exception) {
            die("❌ Database Connection Error: " . $exception->getMessage());
        }
        return $this->conn;
    }
    
}

if (!isset($conn) || !($conn instanceof PDO)) {
    $database = new Database();
    $conn = $database->getConnection();
}
?>
