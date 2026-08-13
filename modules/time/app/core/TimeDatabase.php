<?php

class TimeDatabase
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_DATABASE') ?: 'hrms';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASSWORD') !== false
            ? getenv('DB_PASSWORD')
            : '';

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            $this->conn = new PDO($dsn, $user, $pass);

            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->conn->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $e) {
            die("Time & Attendance DB Connection failed: " . $e->getMessage());
        }
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