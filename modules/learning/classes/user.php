<?php

include_once __DIR__ . '/../../../database/db.php';

class User
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
     * Get user notifications
     */
    public function getNotifications(int $userId, int $limit = 20): array
    {
        $sql = 'SELECT id, type, title, message, reference_type, reference_id, is_read, created_at FROM ld_notification WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadNotificationCount(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_notification WHERE user_id = :user_id AND is_read = :is_read');
        $stmt->execute([
            ':user_id' => $userId,
            ':is_read' => 0,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Mark a notification as read
     */
    public function markNotificationAsRead(int $notificationId): array
    {
        if ($notificationId <= 0) {
            return ['success' => false, 'message' => 'Notification ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_notification SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $notificationId]);

        return ['success' => true, 'id' => $notificationId, 'message' => 'Notification marked as read.'];
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllNotificationsAsRead(int $userId): array
    {
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_notification SET is_read = 1 WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId]);

        return ['success' => true, 'message' => 'All notifications marked as read.'];
    }

    /**
     * Create a notification for a user
     */
    public function createNotification(array $input): array
    {
        $userId = (int) ($input['user_id'] ?? 0);
        $type = trim((string) ($input['type'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));
        $referenceType = isset($input['reference_type']) ? trim((string) $input['reference_type']) : null;
        $referenceId = isset($input['reference_id']) ? (int) $input['reference_id'] : null;

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if ($type === '') {
            return ['success' => false, 'message' => 'Notification type is required.'];
        }

        if ($title === '') {
            return ['success' => false, 'message' => 'Notification title is required.'];
        }

        if ($message === '') {
            return ['success' => false, 'message' => 'Notification message is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_notification (user_id, type, title, message, reference_type, reference_id) VALUES (:user_id, :type, :title, :message, :reference_type, :reference_id)');
        $stmt->execute([
            ':user_id' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':message' => $message,
            ':reference_type' => $referenceType,
            ':reference_id' => $referenceId,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Notification created successfully.',
        ];
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(int $notificationId): array
    {
        if ($notificationId <= 0) {
            return ['success' => false, 'message' => 'Notification ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_notification WHERE id = :id');
        $stmt->execute([':id' => $notificationId]);

        return ['success' => true, 'id' => $notificationId, 'message' => 'Notification deleted successfully'];
    }

    /**
     * Get messages for a user (inbox)
     */
    public function getMessages(int $userId, int $limit = 20): array
    {
        $sql = 'SELECT id, sender_id, subject, body, is_read, created_at FROM ld_message WHERE recipient_id = :user_id ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadMessageCount(int $userId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM ld_message WHERE recipient_id = :user_id AND is_read = 0');
        $stmt->execute([':user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get a specific message
     */
    public function getMessage(int $messageId): ?array
    {
        $sql = 'SELECT id, sender_id, recipient_id, subject, body, is_read, created_at FROM ld_message WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $messageId]);

        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        return $message ?: null;
    }

    /**
     * Send a message to a user
     */
    public function sendMessage(array $input): array
    {
        $senderId = (int) ($input['sender_id'] ?? 0);
        $recipientId = (int) ($input['recipient_id'] ?? 0);
        $subject = isset($input['subject']) ? trim((string) $input['subject']) : null;
        $body = trim((string) ($input['body'] ?? ''));

        if ($senderId <= 0) {
            return ['success' => false, 'message' => 'Sender ID is required.'];
        }

        if ($recipientId <= 0) {
            return ['success' => false, 'message' => 'Recipient ID is required.'];
        }

        if ($body === '') {
            return ['success' => false, 'message' => 'Message body is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO ld_message (sender_id, recipient_id, subject, body) VALUES (:sender_id, :recipient_id, :subject, :body)');
        $stmt->execute([
            ':sender_id' => $senderId,
            ':recipient_id' => $recipientId,
            ':subject' => $subject,
            ':body' => $body,
        ]);

        return [
            'success' => true,
            'id' => (int) $this->conn->lastInsertId(),
            'message' => 'Message sent successfully.',
        ];
    }

    /**
     * Mark a message as read
     */
    public function markMessageAsRead(int $messageId): array
    {
        if ($messageId <= 0) {
            return ['success' => false, 'message' => 'Message ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_message SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $messageId]);

        return ['success' => true, 'id' => $messageId, 'message' => 'Message marked as read.'];
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId): array
    {
        if ($messageId <= 0) {
            return ['success' => false, 'message' => 'Message ID is required.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM ld_message WHERE id = :id');
        $stmt->execute([':id' => $messageId]);

        return ['success' => true, 'id' => $messageId, 'message' => 'Message deleted successfully'];
    }
}
