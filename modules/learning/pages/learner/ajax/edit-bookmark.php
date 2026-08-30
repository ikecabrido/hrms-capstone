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

    $bookmarkId = (int)$data['id'];
    $lessonId = $data['lesson_id'] ?? null;
    $employeeId = $data['employee_id'] ?? null;
    $notes = $data['notes'] ?? null;

    if (!$lessonId || !$employeeId) {
        http_response_code(400);
        die(json_encode(['error' => 'Lesson ID and employee ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_bookmark 
        SET notes = ?, updated_at = NOW()
        WHERE id = ? AND lesson_id = ? AND employee_id = ?
    ");

    $stmt->execute([
        $notes,
        $bookmarkId,
        $lessonId,
        $employeeId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $bookmarkId,
        'message' => 'Bookmark updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
