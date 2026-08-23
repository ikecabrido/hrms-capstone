<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Notification
{
    private PDO $conn;
    private string $table = 'ep_notifications';

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }
    public function all(): array
    {
        $stmt = $this->conn->query("
            SELECT n.*,
                   COUNT(r.recipient_id) AS recipient_count
            FROM {$this->table} n
            LEFT JOIN ep_notification_recipients r
                ON r.notification_id = n.notification_id
            GROUP BY n.notification_id
            ORDER BY n.created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function create(array $data): string
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
            (title, message, type, priority, created_by_user_id)
            VALUES
            (:title, :message, :type, :priority, :created_by_user_id)
        ");

        $stmt->execute([
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'priority' => $data['priority'],
            'created_by_user_id' => $data['created_by_user_id']
        ]);

        return $this->conn->lastInsertId();
    }
    public function find(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM {$this->table}
            WHERE notification_id = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function update(array $data): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET title = :title,
                message = :message,
                type = :type,
                priority = :priority
            WHERE notification_id = :id
        ");

        return $stmt->execute([
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'priority' => $data['priority'],
            'id' => $data['notification_id']
        ]);
    }
    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("
            DELETE FROM {$this->table}
            WHERE notification_id = :id
        ");

        return $stmt->execute(['id' => $id]);
    }
}