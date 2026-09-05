<?php
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$pdo = $database->getConnection();

if (!($pdo instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Database connection could not be established.']);
    exit;
}

$pdo->query("UPDATE lc_notifications SET notification_type = 'email' WHERE email IS NOT NULL AND email != '' AND email != 'system' AND (notification_type IS NULL OR notification_type != 'in_app')");

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'unread-count') {
            $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM lc_notifications WHERE is_read = 0 AND (notification_type IS NULL OR notification_type != 'email')");
            $count = (int)$stmt->fetchColumn();

            echo json_encode(['success' => true, 'unread_count' => $count]);
            exit;
        }

        if ($action === 'sent') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
            $limit = max(1, min($limit, 500));
            $stmt = $pdo->prepare('SELECT id, title, message, type, module, is_read, created_at, email, sender_email, employee_id, notification_type FROM lc_notifications WHERE notification_type = "email" ORDER BY created_at DESC LIMIT :limit');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $notifications]);
            exit;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
        $limit = max(1, min($limit, 500));
        $stmt = $pdo->prepare('SELECT id, title, message, type, module, is_read, created_at, email, sender_email, employee_id FROM lc_notifications WHERE (notification_type IS NULL OR notification_type != "email") ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $notifications]);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_POST['action'] ?? '';

        if ($action === 'mark-read') {
            $id = (int)($_POST['id'] ?? $input['id'] ?? 0);

            $stmt = $pdo->prepare('UPDATE lc_notifications SET is_read = 1, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'mark-all-read') {
            $stmt = $pdo->prepare('UPDATE lc_notifications SET is_read = 1, updated_at = NOW() WHERE is_read = 0 AND (notification_type IS NULL OR notification_type != "email")');
            $stmt->execute();

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
