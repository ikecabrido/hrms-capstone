<?php

header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $learnerId = (int) $_SESSION['employee_id'];

    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("
        UPDATE ld_notification
        SET is_read = 1
        WHERE user_id = :user_id
          AND is_read = 0
    ");

    $stmt->execute([
        ':user_id' => $learnerId
    ]);

    echo json_encode([
        'success' => true,
        'updated' => $stmt->rowCount(),
        'message' => 'All notifications marked as read.'
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark notifications as read.'
    ]);
}