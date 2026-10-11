<?php

class AccountModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the user_account row linked to an employee.
     */
    public function getByEmployeeId(int $employeeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT user_id, employee_id, password, account_status,
                   last_login, password_changed_at
            FROM user_account
            WHERE employee_id = :employee_id
            LIMIT 1
        ");
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Save a new (already-hashed) password for a user account and
     * stamp password_changed_at.
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user_account
            SET
                password = :password,
                password_changed_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ");

        return $stmt->execute([
            ':password' => $passwordHash,
            ':user_id' => $userId,
        ]);
    }
}
