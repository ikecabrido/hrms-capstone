
<?php

class Database
{
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn;

    public function __construct()
    {
        /*
         * Production:
         * Use environment variables when available.
         *
         * Local development:
         * Fall back to the existing db_config.php.
         */
        $configFile = dirname(__DIR__, 2) . '/db_config.php';

        if (getenv('DB_HOST') !== false && getenv('DB_HOST') !== '') {
            // Production / environment configuration
            $this->host = getenv('DB_HOST');
            $this->port = getenv('DB_PORT') ?: '3306';
            $this->db   = getenv('DB_DATABASE') ?: 'hrms';
            $this->user = getenv('DB_USER') ?: 'root';
            $this->pass = getenv('DB_PASSWORD') !== false
                ? getenv('DB_PASSWORD')
                : '';
        } elseif (file_exists($configFile)) {
            // Existing HRMS local configuration
            $config = require $configFile;

            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->db   = $config['database'];
            $this->user = $config['username'];
            $this->pass = $config['password'];
        } else {
            // Final local fallback
            $this->host = 'localhost';
            $this->port = '3306';
            $this->db   = 'hrms';
            $this->user = 'root';
            $this->pass = '';
        }

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
        } catch (PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    public function getRoles()
    {
        $query = "SELECT role_id, role_name FROM em_roles ORDER BY role_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
```
