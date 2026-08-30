<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/Message.php';

$userId = (int) $_SESSION['employee_id'];

try {
    $pdo = (new Database())->getConnection();
    $message = new Message($pdo);

    $messages = $message->getRecipientMessages($userId, 50);
    $sent = $message->getSentMessages($userId, 50);
    $unreadCount = $message->getUnreadCount($userId);

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'sent' => $sent,
        'unread_count' => $unreadCount,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
