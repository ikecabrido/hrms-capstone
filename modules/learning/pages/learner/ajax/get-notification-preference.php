<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/Setting.php';

try {
    $pdo = (new Database())->getConnection();
    $setting = new Setting($pdo);
    $userId = (int) $_SESSION['employee_id'];
    $prefs = $setting->getNotificationPreferences($userId);

    echo json_encode(['success' => true, 'preferences' => $prefs]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
