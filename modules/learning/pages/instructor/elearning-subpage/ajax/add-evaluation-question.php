<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}
require_once dirname(__FILE__, 7) . '/database/db.php';
require_once dirname(__FILE__, 5) . '/classes/dberror.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    /*
     * Evaluation questions live in ld_quiz_question keyed by
     * (item_type = 'evaluation', reference_id = <evaluation id>), the same shape
     * classes/evaluation.php and add-quiz-question.php already write. This endpoint
     * previously inserted into a `quiz_id` / `module_id` schema that does not exist,
     * and bound the text and type to the same :qt placeholder.
     */
    $referenceId = (int) ($input['evaluation_id'] ?? $input['reference_id'] ?? 0);
    $questionText = trim((string) ($input['question_text'] ?? ''));
    $questionType = trim((string) ($input['question_type'] ?? 'rating'));

    if ($referenceId <= 0 || $questionText === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'evaluation_id and question_text are required.']);
        exit;
    }

    $orderStmt = $pdo->prepare(
        "SELECT COALESCE(MAX(order_index), 0) + 1 FROM ld_quiz_question
         WHERE item_type = 'evaluation' AND reference_id = :rid"
    );
    $orderStmt->execute([':rid' => $referenceId]);
    $orderIndex = (int) $orderStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "INSERT INTO ld_quiz_question (item_type, reference_id, question_text, question_type, order_index, status)
         VALUES ('evaluation', :rid, :text, :type, :idx, 'active')"
    );
    $stmt->execute([
        ':rid'  => $referenceId,
        ':text' => $questionText,
        ':type' => $questionType,
        ':idx'  => $orderIndex,
    ]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    DbError::json($e, 'instructor/add-evaluation-question');
}
