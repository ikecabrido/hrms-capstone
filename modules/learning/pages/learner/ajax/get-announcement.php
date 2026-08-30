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
    $database = new Database();
    $pdo = $database->getConnection();

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $limit = max(1, min($limit, 50));

    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            message,
            audience,
            posted_by,
            status,
            created_at,
            expires_at
        FROM ld_announcement
        WHERE status = 'active'
          AND (audience = 'learner' OR audience = 'all')
          AND (expires_at IS NULL OR expires_at >= NOW())
        ORDER BY created_at DESC
        LIMIT :limit
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'announcements' => $announcements,
        'count' => count($announcements)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load announcements.'
    ]);
}