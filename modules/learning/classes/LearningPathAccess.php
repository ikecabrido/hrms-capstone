<?php
require_once __DIR__ . '/LearningRole.php';

class LearningPathAccess
{
    public static function canView(PDO $pdo, int $learningPathId, int $employeeId): bool
    {
        if ($learningPathId <= 0 || $employeeId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT 1
                               FROM ld_learning_path lp
                               WHERE lp.id = :path_id
                                 AND lp.status = 'active'
                                 AND (
                                     lp.is_public = 1
                                     OR (
                                         NOT EXISTS (
                                             SELECT 1 FROM ld_learning_path_invitation rejected
                                             WHERE rejected.learning_path_id = lp.id
                                               AND rejected.employee_id = :rejected_employee
                                               AND rejected.status IN ('declined', 'withdrawn')
                                         )
                                         AND (
                                             lp.assigned_to = :assigned_employee
                                             OR EXISTS (
                                                 SELECT 1 FROM ld_learning_path_invitation pi
                                                 WHERE pi.learning_path_id = lp.id
                                                   AND pi.employee_id = :invited_employee
                                                   AND pi.status IN ('invited', 'enrolled')
                                             )
                                             OR EXISTS (
                                                 SELECT 1 FROM exit_knowledge_transfer_plans kt
                                                 WHERE kt.id = lp.kt_plan_id
                                                   AND kt.successor_id = :successor_employee
                                             )
                                         )
                                     )
                                 )
                               LIMIT 1");
        $stmt->execute([
            ':path_id' => $learningPathId,
            ':rejected_employee' => $employeeId,
            ':assigned_employee' => $employeeId,
            ':invited_employee' => $employeeId,
            ':successor_employee' => $employeeId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public static function canManage(PDO $pdo, int $learningPathId, int $employeeId): bool
    {
        if ($learningPathId <= 0 || $employeeId <= 0) {
            return false;
        }

        $role = LearningRole::forEmployee($pdo, $employeeId);
        if ($role === 'admin') {
            return true;
        }
        if ($role !== 'instructor') {
            return false;
        }

        $stmt = $pdo->prepare('SELECT 1 FROM ld_learning_path WHERE id = :path_id AND instructor_id = :employee_id LIMIT 1');
        $stmt->execute([
            ':path_id' => $learningPathId,
            ':employee_id' => $employeeId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}