<?php
/**
 * Shared notification-preferences writer for every Learning role.
 *
 * Any signed-in employee may change their own switches; a save only ever touches
 * the row keyed by their own employee id. Lives next to the reader so the three
 * notification pages point at one place (see
 * includes/notification-display.php::ntfPreferencesPanel()).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/CSRF.php';
require_once dirname(__FILE__, 4) . '/classes/notificationpreference.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

CSRF::requireValid();

try {
    $employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($employeeId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $pdo = (new Database())->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $notificationType = trim((string) ($input['notification_type'] ?? ''));
    $enabled = !empty($input['enabled']);

    if ($notificationType === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'notification_type is required']);
        exit;
    }

    $result = NotificationPreference::set($pdo, $employeeId, $notificationType, $enabled);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
