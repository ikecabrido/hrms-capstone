<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class NotificationRecipient
{
    private PDO $conn;
    private string $table = 'ep_notification_recipients';

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }
    public function getEmployeeNotifications(int $employee_id): array
    {
        $query = "
        SELECT
            n.notification_id,
            n.title,
            n.message,
            n.type,
            n.priority,
            n.created_at,
            r.is_read,
            r.read_at
        FROM {$this->table} r
        INNER JOIN ep_notifications n
            ON n.notification_id = r.notification_id
        WHERE r.employee_id = :employee_id
        ORDER BY n.created_at DESC
    ";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':employee_id' => $employee_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createRecipients(int $notificationId, array $employeeIds): bool
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table}
                (notification_id, employee_id)
            VALUES
                (:notification_id, :employee_id)
        ");

        foreach (array_unique($employeeIds) as $employeeId) {
            $stmt->execute([
                'notification_id' => $notificationId,
                'employee_id' => $employeeId
            ]);
        }
        return true;
    }
    public function getRecipients(int $notificationId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                e.employee_code,
                e.first_name,
                e.last_name,
                e.department
            FROM {$this->table} r
            INNER JOIN employees e
                ON e.employee_id = r.employee_id
            WHERE r.notification_id = :notification_id
            ORDER BY e.first_name ASC
        ");

        $stmt->execute([
            'notification_id' => $notificationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function markAsRead(int $notificationId, int $employeeId): bool
    {
        $stmt = $this->conn->prepare("
        UPDATE {$this->table}
        SET is_read = 1,
            read_at = NOW()
        WHERE notification_id = :notification_id
          AND employee_id = :employee_id
    ");

        return $stmt->execute([
            ':notification_id' => $notificationId,
            ':employee_id' => $employeeId
        ]);
    }
    public function markAllAsRead(int $employeeId): bool
    {
        $stmt = $this->conn->prepare("
        UPDATE {$this->table}
        SET
            is_read = 1,
            read_at = NOW()
        WHERE employee_id = :employee_id
        AND is_read = 0
    ");

        return $stmt->execute([
            ':employee_id' => $employeeId
        ]);
    }
}