<?php

if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

class Database
{
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn;
    private $connectionError = null;

    public function __construct(string $database = '')
    {
        $this->host = 'localhost';
        $this->port = '3306';
        $this->db   = 'bcp';
        $this->user = 'root';
        $this->pass = '';

        $configFile = dirname(__DIR__) . '/db_config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->host = $config['host'] ?? $this->host;
            $this->port = $config['port'] ?? $this->port;
            $this->db   = $config['database'] ?? $this->db;
            $this->user = $config['username'] ?? $this->user;
            $this->pass = $config['password'] ?? $this->pass;
        }

        if (getenv('DB_HOST')) $this->host = getenv('DB_HOST');
        if (getenv('DB_PORT')) $this->port = getenv('DB_PORT');
        if (getenv('DB_NAME')) $this->db   = getenv('DB_NAME');
        if (getenv('DB_USER')) $this->user = getenv('DB_USER');
        if (getenv('DB_PASS')) $this->pass = getenv('DB_PASS');

        $usingDefaults = ($this->user === 'root' && $this->pass === '') || $this->user === 'CHANGE_ME';

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->user, $this->pass);

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->conn->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            if ($usingDefaults) {
                error_log('WARNING: Database connection using default credentials. Update database/.env or db_config.php with production credentials.');
            }
        } catch (PDOException $e) {
            $this->connectionError = $e->getMessage();
            error_log('DB Connection failed: ' . $e->getMessage());

            if (isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH']))) {
                header('Content-Type: application/json');
                $msg = 'Database connection unavailable.';
                if ($usingDefaults) {
                    $msg = 'Database configuration error. Update database/.env or db_config.php with production credentials.';
                }
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
        }
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            throw new RuntimeException('Database connection unavailable. ' . ($this->connectionError ?? ''));
        }
        return $this->conn;
    }

    public function hasConnectionError(): bool
    {
        return $this->connectionError !== null;
    }

    public function getConnectionError(): ?string
    {
        return $this->connectionError;
    }

    public function getRoles()
    {
        if ($this->conn === null) {
            throw new RuntimeException('Database connection unavailable.');
        }
        $query = "SELECT role_id, role_name FROM em_roles ORDER BY role_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}