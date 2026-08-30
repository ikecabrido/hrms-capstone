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

    $noteId = (int) ($_POST['note_id'] ?? 0);

    if ($noteId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Note ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $noteClass = new Note($pdo);

    $existing = $noteClass->getById($noteId);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Note not found.']);
        exit;
    }

    if ((int) $existing['learner_id'] !== $learnerId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own notes.']);
        exit;
    }

    $result = $noteClass->delete($noteId);

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
        'message' => 'Failed to delete note.',
        'error' => $e->getMessage(),
    ]);
}
exit;
