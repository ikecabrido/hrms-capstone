<?php

class TimeDatabase
{
    private static $instance = null;
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn;

    public function __construct()
    {
        $configFile = null;
        $candidates = [
            dirname(__DIR__, 5) . '/db_config.php',
            dirname(__DIR__, 4) . '/db_config.php',
            (isset($_SERVER['DOCUMENT_ROOT']) ? dirname((string) $_SERVER['DOCUMENT_ROOT']) : '') . '/db_config.php',
            (isset($_SERVER['DOCUMENT_ROOT']) ? (string) $_SERVER['DOCUMENT_ROOT'] : '') . '/db_config.php'
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && file_exists($candidate)) {
                $configFile = $candidate;
                break;
            }
        }

        if ($configFile !== null) {
            $config = require $configFile;
            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->db   = $config['database'];
            $this->user = $config['username'];
            $this->pass = $config['password'];
        } else {
            $serverName = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
            $isLocalHost = in_array(strtolower($serverName), ['localhost', '127.0.0.1'], true) || PHP_SAPI === 'cli';

            if (!$isLocalHost) {
                throw new RuntimeException('Database configuration not found. Place db_config.php in the project root or document root before accessing the Time and Attendance module.');
            }

            $this->host = 'localhost';
            $this->port = '3306';
            $this->db   = 'payr_bcp';
            $this->user = 'root';
            $this->pass = '';
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('TimeDatabase PDOException: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Check the configured DB credentials and server availability.');
        }
    }

    public function getRoles()
    {
        $query = "SELECT role_id, role_name FROM em_roles ORDER BY role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}