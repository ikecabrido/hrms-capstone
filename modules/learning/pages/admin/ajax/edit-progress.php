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

    $progressId = (int)$data['id'];
    $employeeId = $data['employee_id'] ?? null;
    $courseId = $data['course_id'] ?? null;
    $completionPercentage = (int)($data['completion_percentage'] ?? 0);
    $status = $data['status'] ?? 'in_progress';
    $lastAccessedAt = $data['last_accessed_at'] ?? null;

    if (!$employeeId || !$courseId) {
        http_response_code(400);
        die(json_encode(['error' => 'Employee ID and course ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_progress 
        SET completion_percentage = ?, status = ?, last_accessed_at = ?, updated_at = NOW()
        WHERE id = ? AND employee_id = ? AND course_id = ?
    ");

    $stmt->execute([
        $completionPercentage,
        $status,
        $lastAccessedAt,
        $progressId,
        $employeeId,
        $courseId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $progressId,
        'message' => 'Progress updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
