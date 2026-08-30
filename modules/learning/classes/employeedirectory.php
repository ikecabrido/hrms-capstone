<?php
/**
 * EmployeeDirectory — Internal read-only access to employee profiles.
 * Since em_employees lives in the same database, this is a direct read,
 * not an API push endpoint.
 *
 * Used by: prerequisite checks, learner profile display, enrollment roster
 */
include_once __DIR__ . '/../../../database/db.php';

class EmployeeDirectory
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
     * Get a single employee's profile from em_employees.
     */
    public function getProfile(int $employeeId): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT e.employee_id, e.first_name, e.last_name, e.email, e.phone,
                   d.department_name, p.position_name, e.hire_date
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE e.employee_id = :eid
            LIMIT 1
        ");
        $stmt->execute([':eid' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get multiple employees (for roster lookups).
     */
    public function getProfiles(array $employeeIds): array
    {
        if (empty($employeeIds)) return [];

        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $stmt = $this->conn->prepare("
            SELECT e.employee_id, e.first_name, e.last_name, e.email,
                   d.department_name, p.position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE e.employee_id IN ($placeholders)
        ");
        $stmt->execute(array_map('intval', $employeeIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee's completed skills (from Learning enrollments).
     */
    public function getCompletedSkills(int $employeeId): array
    {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT s.id, s.name
            FROM ld_skill s
            JOIN ld_course_skill cs ON cs.skill_id = s.id
            JOIN ld_enrollment en ON en.course_id = cs.course_id
            WHERE en.learner_id = :eid AND en.status = 'completed'
            ORDER BY s.name ASC
        ");
        $stmt->execute([':eid' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get employee's enrollment summary (counts by status).
     */
    public function getEnrollmentSummary(int $employeeId): array
    {
        $stmt = $this->conn->prepare("
            SELECT status, COUNT(*) AS cnt
            FROM ld_enrollment
            WHERE learner_id = :eid
            GROUP BY status
        ");
        $stmt->execute([':eid' => $employeeId]);
        $summary = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $summary[$row['status']] = (int) $row['cnt'];
        }
        return $summary;
    }

    /**
     * Check if an employee exists in the system.
     */
    public function exists(int $employeeId): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM em_employees WHERE employee_id = :eid LIMIT 1");
        $stmt->execute([':eid' => $employeeId]);
        return (bool) $stmt->fetchColumn();
    }
}
