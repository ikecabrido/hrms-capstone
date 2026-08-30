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
require_once dirname(__DIR__, 3) . '/classes/Setting.php';

try {
    $pdo = (new Database())->getConnection();
    $setting = new Setting($pdo);

    $saved = 0;
    $errors = [];

    // Save all POST values as settings
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        $result = $setting->set($key, trim((string) $value));
        if ($result['success']) {
            $saved++;
        } else {
            $errors[] = $result['message'];
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'error' => implode('; ', $errors)]);
    } else {
        echo json_encode(['success' => true, 'message' => "$saved setting(s) saved successfully."]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
