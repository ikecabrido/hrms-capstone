<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/Message.php';

try {
    $pdo = (new Database())->getConnection();
    $message = new Message($pdo);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $result = $message->create([
        'sender_id' => (int) $_SESSION['employee_id'],
        'recipient_id' => (int) ($input['recipient_id'] ?? 0),
        'subject' => trim((string) ($input['subject'] ?? '')),
        'body' => trim((string) ($input['body'] ?? '')),
    ]);

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
