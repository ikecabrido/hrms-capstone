<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__DIR__, 4) . '/classes/CSRF.php';
require_once dirname(__DIR__, 4) . '/classes/LearningRole.php';
CSRF::requireValid();

$actorId = (int) ($_SESSION['employee_id'] ?? 0);
if ($actorId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sign in required.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$employeeId = (int) ($data['employee_id'] ?? 0);
$learningRole = (string) ($data['learning_role'] ?? '');
$allowedRoles = ['admin', 'instructor', 'learner'];

if ($employeeId <= 0 || !in_array($learningRole, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose a valid user and Learning role.']);
    exit;
}

if ($employeeId === $actorId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot change your own role.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (LearningRole::forEmployee($pdo, $actorId) !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Learning admin access required.']);
        exit;
    }

    $target = $pdo->prepare("SELECT 1 FROM em_employees e
                             INNER JOIN user_account u ON u.employee_id = e.employee_id
                             WHERE e.employee_id = :employee_id
                               AND e.employment_status = 'Active'
                             LIMIT 1");
    $target->execute([':employee_id' => $employeeId]);
    if (!$target->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Active user account not found.']);
        exit;
    }

    if ($learningRole !== 'admin') {
        $admins = $pdo->prepare("SELECT e.employee_id, e.role_id, r.role_name, d.department_name,
                                        lr.learning_role AS assigned_learning_role
                                 FROM em_employees e
                                 INNER JOIN user_account u ON u.employee_id = e.employee_id
                                 LEFT JOIN em_roles r ON r.role_id = e.role_id
                                 LEFT JOIN em_departments d ON d.department_id = e.department_id
                                 LEFT JOIN ld_user_role lr ON lr.employee_id = e.employee_id
                                 WHERE e.employment_status = 'Active'
                                   AND e.employee_id <> :target_id");
        $admins->execute([':target_id' => $employeeId]);
        $hasAnotherAdmin = false;
        foreach ($admins->fetchAll(PDO::FETCH_ASSOC) as $user) {
            if (LearningRole::resolveWithAssignment($user, $user['assigned_learning_role'] ?? null) === 'admin') {
                $hasAnotherAdmin = true;
                break;
            }
        }

        if (!$hasAnotherAdmin) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Promote another user to admin before demoting the last Learning Admin.']);
            exit;
        }
    }

    $save = $pdo->prepare("INSERT INTO ld_user_role (employee_id, learning_role, updated_by)
                           VALUES (:employee_id, :learning_role, :updated_by)
                           ON DUPLICATE KEY UPDATE
                               learning_role = VALUES(learning_role),
                               updated_by = VALUES(updated_by)");
    $save->execute([
        ':employee_id' => $employeeId,
        ':learning_role' => $learningRole,
        ':updated_by' => $actorId,
    ]);

    echo json_encode(['success' => true, 'learning_role' => $learningRole]);
} catch (Throwable $e) {
    error_log('[learning] User role update failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update the Learning role.']);
}