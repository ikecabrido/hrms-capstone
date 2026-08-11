<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private string $host;
    private string $db_name = 'hrms-capstone';
    private string $username = 'root';
    private string $password = '';
    private ?PDO $conn = null;

    public function __construct()
    {
        $this->host = $this->getServerHost();
    }

    private function getServerHost(): string
    {
        // If accessing via HTTP host, use that host/IP
        if (!empty($_SERVER['HTTP_HOST'])) {
            $host = explode(':', $_SERVER['HTTP_HOST'])[0];

            // Already an IP address
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                return $host;
            }

            // Try to resolve hostname to IP
            $ip = gethostbyname($host);

            if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Try to get the server's IP address
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        // Try to resolve the server hostname
        $hostname = gethostname();

        if ($hostname) {
            $ip = gethostbyname($hostname);

            if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Final fallback
        return 'localhost';
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
