<?php
require_once dirname(__DIR__, 3) . '/database/db.php';

class LearningRole
{
    private const ROLES = ['admin', 'instructor', 'learner'];

    public static function resolve(PDO $pdo, array $user): string
    {
        $stmt = $pdo->prepare('SELECT learning_role FROM ld_user_role WHERE employee_id = :employee_id LIMIT 1');
        $stmt->execute([':employee_id' => (int) ($user['employee_id'] ?? 0)]);
        $assignedRole = $stmt->fetchColumn();

        return self::resolveWithAssignment($user, $assignedRole !== false ? (string) $assignedRole : null);
    }

    public static function resolveWithAssignment(array $user, ?string $assignedRole): string
    {
        if ($assignedRole !== null && in_array($assignedRole, self::ROLES, true)) {
            return $assignedRole;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $roleName = strtolower((string) ($user['role_name'] ?? ''));
        $departmentName = strtolower((string) ($user['department_name'] ?? ''));
        $employeeId = (int) ($user['employee_id'] ?? 0);

        if ($roleId === 1 || stripos($roleName, 'system admin') !== false
            || in_array($employeeId, [35, 1007, 1018], true)) {
            return 'admin';
        }

        if ($roleId === 7 || stripos($roleName, 'learning') !== false
            || stripos($departmentName, 'instructor') !== false) {
            return 'instructor';
        }

        return 'learner';
    }

    public static function forEmployee(PDO $pdo, int $employeeId): string
    {
        $stmt = $pdo->prepare("SELECT e.employee_id, e.role_id, r.role_name, d.department_name,
                                      lr.learning_role AS assigned_learning_role
                               FROM em_employees e
                               LEFT JOIN em_roles r ON r.role_id = e.role_id
                               LEFT JOIN em_departments d ON d.department_id = e.department_id
                               LEFT JOIN ld_user_role lr ON lr.employee_id = e.employee_id
                               WHERE e.employee_id = :employee_id
                                 AND e.employment_status = 'Active'
                               LIMIT 1");
        $stmt->execute([':employee_id' => $employeeId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return 'learner';
        }

        return self::resolveWithAssignment($user, $user['assigned_learning_role'] ?? null);
    }
}