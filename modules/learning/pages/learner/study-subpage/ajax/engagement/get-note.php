<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/note.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $itemType = isset($_GET['item_type']) ? trim((string) $_GET['item_type']) : null;
    $referenceId = isset($_GET['reference_id']) ? (int) $_GET['reference_id'] : null;

    $database = new Database();
    $pdo = $database->getConnection();
    $noteClass = new Note($pdo);

    if ($itemType !== null && $referenceId !== null && $referenceId > 0) {
        // Scoped to one specific item (e.g. notes for this lesson only).
        $note = $noteClass->getByLearnerAndItem($learnerId, $itemType, $referenceId);
        $items = $note ? [$note] : [];
    } else {
        // All of the learner's notes.
        $items = $noteClass->getByLearner($learnerId);
    }

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'item_type' => $item['item_type'],
                'reference_id' => (int) $item['reference_id'],
                'note' => $item['note'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load notes.',
        'error' => $e->getMessage(),
    ]);
}
