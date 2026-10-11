<?php
/**
 * Notification Model
 * Handles notification creation and tracking
 */

require_once __DIR__ . '/../core/TimeDatabase.php';

class Notification
{
    private $conn;
    private $table = 'notifications';

    public function __construct()
    {
        $database = TimeDatabase::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Create a new notification
     */
    public function create($data)
    {
        try {
            $query = "INSERT INTO `notifications` 
                      (user_id, employee_id, notification_type, title, message, related_id, related_type, 
                       send_via_email, send_via_sms, created_at)
                      VALUES 
                      (:user_id, :employee_id, :notification_type, :title, :message, :related_id, :related_type,
                       :send_via_email, :send_via_sms, NOW())";

            $stmt = $this->conn->prepare($query);
            
            $user_id = $data['user_id'] ?? null;
            $employee_id = $data['employee_id'] ?? null;
            $notification_type = $data['notification_type'] ?? $data['type'] ?? 'SYSTEM';
            $title = $data['title'] ?? '';
            $message = $data['message'] ?? '';
            $related_id = $data['related_id'] ?? null;
            $related_type = $data['related_type'] ?? null;
            $send_via_email = $data['send_via_email'] ?? 1;
            $send_via_sms = $data['send_via_sms'] ?? 1;

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
            $stmt->bindParam(':notification_type', $notification_type);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':related_id', $related_id, PDO::PARAM_INT);
            $stmt->bindParam(':related_type', $related_type);
            $stmt->bindParam(':send_via_email', $send_via_email, PDO::PARAM_INT);
            $stmt->bindParam(':send_via_sms', $send_via_sms, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification by ID
     */
    public function getById($notification_id)
    {
        $query = "SELECT * FROM ta_notifications WHERE notification_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread($user_id)
    {
        $query = "SELECT * FROM ta_notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all notifications for a user
     */
    public function getForUser($user_id, $limit = 20)
    {
        $query = "SELECT * FROM ta_notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id)
    {
        $query = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Mark notification as sent
     */
    public function markSent($notification_id, $channel = 'email')
    {
        if ($channel === 'email') {
            $query = "UPDATE notifications SET email_sent = 1, email_status = 'SENT', sent_at = NOW() WHERE notification_id = :id";
        } else {
            $query = "UPDATE notifications SET sms_sent = 1, sms_status = 'SENT', sent_at = NOW() WHERE notification_id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Mark notification as failed
     */
    public function markFailed($notification_id, $channel = 'email')
    {
        if ($channel === 'email') {
            $query = "UPDATE notifications SET email_status = 'FAILED' WHERE notification_id = :id";
        } else {
            $query = "UPDATE notifications SET sms_status = 'FAILED' WHERE notification_id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get count of unread notifications
     */
    public function getUnreadCount($user_id)
    {
        $query = "SELECT COUNT(*) as count FROM ta_notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
