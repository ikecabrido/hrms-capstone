<?php

class Database
{
    // Railway will provide these, otherwise they fall back to your local setup
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn;

    public function __construct()
    {
        $configFile = dirname(__DIR__, 2) . '/db_config.php';

        if (file_exists($configFile)) {
            $config = require $configFile;

            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->db   = $config['database'];
            $this->user = $config['username'];
            $this->pass = $config['password'];
        } else {
            // Local development configuration
            $this->host = "localhost";
            $this->port = "3306";
            $this->db   = "hrms";
            $this->user = "root";
            $this->pass = "";
        }

        try {
            // Added port mapping and upgraded charset to utf8mb4 (standard for modern MySQL)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Forces PDO to return rows as associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    public function getRoles()
    {
        $query = "SELECT role_id, role_name FROM em_roles ORDER BY role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(); // Defaults to FETCH_ASSOC now because of line 23
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
