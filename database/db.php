<?php

class Database
{
    private $host;
    private $port;
    private $db;
    private $user;
    private $pass;
    private $conn;

    /** Where the credentials actually came from, for diagnostics. */
    private $configSource = '';

    public function __construct()
    {
        // db_config.php is not in version control (see db_config.example.php), so
        // its location depends on the install: the example documents the project
        // root, while shared hosting — where the project folder *is* the docroot —
        // conventionally keeps it one level above the project, outside the
        // webroot. Both are checked. Looking in only one of them made the
        // connection fall silently through to the local development defaults
        // below, which surfaced as "Access denied for user 'root'@'localhost'
        // (using password: NO)" instead of a missing-config error.
        $configFile = $this->resolveConfigFile();

        if ($configFile !== null) {
            $config = require $configFile;

            if (is_array($config)) {
                $this->host = (string) ($config['host'] ?? '');
                $this->port = (string) ($config['port'] ?? '');
                $this->db   = (string) ($config['database'] ?? '');
                $this->user = (string) ($config['username'] ?? '');
                $this->pass = (string) ($config['password'] ?? '');
                $this->configSource = 'db_config.php';
            }
        }

        if ($this->configSource === '' && self::envCredentialsPresent()) {
            // The hosting environment may describe the connection instead of a
            // file, so no credentials ever need to sit next to the code.
            $this->host = (string) (getenv('DB_HOST') ?: 'localhost');
            $this->port = (string) (getenv('DB_PORT') ?: '3306');
            $this->db   = (string) (getenv('DB_NAME') ?: '');
            $this->user = (string) (getenv('DB_USER') ?: '');
            $this->pass = (string) (getenv('DB_PASS') ?: '');
            $this->configSource = 'DB_* environment variables';
        }

        if ($this->configSource === '') {
            // Local development configuration
            $this->host = "localhost";
            $this->port = "3306";
            $this->db   = "payr_bcp";
            $this->user = "root";
            $this->pass = "";
            $this->configSource = 'local development defaults';
        }

        try {
            // Added port mapping and upgraded charset to utf8mb4 (standard for modern MySQL)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Forces PDO to return rows as associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Naming the source turns an opaque credential failure into an
            // obvious "it never read your config" one.
            die("DB Connection failed: " . $e->getMessage()
                . " [credentials from " . $this->configSource . "]");
        }
    }

    /**
     * The db_config.php to use, or null when there is none. The project root wins
     * when both exist, so a stray file cannot shadow a deliberate one.
     */
    private function resolveConfigFile(): ?string
    {
        $projectRoot = dirname(__DIR__);

        foreach ([$projectRoot, dirname($projectRoot)] as $directory) {
            $candidate = $directory . '/db_config.php';
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function envCredentialsPresent(): bool
    {
        foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $key) {
            if (getenv($key) !== false && getenv($key) !== '') {
                return true;
            }
        }

        return false;
    }

    public function getRoles()
    {
        $query = "SELECT role_id, role_name FROM em_roles ORDER BY role_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(); // Defaults to FETCH_ASSOC now
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
