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

    $reportId = (int)$data['id'];
    $title = $data['title'] ?? null;
    $description = $data['description'] ?? null;
    $reportType = $data['report_type'] ?? 'performance';
    $status = $data['status'] ?? 'draft';
    $generatedAt = $data['generated_at'] ?? null;

    if (!$title) {
        http_response_code(400);
        die(json_encode(['error' => 'Report title is required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_report 
        SET title = ?, description = ?, report_type = ?, status = ?, 
            generated_at = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $description,
        $reportType,
        $status,
        $generatedAt,
        $reportId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $reportId,
        'message' => 'Report updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
