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

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $learnerId = (int) $_SESSION['employee_id'];

    $database = new Database();
    $pdo = $database->getConnection();

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $limit = max(1, min($limit, 100));

    $stmt = $pdo->prepare("
        SELECT
            id,
            type,
            title,
            message,
            reference_type,
            reference_id,
            is_read,
            created_at
        FROM ld_notification
        WHERE user_id = :user_id
          AND is_read = 0
        ORDER BY created_at DESC
        LIMIT :limit
    ");

    $stmt->bindValue(':user_id', $learnerId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load pending notifications.'
    ]);
}