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
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $enrollmentId = (int) ($input['enrollment_id'] ?? 0);

    if ($enrollmentId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'enrollment_id is required']));
    }

    $pdo = (new Database())->getConnection();

    // Verify instructor owns the course for this enrollment
    $stmt = $pdo->prepare("SELECT id FROM ld_enrollment WHERE id = :eid");
    $stmt->execute([':eid' => $enrollmentId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Enrollment not found']));
    }

    // Withdraw the learner
    $stmt = $pdo->prepare("UPDATE ld_enrollment SET status = 'withdrawn' WHERE id = :eid AND status NOT IN ('withdrawn', 'archived')");
    $stmt->execute([':eid' => $enrollmentId]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Learner withdrawn from course']);
    } else {
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'Enrollment already withdrawn or not found']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
