<?php
/**
 * Notification — the read model behind the shared sidebar's bell menu.
 *
 * Every module includes the sidebar, so this cannot live in one module's page code. Rows
 * come from the learning module's ld_notification table: today it is the only per-employee
 * notification store in the schema, it has an index on (user_id, is_read), and it already
 * receives rows from that module's AJAX endpoints and cron jobs. Because it keys on
 * user_id = the logged-in employee_id, the same bell works in all twelve modules; a module
 * whose events never write there simply shows the empty state.
 *
 * If the table is missing — a database without the learning schema — the queries throw and
 * the sidebar falls back to its empty state. The bell must never be able to take a page
 * down with it.
 */
final class Notification
{
    /** Per-employee notification store read by the shared sidebar. */
    public const TABLE = 'ld_notification';

    /**
     * Icons for the type values the learning module writes (see its notification pages);
     * anything unrecognised gets a plain bell rather than no icon.
     */
    private const ICONS = [
        'invitation'           => 'fa-user-plus',
        'certificate'          => 'fa-certificate',
        'certificate_expiry'   => 'fa-triangle-exclamation',
        'announcement'         => 'fa-bullhorn',
        'enrollment'           => 'fa-graduation-cap',
        'grade'                => 'fa-chart-line',
        'reminder'             => 'fa-clock',
        'conference_reminder'  => 'fa-video',
        'message'              => 'fa-envelope',
        'update'               => 'fa-sync',
        'knowledge_transfer'   => 'fa-people-arrows',
    ];

    private PDO $conn;

    /**
     * Pass a PDO to read from a specific connection; without one the application database
     * is used, the same way the module Employee classes work.
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;

            return;
        }

        require_once dirname(__DIR__) . '/database/db.php';

        $this->conn = (new Database())->getConnection();
    }

    /**
     * Newest notifications for one employee, for the bell menu.
     *
     * @return list<array{id:int,type:string,title:string,message:string,is_read:int,created_at:string}>
     */
    public function getRecent(int $userId, int $limit = 6): array
    {
        $limit = max(1, min($limit, 50));

        $stmt = $this->conn->prepare(
            'SELECT id, type, title, message, is_read, created_at
               FROM ' . self::TABLE . '
              WHERE user_id = :user_id
              ORDER BY created_at DESC, id DESC
              LIMIT ' . $limit
        );
        $stmt->execute([':user_id' => $userId]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id'         => (int) $row['id'],
                'type'       => (string) $row['type'],
                'title'      => (string) $row['title'],
                'message'    => (string) $row['message'],
                'is_read'    => (int) $row['is_read'],
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $rows;
    }

    /**
     * Records a notification for one employee and returns its id.
     *
     * This is how modules outside learning reach the bell: the sidebar reads a single
     * store, so any module that has something to tell an employee writes here instead of
     * inventing its own table. Recipients are session employee ids, i.e. em_employees ids.
     *
     * Throws InvalidArgumentException for a non-positive recipient — a programming error,
     * not a condition to paper over. Callers on a save path should wrap this so that a
     * notification failure cannot undo the work the user asked for.
     */
    public function push(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): int {
        if ($userId <= 0) {
            throw new InvalidArgumentException('A notification needs a recipient employee id.');
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO ' . self::TABLE . '
                 (user_id, type, title, message, reference_type, reference_id, is_read)
             VALUES (:user_id, :type, :title, :message, :reference_type, :reference_id, 0)'
        );
        $stmt->execute([
            ':user_id'        => $userId,
            ':type'           => $type,
            ':title'          => $title,
            ':message'        => $message,
            ':reference_type' => $referenceType,
            ':reference_id'   => $referenceId,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /** How many notifications are still unread — the number on the bell's badge. */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->execute([':user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    /** Marks every unread notification read; returns how many rows changed. */
    public function markAllAsRead(int $userId): int
    {
        $stmt = $this->conn->prepare(
            'UPDATE ' . self::TABLE . ' SET is_read = 1 WHERE user_id = :user_id AND is_read = 0'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->rowCount();
    }

    /**
     * Marks one notification read. Scoped to $userId so a request cannot touch somebody
     * else's notification. Returns how many rows changed (0 for an unknown or foreign id).
     */
    public function markAsRead(int $userId, int $id): int
    {
        $stmt = $this->conn->prepare(
            'UPDATE ' . self::TABLE . ' SET is_read = 1 WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount();
    }

    /** Font Awesome icon for a notification type. */
    public static function iconFor(string $type): string
    {
        return self::ICONS[$type] ?? 'fa-bell';
    }

    /**
     * Human age of a timestamp, e.g. "3h ago". $now is injectable so the format can be
     * tested without waiting for the clock.
     */
    public static function timeAgo(?string $createdAt, ?int $now = null): string
    {
        $timestamp = strtotime((string) $createdAt);
        if ($timestamp === false) {
            return '';
        }

        $diff = ($now ?? time()) - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . 'd ago';
        }

        return date('M j, Y', $timestamp);
    }
}
