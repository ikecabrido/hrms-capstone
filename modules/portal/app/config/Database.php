<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private ?PDO $conn = null;

    public function __construct()
    {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';

        if (
            $serverName === 'localhost' ||
            $serverName === '127.0.0.1'
        ) {
            // Local XAMPP
            $this->host = '127.0.0.1';
            $this->db_name = 'hrms-capstone';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production Server
            $this->host = 'localhost';
            $this->db_name = 'port_hrmscapstone';
            $this->username = 'port_root';
            $this->password = 'bUWdO1%TPyT1jV7o';
        }
    }

    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $this->host,
                $this->db_name
            );

            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $this->conn->exec("SET SESSION time_zone = '+08:00'");

            return $this->conn;

        } catch (PDOException $exception) {
            throw new PDOException(
                'Database connection error: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}