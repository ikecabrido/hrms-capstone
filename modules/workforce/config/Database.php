<?php
/**
 * Database Connection Class
 * Singleton pattern for database connection
 */

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connect();
    }

    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }

            // Set charset to utf8mb4
            if (!$this->connection->set_charset('utf8mb4')) {
                throw new Exception('Error loading character set utf8mb4: ' . $this->connection->error);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }

    /**
     * Get connection object
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute prepared statement
     */
    public function executeQuery($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }

            // Bind parameters if provided
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            // Execute statement
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            throw new Exception('Database Error: ' . $e->getMessage());
        }
    }

    /**
     * Fetch single row
     */
    public function fetchOne($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $stmt->close();
        return $rows;
    }

    /**
     * Count rows
     */
    public function count($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        $result = $stmt->get_result();
        $count = $result->num_rows;
        $stmt->close();
        return $count;
    }

    /**
     * Insert data
     */
    public function insert($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        $insertId = $this->connection->insert_id;
        $stmt->close();
        return $insertId;
    }

    /**
     * Update data
     */
    public function update($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        $affectedRows = $this->connection->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    /**
     * Delete data
     */
    public function delete($query, $params = [], $types = '') {
        return $this->update($query, $params, $types);
    }

    /**
     * Close connection
     */
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {}
}

?>
