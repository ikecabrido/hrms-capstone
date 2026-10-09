<?php
/** Admin AJAX: assign / unassign a learning path to an employee.
 *  Acts on ld_learning_path.assigned_to:
 *    - assign : set assigned_to = employee_id
 *    - unassign : set assigned_to = NULL
 *
 *  POST params:
 *    action      = assign | unassign
 *    path_id     = ld_learning_path.id
 *    employee_id = em_employees.employee_id (for assign)
 *
 *  Must be a logged-in admin session.
 *  Returns the updated path row on success.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/LearningRole.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$adminId    = (int)($_SESSION['employee_id'] ?? 0);
$action     = trim($_POST['action'] ?? '');
$pathId     = (int)($_POST['path_id'] ?? 0);
$employeeId = (int)($_POST['employee_id'] ?? 0);

if ($action !== 'assign' && $action !== 'unassign') {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'action must be assign or unassign']));
}
if ($pathId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'path_id is required']));
}

if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'employee_id is required']));
}

try {
    $pdo = (new Database())->getConnection();

    if (LearningRole::forEmployee($pdo, $adminId) !== 'admin') {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Learning admin access required']));
    }

    // Path exists & active?
    $stmt = $pdo->prepare("SELECT id, assigned_to, status FROM ld_learning_path WHERE id = :pid LIMIT 1");
    $stmt->execute([':pid' => $pathId]);
    $path = $stmt->fetch();
    if (!$path) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Learning path not found']));
    }
    if ($path['status'] !== 'active') {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Learning path is not active']));
    }

    if ($action === 'assign') {
        // Employee exists?
        $stmt = $pdo->prepare("SELECT e.employee_id FROM em_employees e
                       INNER JOIN user_account u ON u.employee_id = e.employee_id
                       WHERE e.employee_id = :eid AND e.employment_status = 'Active' LIMIT 1");
        $stmt->execute([':eid' => $employeeId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'Employee not found']));
        }
        $stmt = $pdo->prepare("INSERT INTO ld_learning_path_invitation (learning_path_id, employee_id, status, invited_by)
                               VALUES (:path_id, :employee_id, 'invited', :invited_by)
                               ON DUPLICATE KEY UPDATE
                                   status = IF(status IN ('declined', 'withdrawn'), 'invited', status),
                                   invited_by = VALUES(invited_by)");
        $stmt->execute([':path_id' => $pathId, ':employee_id' => $employeeId, ':invited_by' => $adminId]);
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'This employee already has a path invitation']));
        }
    } else {
        $stmt = $pdo->prepare("UPDATE ld_learning_path_invitation
                               SET status = 'withdrawn', responded_at = NOW()
                               WHERE learning_path_id = :path_id AND employee_id = :employee_id
                                 AND status IN ('invited', 'enrolled')");
        $stmt->execute([':path_id' => $pathId, ':employee_id' => $employeeId]);
        $affected = $stmt->rowCount();
        $pdo->prepare('UPDATE ld_learning_path SET assigned_to = NULL, updated_at = NOW() WHERE id = :path_id AND assigned_to = :employee_id')
            ->execute([':path_id' => $pathId, ':employee_id' => $employeeId]);
    }

    // Return updated path
    $stmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.instructor_id, lp.type,
               lp.status, lp.is_public, lp.kt_plan_id,
               lp.assigned_to, lp.created_at, lp.updated_at,
               CONCAT(em.first_name, ' ', em.last_name) AS assigned_to_name
        FROM ld_learning_path lp
        LEFT JOIN em_employees em ON em.employee_id = lp.assigned_to
        WHERE lp.id = :pid
    ");
    $stmt->execute([':pid' => $pathId]);
    $updated = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'path' => $updated,
        'updated_by' => $adminId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
