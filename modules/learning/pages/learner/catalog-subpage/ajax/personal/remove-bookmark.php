<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/bookmark.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id'])
        ? (int) $_SESSION['employee_id']
        : 0;

    if ($learnerId <= 0) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);

        exit;
    }

    $itemType = trim(
        (string) ($_POST['item_type'] ?? 'course')
    );

    $referenceId = (int) (
        $_POST['reference_id']
        ?? $_POST['id']
        ?? 0
    );

    $allowedTypes = [
        'course',
        'module',
        'lesson',
        'quiz',
        'program',
        'skill',
        'learning_path',
        'evaluation'
    ];

    if (!in_array($itemType, $allowedTypes, true)) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid item type.'
        ]);

        exit;
    }

    if ($referenceId <= 0) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Reference ID is required.'
        ]);

        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $bookmark = new Bookmark($pdo);

    /*
     * Do not trust learner_id from the request.
     */
    $result = $bookmark->add([
        'learner_id' => $learnerId,
        'item_type' => $itemType,
        'reference_id' => $referenceId
    ]);

    if (!empty($result['success'])) {
        echo json_encode([
            'success' => true,
            'id' => $result['id'] ?? null,
            'item_type' => $itemType,
            'reference_id' => $referenceId,
            'bookmarked' => true,
            'message' => $result['message'] ?? 'Bookmark added successfully.'
        ]);

        exit;
    }

    http_response_code(422);

    echo json_encode($result);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to add bookmark.'
    ]);
}