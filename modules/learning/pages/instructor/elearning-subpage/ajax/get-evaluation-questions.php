<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Evaluation ID required']);
        exit;
    }

    $questions = $pdo->prepare('SELECT q.id, q.question_text, q.question_type, q.order_index, q.status FROM ld_quiz_question q WHERE q.item_type = ? AND q.reference_id = ? ORDER BY q.order_index ASC, q.id ASC');
    $questions->execute(['evaluation', $id]);
    $questionRows = $questions->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($questionRows as $question) {
        $optionsStmt = $pdo->prepare('SELECT id, option_text, is_correct, order_index FROM ld_quiz_question_option WHERE question_id = ? ORDER BY order_index ASC, id ASC');
        $optionsStmt->execute([(int) $question['id']]);
        $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);

        $result[] = [
            'id' => (int) $question['id'],
            'question_text' => $question['question_text'],
            'question_type' => $question['question_type'],
            'order_index' => (int) $question['order_index'],
            'status' => $question['status'],
            'options' => array_map(function ($option) {
                return [
                    'id' => (int) $option['id'],
                    'option_text' => $option['option_text'],
                    'is_correct' => (bool) $option['is_correct'],
                    'order_index' => (int) $option['order_index'],
                ];
            }, $options),
            'correct_answer' => !empty($options) && !empty($options[0]['option_text']) ? $options[0]['option_text'] : ''
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
