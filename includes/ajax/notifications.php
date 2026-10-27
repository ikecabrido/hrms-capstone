<?php
/**
 * Notification actions for the shared sidebar's bell menu.
 *
 * POST action=mark-all-read        mark every unread notification read
 * POST action=mark-read&id=<id>    mark one notification read
 *
 * One endpoint for all twelve modules, because the bell itself is shared. Every request is
 * scoped to the session's employee, so a caller can only ever touch their own rows.
 * Responses always carry the authoritative unread count so the client's badge cannot drift
 * from the database.
 */
header('Content-Type: application/json; charset=utf-8');

// Responds with JSON 401 for POSTs and redirects to the login page otherwise.
require_once dirname(__DIR__, 2) . '/auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$userId = (int) ($_SESSION['employee_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($action === 'mark-read' && (int) ($_POST['id'] ?? 0) <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A notification id is required.']);
    exit;
}

if (!in_array($action, ['mark-read', 'mark-all-read'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

require_once dirname(__DIR__) . '/Notification.php';

try {
    $notifications = new Notification();

    $updated = $action === 'mark-all-read'
        ? $notifications->markAllAsRead($userId)
        : $notifications->markAsRead($userId, (int) $_POST['id']);

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'unread'  => $notifications->getUnreadCount($userId),
    ]);
} catch (Throwable $e) {
    error_log('Notifications endpoint failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Notifications are unavailable right now.']);
}
