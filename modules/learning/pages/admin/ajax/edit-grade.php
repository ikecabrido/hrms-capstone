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

    $gradeId = (int)$data['id'];
    $employeeId = $data['employee_id'] ?? null;
    $courseId = $data['course_id'] ?? null;
    $score = $data['score'] ?? null;
    $grade = $data['grade'] ?? null;
    $status = $data['status'] ?? 'pending';

    if (!$employeeId || !$courseId) {
        http_response_code(400);
        die(json_encode(['error' => 'Employee ID and course ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_grade 
        SET score = ?, grade = ?, status = ?, updated_at = NOW()
        WHERE id = ? AND employee_id = ? AND course_id = ?
    ");

    $stmt->execute([
        $score,
        $grade,
        $status,
        $gradeId,
        $employeeId,
        $courseId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $gradeId,
        'message' => 'Grade updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
