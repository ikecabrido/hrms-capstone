<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include dirname(__FILE__, 6) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $enrollmentId = (int)$data['id'];
    $employeeId = $data['employee_id'] ?? null;
    $courseId = $data['course_id'] ?? null;
    $status = $data['status'] ?? 'active';
    $enrolledAt = $data['enrolled_at'] ?? null;
    $completedAt = $data['completed_at'] ?? null;

    if (!$employeeId || !$courseId) {
        http_response_code(400);
        die(json_encode(['error' => 'Employee ID and course ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_enrollment 
        SET employee_id = ?, course_id = ?, status = ?, 
            enrolled_at = ?, completed_at = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $employeeId,
        $courseId,
        $status,
        $enrolledAt,
        $completedAt,
        $enrollmentId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $enrollmentId,
        'message' => 'Enrollment updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
