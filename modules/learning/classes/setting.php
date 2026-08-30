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
     * Get user display preferences
     */
    public function getDisplayPreferences(int $userId): ?array
    {
        $sql = 'SELECT id, page_size, view_mode, theme FROM ld_display_preference WHERE user_id = :user_id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

        return $prefs ?: null;
    }

    /**
     * Set user display preferences
     */
    public function setDisplayPreferences(int $userId, array $input): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        $pageSize = (int) ($input['page_size'] ?? 10);
        $viewMode = trim((string) ($input['view_mode'] ?? 'grid'));
        $theme = trim((string) ($input['theme'] ?? 'light'));

        if (!in_array($pageSize, [12, 24, 36], true)) {
            $pageSize = 12;
        }

        if (!in_array($viewMode, ['grid', 'list'], true)) {
            $viewMode = 'grid';
        }

        if (!in_array($theme, ['light', 'dark'], true)) {
            $theme = 'light';
        }

        // Check if preferences exist
        $existing = $this->getDisplayPreferences($userId);

        if ($existing) {
            $stmt = $this->conn->prepare('UPDATE ld_display_preference SET page_size = :page_size, view_mode = :view_mode, theme = :theme WHERE user_id = :user_id');
            $stmt->execute([
                ':page_size' => $pageSize,
                ':view_mode' => $viewMode,
                ':theme' => $theme,
                ':user_id' => $userId,
            ]);
        } else {
            $stmt = $this->conn->prepare('INSERT INTO ld_display_preference (user_id, page_size, view_mode, theme) VALUES (:user_id, :page_size, :view_mode, :theme)');
            $stmt->execute([
                ':user_id' => $userId,
                ':page_size' => $pageSize,
                ':view_mode' => $viewMode,
                ':theme' => $theme,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Display preferences updated successfully.',
            'preferences' => [
                'page_size' => $pageSize,
                'view_mode' => $viewMode,
                'theme' => $theme,
            ],
        ];
    }

    /**
     * Get notification preferences for a user
     */
    public function getNotificationPreferences(int $userId): array
    {
        $sql = 'SELECT notification_type, enabled FROM ld_notification_preference WHERE user_id = :user_id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $prefs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $prefs[$row['notification_type']] = (bool) $row['enabled'];
        }

        return $prefs;
    }

    /**
     * Set notification preference for a user
     */
    public function setNotificationPreference(int $userId, string $notificationType, bool $enabled): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if ($notificationType === '') {
            return ['success' => false, 'message' => 'Notification type is required.'];
        }

        // Check if preference exists
        $stmt = $this->conn->prepare('SELECT 1 FROM ld_notification_preference WHERE user_id = :user_id AND notification_type = :notification_type LIMIT 1');
        $stmt->execute([
            ':user_id' => $userId,
            ':notification_type' => $notificationType,
        ]);
        $exists = (bool) $stmt->fetchColumn();

        if ($exists) {
            $stmt = $this->conn->prepare('UPDATE ld_notification_preference SET enabled = :enabled WHERE user_id = :user_id AND notification_type = :notification_type');
            $stmt->execute([
                ':enabled' => $enabled ? 1 : 0,
                ':user_id' => $userId,
                ':notification_type' => $notificationType,
            ]);
        } else {
            $stmt = $this->conn->prepare('INSERT INTO ld_notification_preference (user_id, notification_type, enabled) VALUES (:user_id, :notification_type, :enabled)');
            $stmt->execute([
                ':user_id' => $userId,
                ':notification_type' => $notificationType,
                ':enabled' => $enabled ? 1 : 0,
            ]);
        }

        return ['success' => true, 'message' => 'Notification preference updated successfully.'];
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
