<?php

include_once __DIR__ . '/../../../database/db.php';

class Message
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

    public function getById(int $id): ?array
    {
        $sql = 'SELECT id, sender_id, recipient_id, subject, body, is_read, created_at FROM ld_message WHERE id = :id LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        return $message ?: null;
    }

    public function getRecipientMessages(int $recipientId, int $limit = 20): array
    {
        $sql = 'SELECT id, sender_id, subject, body, is_read, created_at FROM ld_message WHERE recipient_id = :recipient_id ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':recipient_id', $recipientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSentMessages(int $senderId, int $limit = 20): array
    {
        $sql = 'SELECT id, recipient_id, subject, body, created_at FROM ld_message WHERE sender_id = :sender_id ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':sender_id', $senderId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount(int $recipientId): int
    {
        $sql = 'SELECT COUNT(*) as count FROM ld_message WHERE recipient_id = :recipient_id AND is_read = :is_read';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':recipient_id' => $recipientId,
            ':is_read' => 0,
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0);
    }

    public function create(array $input): array
    {
        $senderId = (int) ($input['sender_id'] ?? 0);
        $recipientId = (int) ($input['recipient_id'] ?? 0);
        $subject = trim((string) ($input['subject'] ?? ''));
        $body = trim((string) ($input['body'] ?? ''));

        if ($senderId <= 0) {
            return ['success' => false, 'message' => 'Sender ID is required.'];
        }

        if ($recipientId <= 0) {
            return ['success' => false, 'message' => 'Recipient ID is required.'];
        }

        if ($subject === '') {
            return ['success' => false, 'message' => 'Message subject is required.'];
        }

        if ($body === '') {
            return ['success' => false, 'message' => 'Message body is required.'];
        }

        $sql = 'INSERT INTO ld_message (sender_id, recipient_id, subject, body, is_read) VALUES (:sender_id, :recipient_id, :subject, :body, :is_read)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':sender_id' => $senderId,
            ':recipient_id' => $recipientId,
            ':subject' => $subject,
            ':body' => $body,
            ':is_read' => 0,
        ]);

        return [
            'success' => true,
            'message' => 'Message sent successfully.',
            'id' => (int) $this->conn->lastInsertId(),
        ];
    }

    public function update(array $input): array
    {
        $messageId = (int) ($input['id'] ?? 0);
        $subject = trim((string) ($input['subject'] ?? ''));
        $body = trim((string) ($input['body'] ?? ''));

        if ($messageId <= 0) {
            return ['success' => false, 'message' => 'Message ID is required.'];
        }

        if ($subject === '') {
            return ['success' => false, 'message' => 'Message subject is required.'];
        }

        if ($body === '') {
            return ['success' => false, 'message' => 'Message body is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_message SET subject = :subject, body = :body WHERE id = :id');
        $stmt->execute([
            ':subject' => $subject,
            ':body' => $body,
            ':id' => $messageId,
        ]);

        return ['success' => true, 'id' => $messageId, 'message' => 'Message updated successfully'];
    }

    public function markAsRead(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Message ID is required.'];
        }

        $stmt = $this->conn->prepare('UPDATE ld_message SET is_read = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'id' => $id, 'message' => 'Message marked as read'];
    }

    public function markMultipleAsRead(array $messageIds): array
    {
        if (empty($messageIds)) {
            return ['success' => false, 'message' => 'No message IDs provided.'];
        }

        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $sql = "UPDATE ld_message SET is_read = 1 WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($messageIds);

        return ['success' => true, 'message' => 'Messages marked as read', 'count' => $stmt->rowCount()];
    }
}
