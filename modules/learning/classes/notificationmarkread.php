<?php
/**
 * Marks the reader's own notifications read.
 *
 * The admin, instructor and learner lists post the same two operations — read one row,
 * or read every row — so the SQL lives here once and each endpoint is a one-line
 * bootstrap. Scoping every UPDATE to :user_id is the security boundary: a caller can
 * name any notification id, but only their own rows can change.
 */
class NotificationMarkRead {
    /** Mark one notification read, returning how many rows changed. */
    public static function markOne(PDO $pdo, int $userId, int $notificationId): int {
        $stmt = $pdo->prepare(
            'UPDATE ld_notification SET is_read = 1 WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);

        return $stmt->rowCount();
    }

    /** Mark every unread notification for the reader, returning how many rows changed. */
    public static function markAll(PDO $pdo, int $userId): int {
        $stmt = $pdo->prepare(
            'UPDATE ld_notification SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->rowCount();
    }

    /** The notification the request names; the lists send notification_id, rows send id. */
    public static function requestedId(): int {
        return (int) ($_POST['notification_id'] ?? $_POST['id'] ?? 0);
    }

    /** Mark the requested notification read and print the JSON reply. */
    public static function single(): void {
        self::handle(function (PDO $pdo, int $userId): int {
            return self::markOne($pdo, $userId, self::requestedId());
        });
    }

    /** Mark every unread notification read and print the JSON reply. */
    public static function all(): void {
        self::handle(function (PDO $pdo, int $userId): int {
            return self::markAll($pdo, $userId);
        });
    }

    /**
     * Shared request handling: session, method, connection, then the work. The session
     * and header guards make the class safe to include from a process (such as the test
     * harness) where both may already be settled.
     */
    private static function handle(callable $work): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        if (!isset($_SESSION['employee_id'])) {
            self::fail(401, 'Unauthorized.');

            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::fail(405, 'Method not allowed.');

            return;
        }

        require_once dirname(__DIR__, 3) . '/database/db.php';

        try {
            $updated = $work((new Database())->getConnection(), (int) $_SESSION['employee_id']);
            echo json_encode([
                'success' => true,
                'updated' => $updated,
                'message' => 'Notification marked as read.',
            ]);
        } catch (Throwable $e) {
            self::fail(500, 'Failed to mark notification as read.');
        }
    }

    private static function fail(int $status, string $message): void {
        if (!headers_sent()) {
            http_response_code($status);
        }
        echo json_encode(['success' => false, 'message' => $message]);
    }
}
