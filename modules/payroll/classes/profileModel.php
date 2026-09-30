<?php

class ProfileModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get the full profile for one employee: personal/contact info
     * (em_employees), department/position names, and account meta
     * (user_account — theme, profile picture, status, last login).
     */
    public function getProfile(int $employeeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.employee_id,
                e.employee_code,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.suffix,
                e.email,
                e.mobile_no,
                e.phone_no,
                e.current_address,
                e.permanent_address,
                e.hire_date,
                e.employment_status,
                e.employment_type,
                d.department_name,
                p.position_name,
                ua.user_id,
                ua.last_login,
                ua.password_changed_at
            FROM em_employees AS e
            LEFT JOIN em_departments AS d ON e.department_id = d.department_id
            LEFT JOIN em_positions AS p ON e.position_id = p.position_id
            LEFT JOIN user_account AS ua ON ua.employee_id = e.employee_id
            WHERE e.employee_id = :employee_id
            LIMIT 1
        ");
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update the contact-info fields an employee is allowed to
     * self-edit. Core HR fields (name, department, position, hire
     * date, employment status, etc.) are intentionally excluded —
     * those remain managed by HR/Admin elsewhere.
     */
    public function updateContactInfo(int $employeeId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE em_employees
            SET
                email = :email,
                mobile_no = :mobile_no,
                phone_no = :phone_no,
                current_address = :current_address,
                permanent_address = :permanent_address,
                updated_at = CURRENT_TIMESTAMP
            WHERE employee_id = :employee_id
        ");

        return $stmt->execute([
            ':email' => $data['email'],
            ':mobile_no' => $data['mobile_no'],
            ':phone_no' => $data['phone_no'],
            ':current_address' => $data['current_address'],
            ':permanent_address' => $data['permanent_address'],
            ':employee_id' => $employeeId,
        ]);
    }

    /**
     * Check whether an email address is already used by a
     * different employee (for basic uniqueness validation).
     */
    public function isEmailTaken(string $email, int $excludeEmployeeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT employee_id FROM em_employees
            WHERE email = :email AND employee_id != :employee_id
            LIMIT 1
        ");
        $stmt->execute([
            ':email' => $email,
            ':employee_id' => $excludeEmployeeId,
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update the viewer's theme preference (light/dark), stored
     * on user_account.
     */
    public function updateTheme(int $employeeId, string $theme): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user_account
            SET theme = :theme, updated_at = CURRENT_TIMESTAMP
            WHERE employee_id = :employee_id
        ");

        return $stmt->execute([
            ':theme' => $theme,
            ':employee_id' => $employeeId,
        ]);
    }
}
