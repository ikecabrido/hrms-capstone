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
    $quizId = isset($input['quiz_id']) ? (int) $input['quiz_id'] : 0;
    $questionText = trim((string) ($input['question_text'] ?? ''));
    $questionType = trim((string) ($input['question_type'] ?? 'single_choice'));
    $itemType = trim((string) ($input['item_type'] ?? 'quiz'));
    if (!in_array($itemType, ['quiz', 'evaluation'])) $itemType = 'quiz';
    $referenceId = isset($input['reference_id']) ? (int) $input['reference_id'] : $quizId;
    if ($referenceId <= 0) $referenceId = $quizId;

    if ($referenceId <= 0 || $questionText === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'reference_id and question_text are required.']);
        exit;
    }

    $allowedTypes = ['single_choice', 'multiple_choice', 'true_false'];
    if (!in_array($questionType, $allowedTypes)) {
        $questionType = 'single_choice';
    }

    // Get max order_index
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 FROM ld_quiz_question WHERE item_type = :itype AND reference_id = :rid");
    $stmt->execute([':itype' => $itemType, ':rid' => $referenceId]);
    $orderIndex = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status) VALUES (:itype, :rid, :text, :type, :idx, 'active')");
    $stmt->execute([
        ':itype' => $itemType,
        ':rid' => $referenceId,
        ':text' => $questionText,
        ':type' => $questionType,
        ':idx' => $orderIndex,
    ]);

    $questionId = (int) $pdo->lastInsertId();

    // For true_false, auto-create True/False options
    if ($questionType === 'true_false') {
        $optStmt = $pdo->prepare("INSERT INTO ld_quiz_question_option (question_id, option_text, is_correct, order_index) VALUES (:qid, :text, :correct, :idx)");
        $optStmt->execute([':qid' => $questionId, ':text' => 'True', ':correct' => 1, ':idx' => 1]);
        $optStmt->execute([':qid' => $questionId, ':text' => 'False', ':correct' => 0, ':idx' => 2]);
    }

    echo json_encode(['success' => true, 'id' => $questionId, 'message' => 'Question added.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
