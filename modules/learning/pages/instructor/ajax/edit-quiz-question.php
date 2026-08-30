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

    $questionId = (int)$data['id'];
    $questionText = $data['question_text'] ?? null;
    $questionType = $data['question_type'] ?? 'multiple_choice';
    $status = $data['status'] ?? 'active';

    if (!$questionText) {
        http_response_code(400);
        die(json_encode(['error' => 'Question text is required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_quiz_question 
        SET question_text = ?, question_type = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $questionText,
        $questionType,
        $status,
        $questionId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $questionId,
        'message' => 'Quiz question updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
