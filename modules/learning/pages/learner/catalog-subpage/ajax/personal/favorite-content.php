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
     * Remove by learner + item reference.
     *
     * This is safer than Bookmark::remove(id), because the class
     * method does not verify ownership of the bookmark ID.
     */
    $result = $bookmark->removeByReference(
        $learnerId,
        $itemType,
        $referenceId
    );

    echo json_encode([
        'success' => true,
        'item_type' => $itemType,
        'reference_id' => $referenceId,
        'bookmarked' => false,
        'message' => 'Bookmark removed successfully.'
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove bookmark.'
    ]);
}