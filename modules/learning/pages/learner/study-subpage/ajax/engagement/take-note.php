<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/note.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $itemType = trim((string) ($_POST['item_type'] ?? ''));
    $referenceId = (int) ($_POST['reference_id'] ?? 0);
    $note = trim((string) ($_POST['note'] ?? ''));

    $database = new Database();
    $pdo = $database->getConnection();
    $noteClass = new Note($pdo);

    $result = $noteClass->create([
        'learner_id' => $learnerId,
        'item_type' => $itemType,
        'reference_id' => $referenceId,
        'note' => $note,
    ]);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save note.',
        'error' => $e->getMessage(),
    ]);
}
exit;
