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
    $questionId = isset($input['id']) ? (int) $input['id'] : 0;
    $questionText = trim((string) ($input['question_text'] ?? ''));
    $questionType = trim((string) ($input['question_type'] ?? ''));

    if ($questionId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Question ID is required.']);
        exit;
    }

    $updates = [];
    $params = [':id' => $questionId];

    if ($questionText !== '') {
        $updates[] = 'question_text = :text';
        $params[':text'] = $questionText;
    }

    $allowedTypes = ['single_choice', 'multiple_choice', 'true_false'];
    if ($questionType !== '' && in_array($questionType, $allowedTypes)) {
        $updates[] = 'question_type = :type';
        $params[':type'] = $questionType;
    }

    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'Nothing to update.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE ld_quiz_question SET " . implode(', ', $updates) . " WHERE id = :id");
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Question updated.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
