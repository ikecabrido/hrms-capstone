<?php
/** Admin AJAX: list active learning paths relevant to an employee.
 *  - Paths already assigned to that employee (assigned_to = :eid)
 *  - Paths available to assign (active, is_public=1, assigned_to IS NULL)
 *  - Employee's current enrollments (so the UI can show overlap)
 *
 *  GET params: employee_id
 *  Must be a logged-in admin session.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 6) . '/database/db.php';

$employeeId = (int)($_GET['employee_id'] ?? 0);
if ($employeeId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'employee_id is required']));
}

try {
    $pdo = (new Database())->getConnection();

    // Employee exists?
    $stmt = $pdo->prepare("SELECT employee_id, first_name, last_name, email, department_id, position_id
                           FROM em_employees WHERE employee_id = :eid LIMIT 1");
    $stmt->execute([':eid' => $employeeId]);
    $emp = $stmt->fetch();
    if (!$emp) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Employee not found']));
    }

    // Paths already assigned to this employee
    $assignedStmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.instructor_id, lp.type,
               lp.status, lp.is_public, lp.kt_plan_id, lp.created_at
        FROM ld_learning_path lp
        WHERE lp.assigned_to = :eid AND lp.status = 'active'
        ORDER BY lp.created_at DESC
    ");
    $assignedStmt->execute([':eid' => $employeeId]);
    $assignedPaths = $assignedStmt->fetchAll();

    // Enrollments already tied to this employee (for overlap display)
    $enrollStmt = $pdo->prepare("
        SELECT e.id, e.course_id, c.title AS course_title, e.status, e.enrolled_at
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        WHERE e.learner_id = :eid
        ORDER BY e.enrolled_at DESC
    ");
    $enrollStmt->execute([':eid' => $employeeId]);
    $enrollments = $enrollStmt->fetchAll();

    // Available paths to assign: active, public, currently unassigned
    $availStmt = $pdo->prepare("
        SELECT lp.id, lp.title, lp.description, lp.instructor_id, lp.type,
               lp.status, lp.is_public, lp.kt_plan_id, lp.created_at,
               CONCAT(em.first_name, ' ', em.last_name) AS instructor_name
        FROM ld_learning_path lp
        LEFT JOIN em_employees em ON em.employee_id = lp.instructor_id
        WHERE lp.status = 'active'
          AND lp.is_public = 1
          AND (lp.assigned_to IS NULL OR lp.assigned_to != :eid)
        ORDER BY lp.title ASC
    ");
    $availStmt->execute([':eid' => $employeeId]);
    $availablePaths = $availStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'employee' => [
            'employee_id' => (int)$emp['employee_id'],
            'first_name' => $emp['first_name'],
            'last_name' => $emp['last_name'],
            'email' => $emp['email'],
            'department_id' => (int)($emp['department_id'] ?? 0),
            'position_id' => (int)($emp['position_id'] ?? 0),
        ],
        'assigned_paths' => $assignedPaths,
        'enrollments' => $enrollments,
        'available_paths' => $availablePaths,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
