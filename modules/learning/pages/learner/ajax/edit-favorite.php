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

    $favoriteId = (int)$data['id'];
    $courseId = $data['course_id'] ?? null;
    $employeeId = $data['employee_id'] ?? null;

    if (!$courseId || !$employeeId) {
        http_response_code(400);
        die(json_encode(['error' => 'Course ID and employee ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_favorite 
        SET updated_at = NOW()
        WHERE id = ? AND course_id = ? AND employee_id = ?
    ");

    $stmt->execute([
        $favoriteId,
        $courseId,
        $employeeId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $favoriteId,
        'message' => 'Favorite updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
