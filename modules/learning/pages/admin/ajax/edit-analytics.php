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

    $analyticsId = (int)$data['id'];
    $metricType = $data['metric_type'] ?? null;
    $metricValue = $data['metric_value'] ?? null;
    $description = $data['description'] ?? null;
    $dateRecorded = $data['date_recorded'] ?? null;

    if (!$metricType) {
        http_response_code(400);
        die(json_encode(['error' => 'Metric type is required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_analytics 
        SET metric_value = ?, description = ?, date_recorded = ?, updated_at = NOW()
        WHERE id = ? AND metric_type = ?
    ");

    $stmt->execute([
        $metricValue,
        $description,
        $dateRecorded,
        $analyticsId,
        $metricType
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $analyticsId,
        'message' => 'Analytics updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
