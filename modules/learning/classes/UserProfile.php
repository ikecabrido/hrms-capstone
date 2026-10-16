<?php

include_once __DIR__ . '/../../../database/db.php';

/**
 * UserProfile — per-employee profile extras for the Learning module:
 * a learner-settable bio and an uploaded profile picture.
 *
 * Backed by ld_user_profile (see sql/create_user_profile.sql). The table is
 * also created lazily on first use so the feature works before the migration
 * is imported on a given environment.
 */
class UserProfile
{
    private PDO $conn;
    private static bool $ensured = false;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    private function ensureTable(): void
    {
        if (self::$ensured) {
            return;
        }

        $this->conn->exec(
            "CREATE TABLE IF NOT EXISTS ld_user_profile (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                bio TEXT NULL,
                profile_photo VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ld_user_profile_employee (employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        self::$ensured = true;
    }

    /**
     * Fetch the profile row for an employee. Returns a row with
     * bio/profile_photo keys, or a default empty row when none exists yet.
     */
    public function getForEmployee(int $employeeId): array
    {
        $defaults = ['employee_id' => $employeeId, 'bio' => null, 'profile_photo' => null];

        if ($employeeId <= 0) {
            return $defaults;
        }

        try {
            $stmt = $this->conn->prepare(
                'SELECT employee_id, bio, profile_photo FROM ld_user_profile WHERE employee_id = :employee_id LIMIT 1'
            );
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Table not created yet — degrade to "no profile extras".
            return $defaults;
        }

        return $row ?: $defaults;
    }

    /**
     * Save the learner's bio. Empty string clears it.
     */
    public function updateBio(int $employeeId, string $bio): array
    {
        if ($employeeId <= 0) {
            return ['success' => false, 'message' => 'Employee ID is required.'];
        }

        $bio = trim($bio);

        if (mb_strlen($bio) > 1000) {
            return ['success' => false, 'message' => 'Bio must be 1000 characters or fewer.'];
        }

        $this->ensureTable();

        $sql = 'INSERT INTO ld_user_profile (employee_id, bio) VALUES (:employee_id, :bio)
                ON DUPLICATE KEY UPDATE bio = :bio';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId, ':bio' => $bio === '' ? null : $bio]);

        return ['success' => true, 'bio' => $bio === '' ? null : $bio, 'message' => 'Bio updated.'];
    }

    /**
     * Store the profile picture filename (just the basename; callers resolve
     * the public URL). Passing null removes the picture.
     */
    public function updatePhoto(int $employeeId, ?string $filename): array
    {
        if ($employeeId <= 0) {
            return ['success' => false, 'message' => 'Employee ID is required.'];
        }

        $safeName = null;
        if ($filename !== null && $filename !== '') {
            $safeName = basename($filename);
        }

        $this->ensureTable();

        $sql = 'INSERT INTO ld_user_profile (employee_id, profile_photo) VALUES (:employee_id, :photo)
                ON DUPLICATE KEY UPDATE profile_photo = :photo';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId, ':photo' => $safeName]);

        return ['success' => true, 'profile_photo' => $safeName, 'message' => 'Profile picture updated.'];
    }
}
