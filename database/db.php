<?php
class Database {
    private static ?PDO $sharedConn = null;
    private static bool $schemaChecked = false;

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $isLocal = $this->isLocalEnvironment();

        // 127.0.0.1 is more reliable than "localhost" on Windows/XAMPP.
        $defaultHost = $isLocal ? '127.0.0.1' : 'localhost';
        $defaultDbName = $isLocal ? 'u903950600_custom_pd' : 'u972962277_custom_pd';
        $defaultUsername = $isLocal ? 'root' : 'u972962277_custom_pd';
        $defaultPassword = $isLocal ? '' : 'Phones&Dukan12!';

        $this->host = $this->env('DB_HOST', $defaultHost);
        $this->db_name = $this->env('DB_NAME', $defaultDbName);
        $this->username = $this->env('DB_USER', $defaultUsername);
        $this->password = $this->env('DB_PASS', $defaultPassword);
    }

    public function getConnection() {
        if (self::$sharedConn instanceof PDO) {
            $this->conn = self::$sharedConn;
            return $this->conn;
        }

        $this->conn = null;
        $dbCandidates = [$this->db_name];

        if ($this->isLocalEnvironment() && $this->db_name !== 'u903950600_custom_pd') {
            $dbCandidates[] = 'u903950600_custom_pd';
        }

        $dbCandidates = array_values(array_unique(array_filter($dbCandidates)));
        $lastException = null;

        foreach ($dbCandidates as $dbName) {
            try {
                $this->conn = $this->openPdo($dbName);
                $this->db_name = $dbName;
                self::$sharedConn = $this->conn;

                if (!self::$schemaChecked) {
                    self::$schemaChecked = true;
                    $migrationsFile = dirname(__DIR__) . '/includes/database_migrations.php';
                    if (is_file($migrationsFile)) {
                        require_once $migrationsFile;
                        if (function_exists('ensureDatabaseSchema')) {
                            ensureDatabaseSchema($this->conn);
                        }
                    }
                }

                return $this->conn;
            } catch (PDOException $exception) {
                $lastException = $exception;
                if (strpos($exception->getMessage(), '[1049]') === false) {
                    break;
                }
            }
        }

        $message = $lastException !== null
            ? $lastException->getMessage()
            : 'Unable to connect to any configured database.';

        if (stripos($message, '2006') !== false || stripos($message, '2002') !== false) {
            $message .= ' Start MySQL in the XAMPP Control Panel, wait until it shows Running, then refresh.';
        }

        die('❌ Database Connection Error: ' . $message);
    }

    private function openPdo(string $dbName): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ];

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $dbName . ';charset=utf8mb4';

        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $first) {
            $msg = $first->getMessage();
            $retryable = stripos($msg, '2006') !== false || stripos($msg, '2002') !== false;
            if (!$retryable) {
                throw $first;
            }
            usleep(500000);
            return new PDO($dsn, $this->username, $this->password, $options);
        }
    }

    private function env($key, $default = null) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;l
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
