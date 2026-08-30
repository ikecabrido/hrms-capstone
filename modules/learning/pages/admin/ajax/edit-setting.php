<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include dirname(__FILE__, 6) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $settingId = (int)$data['id'];
    $settingKey = $data['setting_key'] ?? null;
    $settingValue = $data['setting_value'] ?? null;

    if (!$settingKey) {
        http_response_code(400);
        die(json_encode(['error' => 'Setting key is required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_setting 
        SET setting_value = ?, updated_at = NOW()
        WHERE id = ? AND setting_key = ?
    ");

    $stmt->execute([
        $settingValue,
        $settingId,
        $settingKey
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $settingId,
        'message' => 'Setting updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
