<?php

include_once __DIR__ . '/../../../database/db.php';

class Setting
{
    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get a system setting by key
     */
    public function get(string $key): ?string
    {
        $sql = 'SELECT setting_value FROM ld_setting WHERE setting_key = :setting_key LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':setting_key' => $key]);

        $value = $stmt->fetchColumn();

        return $value !== false ? $value : null;
    }

    /**
     * Get all system settings
     */
    public function getAll(): array
    {
        $sql = 'SELECT setting_key, setting_value FROM ld_setting ORDER BY setting_key ASC';
        $stmt = $this->conn->query($sql);

        $settings = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Set a system setting
     */
    public function set(string $key, string $value): array
    {
        if ($key === '') {
            return ['success' => false, 'message' => 'Setting key is required.'];
        }

        // Try update first
        $stmt = $this->conn->prepare('UPDATE ld_setting SET setting_value = :setting_value, updated_at = NOW() WHERE setting_key = :setting_key');
        $stmt->execute([
            ':setting_value' => $value,
            ':setting_key' => $key,
        ]);

        // If no rows updated, insert
        if ($stmt->rowCount() === 0) {
            $stmt = $this->conn->prepare('INSERT INTO ld_setting (setting_key, setting_value) VALUES (:setting_key, :setting_value)');
            $stmt->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
            ]);
        }

        return ['success' => true, 'key' => $key, 'message' => 'Setting saved successfully.'];
    }

    /**
     * Get a specific system configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        // Try to parse as number
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }
}
