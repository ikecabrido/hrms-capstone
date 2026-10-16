<?php
/**
 * Shared notification-preferences reader for every Learning role.
 *
 * The store is employee-scoped (ld_notification_preference_store, keyed by the
 * HR employee id the notification writers use), so an admin, an instructor and a
 * learner each read their own switches through this one endpoint. It used to sit
 * under pages/learner/ajax/, which meant only the learner notification page could
 * reach a feature the other two roles need just as much.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/notificationpreference.php';

$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

if ($employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $preferences = NotificationPreference::get($pdo, $employeeId);

    echo json_encode(['success' => true, 'preferences' => $preferences]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
