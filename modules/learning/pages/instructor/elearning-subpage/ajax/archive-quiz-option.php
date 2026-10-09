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
    $optionId = isset($input['id']) ? (int) $input['id'] : 0;

    if ($optionId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Option ID is required.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM ld_quiz_question_option WHERE id = :id");
    $stmt->execute([':id' => $optionId]);

    echo json_encode(['success' => true, 'message' => 'Option deleted.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
