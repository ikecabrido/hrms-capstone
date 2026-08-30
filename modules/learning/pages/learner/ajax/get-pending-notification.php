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
require_once dirname(__DIR__, 3) . '/classes/certificate-expiry-checker.php';

try {
    $learnerId = (int) $_SESSION['employee_id'];

    $database = new Database();
    $pdo = $database->getConnection();

    // Generate certificate expiry notifications on each check
    checkCertificateExpiryNotifications($pdo, $learnerId);

    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
    $limit = max(1, min($limit, 100));

    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    $offset = max(0, $offset);

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
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':user_id', $learnerId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM ld_notification
        WHERE user_id = :user_id
    ");

    $countStmt->execute([
        ':user_id' => $learnerId
    ]);

    $total = (int) $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + count($notifications)) < $total
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load notification history.'
    ]);
}