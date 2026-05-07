<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $isLocal = $this->isLocalEnvironment();

        $defaultHost = 'localhost';
        $defaultDbName = $isLocal ? 'phonesdukan' : 'u972962277_custom_pd';
        $defaultUsername = $isLocal ? 'root' : 'u972962277_custom_pd';
        $defaultPassword = $isLocal ? '' : 'Phones&Dukan12!';

        $this->host = $this->env('DB_HOST', $defaultHost);
        $this->db_name = $this->env('DB_NAME', $defaultDbName);
        $this->username = $this->env('DB_USER', $defaultUsername);
        $this->password = $this->env('DB_PASS', $defaultPassword);
    }

    public function getConnection() {
        $this->conn = null;
        $dbCandidates = [$this->db_name];

        if ($this->isLocalEnvironment()) {
            $dbCandidates[] = 'phonesdukan';
            $dbCandidates[] = 'u972962277_custom_pd';
            $dbCandidates[] = 'u903950600_custom_pd';
        }

        $dbCandidates = array_values(array_unique(array_filter($dbCandidates)));
        $lastException = null;

        foreach ($dbCandidates as $dbName) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $dbName . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->db_name = $dbName;
                return $this->conn;
            } catch (PDOException $exception) {
                $lastException = $exception;
                if (strpos($exception->getMessage(), '[1049]') === false) {
                    break;
                }
            }
        }

        if ($lastException !== null) {
            die("❌ Database Connection Error: " . $lastException->getMessage());
        }

        die("❌ Database Connection Error: Unable to connect to any configured database.");
    }

    private function env($key, $default = null) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
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

if (!isset($conn) || !($conn instanceof PDO)) {
    $database = new Database();
    $conn = $database->getConnection();
}
?>
