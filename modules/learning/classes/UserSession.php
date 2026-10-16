<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * UserSession — a lightweight, database-backed registry of signed-in sessions
 * for the Learning module. It powers the "Security" block on the profile pages:
 * last login, active devices, and "sign out other sessions".
 *
 * Backed by ld_user_session (see sql/create_user_session.sql). The table is also
 * created lazily so the feature works before the migration is imported on a
 * given environment — the same pattern as UserProfile.
 */
class UserSession
{
    /** Sessions idle for longer than this many minutes are not "active". */
    public const ACTIVE_WINDOW_MINUTES = 180;

    /** last_seen_at is only refreshed once per this many seconds, to keep writes cheap. */
    private const TOUCH_THROTTLE_SECONDS = 60;

    private PDO $conn;
    private static bool $ensured = false;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $this->conn = (new Database())->getConnection();
        }
    }

    private function ensureTable(): void
    {
        if (self::$ensured) {
            return;
        }

        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS ld_user_session (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                user_id INT NULL,
                session_token CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                device_label VARCHAR(120) NULL,
                revoked TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ld_user_session_token (session_token),
                KEY idx_ld_user_session_employee (employee_id, last_seen_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        self::$ensured = true;
    }

    /**
     * Stable, non-reversible identifier for the current PHP session. Never store
     * the raw session id in the database.
     */
    public static function currentToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = session_id();

        return $sessionId === '' ? '' : hash('sha256', $sessionId);
    }

    /**
     * Turn a user-agent string into a friendly "Browser on OS" label.
     *
     * @return array{os:string,browser:string,label:string}
     */
    public static function describeDevice(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        $os = 'Unknown OS';
        foreach ([
            'Windows' => '/Windows/i',
            'macOS'   => '/Macintosh|Mac OS X/i',
            'Android' => '/Android/i',
            'iOS'     => '/iPhone|iPad|iPod/i',
            'Linux'   => '/Linux/i',
        ] as $label => $pattern) {
            if (preg_match($pattern, $ua)) {
                $os = $label;
                break;
            }
        }

        $browser = 'Browser';
        foreach ([
            'Edge'    => '/Edg\//i',
            'Chrome'  => '/Chrome\//i',
            'Firefox' => '/Firefox\//i',
            'Safari'  => '/Safari\//i',
        ] as $label => $pattern) {
            if (preg_match($pattern, $ua)) {
                $browser = $label;
                break;
            }
        }

        return ['os' => $os, 'browser' => $browser, 'label' => $browser . ' on ' . $os];
    }

    /**
     * Record a brand-new session at login time.
     */
    public function register(int $employeeId, ?int $userId, ?string $ip, ?string $userAgent): void
    {
        if ($employeeId <= 0) {
            return;
        }

        $token = self::currentToken();
        if ($token === '') {
            return;
        }

        $this->ensureTable();

        $device = self::describeDevice($userAgent)['label'];

        $sql = 'INSERT INTO ld_user_session
                    (employee_id, user_id, session_token, ip_address, user_agent, device_label, revoked, last_seen_at)
                VALUES
                    (:employee_id, :user_id, :token, :ip, :ua, :device, 0, NOW())
                ON DUPLICATE KEY UPDATE
                    last_seen_at = NOW(),
                    revoked = 0';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':user_id'     => $userId,
            ':token'       => $token,
            ':ip'          => $ip !== null ? substr($ip, 0, 45) : null,
            ':ua'          => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            ':device'      => substr($device, 0, 120),
        ]);
    }

    /**
     * Refresh the current session's last-seen time (throttled) and report whether
     * it may continue. Returns false when the session was revoked elsewhere.
     */
    public function touch(int $employeeId, ?int $userId, ?string $ip, ?string $userAgent): bool
    {
        if ($employeeId <= 0) {
            return true;
        }

        $token = self::currentToken();
        if ($token === '') {
            return true;
        }

        $this->ensureTable();

        $device = self::describeDevice($userAgent)['label'];
        $throttle = self::TOUCH_THROTTLE_SECONDS;

        // Keep the row pointed at the session's current owner, but never clear
        // `revoked` here — an interrupted session must stay signed out.
        $sql = 'INSERT INTO ld_user_session
                    (employee_id, user_id, session_token, ip_address, user_agent, device_label, revoked, last_seen_at)
                VALUES
                    (:employee_id, :user_id, :token, :ip, :ua, :device, 0, NOW())
                ON DUPLICATE KEY UPDATE
                    employee_id = :employee_id2,
                    user_id = :user_id2,
                    last_seen_at = IF(last_seen_at < (NOW() - INTERVAL ' . $throttle . ' SECOND), NOW(), last_seen_at)';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':employee_id'  => $employeeId,
            ':employee_id2' => $employeeId,
            ':user_id'      => $userId,
            ':user_id2'     => $userId,
            ':token'        => $token,
            ':ip'           => $ip !== null ? substr($ip, 0, 45) : null,
            ':ua'           => $userAgent !== null ? substr($userAgent, 0, 255) : null,
            ':device'       => substr($device, 0, 120),
        ]);

        $check = $this->conn->prepare('SELECT revoked FROM ld_user_session WHERE session_token = :token LIMIT 1');
        $check->execute([':token' => $token]);
        $revoked = $check->fetchColumn();

        if ($revoked === false || $revoked === null) {
            return true;
        }

        return (int) $revoked === 0;
    }

    /**
     * Active sessions for an employee, newest activity first.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listActive(int $employeeId, string $currentToken = ''): array
    {
        if ($employeeId <= 0) {
            return [];
        }

        $this->ensureTable();

        $window = self::ACTIVE_WINDOW_MINUTES;
        $sql = 'SELECT id, session_token, ip_address, device_label, user_agent, created_at, last_seen_at
                FROM ld_user_session
                WHERE employee_id = :employee_id
                  AND revoked = 0
                  AND last_seen_at >= (NOW() - INTERVAL ' . $window . ' MINUTE)
                ORDER BY last_seen_at DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            $row['is_current'] = ($currentToken !== '' && hash_equals($currentToken, (string) $row['session_token']));
            if (empty($row['device_label'])) {
                $row['device_label'] = self::describeDevice($row['user_agent'] ?? '')['label'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Recent sign-ins (including ended ones) for the login history list.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listRecent(int $employeeId, int $limit = 5): array
    {
        if ($employeeId <= 0) {
            return [];
        }

        $this->ensureTable();

        $limit = max(1, min(20, $limit));
        $sql = 'SELECT id, session_token, ip_address, device_label, user_agent, revoked, created_at, last_seen_at
                FROM ld_user_session
                WHERE employee_id = :employee_id
                ORDER BY created_at DESC
                LIMIT ' . $limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            if (empty($row['device_label'])) {
                $row['device_label'] = self::describeDevice($row['user_agent'] ?? '')['label'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Revoke every session for the employee except the current one.
     * Returns the number of sessions that were signed out.
     */
    public function revokeOthers(int $employeeId, string $currentToken): int
    {
        if ($employeeId <= 0 || $currentToken === '') {
            return 0;
        }

        $this->ensureTable();

        $sql = 'UPDATE ld_user_session
                SET revoked = 1
                WHERE employee_id = :employee_id
                  AND revoked = 0
                  AND session_token <> :token';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId, ':token' => $currentToken]);

        return $stmt->rowCount();
    }

    /**
     * Mark the current session as ended (used on logout).
     */
    public function endCurrent(int $employeeId, string $currentToken): void
    {
        if ($employeeId <= 0 || $currentToken === '') {
            return;
        }

        $this->ensureTable();

        $stmt = $this->conn->prepare(
            'UPDATE ld_user_session SET revoked = 1 WHERE employee_id = :employee_id AND session_token = :token'
        );
        $stmt->execute([':employee_id' => $employeeId, ':token' => $currentToken]);
    }
}
