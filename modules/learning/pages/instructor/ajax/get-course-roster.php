<?php
header('Content-Type: application/json');
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    if ($instructorId <= 0) {
        http_response_code(401);
        die(json_encode(['error' => 'Unauthorized']));
    }

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    if ($courseId <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'course_id is required']));
    }

    $pdo = (new Database())->getConnection();

    // Verify course exists
    $stmt = $pdo->prepare("SELECT id FROM ld_course WHERE id = :cid");
    $stmt->execute([':cid' => $courseId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['error' => 'Course not found']));
    }

    $stmt = $pdo->prepare("
        SELECT e.id AS enrollment_id, e.learner_id, e.status, e.enrolled_at, e.completed_at, e.last_accessed_at,
               emp.first_name, emp.last_name, emp.email,
               (SELECT final_score FROM ld_grade WHERE learner_id = e.learner_id AND course_id = e.course_id LIMIT 1) AS final_score,
               (SELECT COUNT(*) FROM ld_progress WHERE enrollment_id = e.id AND status = 'completed') AS completed_items,
               (SELECT COUNT(*) FROM ld_progress WHERE enrollment_id = e.id) AS total_items
        FROM ld_enrollment e
        JOIN em_employees emp ON emp.employee_id = e.learner_id
        WHERE e.course_id = :cid AND e.status != 'archived'
        ORDER BY emp.last_name ASC, emp.first_name ASC
    ");
    $stmt->execute([':cid' => $courseId]);
    $roster = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $stats = ['total' => count($roster), 'enrolled' => 0, 'in_progress' => 0, 'completed' => 0, 'invited' => 0, 'withdrawn' => 0];
    foreach ($roster as $r) {
        $s = $r['status'];
        if (isset($stats[$s])) $stats[$s]++;
    }

    http_response_code(200);
    echo json_encode(['roster' => $roster, 'stats' => $stats]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
