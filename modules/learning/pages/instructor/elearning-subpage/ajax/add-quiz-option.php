<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    $questionId = isset($input['question_id']) ? (int) $input['question_id'] : 0;
    $optionText = trim((string) ($input['option_text'] ?? ''));
    $isCorrect = !empty($input['is_correct']) ? 1 : 0;

    if ($questionId <= 0 || $optionText === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'question_id and option_text are required.']);
        exit;
    }

    // Get max order_index
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 FROM ld_quiz_question_option WHERE question_id = :qid");
    $stmt->execute([':qid' => $questionId]);
    $orderIndex = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (:qid, :text, :correct, :idx)");
    $stmt->execute([
        ':qid' => $questionId,
        ':text' => $optionText,
        ':correct' => $isCorrect,
        ':idx' => $orderIndex,
    ]);

    $optionId = (int) $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $optionId, 'message' => 'Option added.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
