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

    $optionId = (int)$data['id'];
    $optionText = $data['option_text'] ?? null;
    $isCorrect = (bool)($data['is_correct'] ?? false);

    if (!$optionText) {
        http_response_code(400);
        die(json_encode(['error' => 'Option text is required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_evaluation_question_option 
        SET option_text = ?, is_correct = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $optionText,
        $isCorrect ? 1 : 0,
        $optionId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $optionId,
        'message' => 'Evaluation option updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
