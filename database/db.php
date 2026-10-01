<?php

class Database
{
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn = null;
    private $connectionError = null;

    public function __construct()
    {
        $configFile = __DIR__ . '/db_config.php';

        if (file_exists($configFile)) {
            $config = require $configFile;

            $this->host = $config['host'] ?? 'localhost';
            $this->port = $config['port'] ?? '3306';
            $this->db   = $config['database'] ?? '';
            $this->user = $config['username'] ?? '';
            $this->pass = $config['password'] ?? '';
        } else {
            $this->host = 'localhost';
            $this->port = '3306';
            $this->db   = 'payr_bcp';
            $this->user = 'root';
            $this->pass = '';
        }

        try {
            $dsn = "mysql:host={$this->host};"
                 . "port={$this->port};"
                 . "dbname={$this->db};"
                 . "charset=utf8mb4";

            $this->conn = new PDO(
                $dsn,
                $this->user,
                $this->pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $e) {
            $this->connectionError = $e->getMessage();
            $this->conn = null;
        }
    }

    public function hasConnectionError()
    {
        return $this->connectionError !== null;
    }

    public function getConnectionError()
    {
        return $this->connectionError;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getRoles()
    {
        if ($this->conn === null) {
            return [];
        }

        $query = "SELECT role_id, role_name
                  FROM em_roles
                  ORDER BY role_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
