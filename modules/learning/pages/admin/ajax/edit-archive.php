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

    $archiveId = (int)$data['id'];
    $contentType = $data['content_type'] ?? null;
    $contentId = $data['content_id'] ?? null;
    $status = $data['status'] ?? 'archived';
    $reason = $data['reason'] ?? null;

    if (!$contentType || !$contentId) {
        http_response_code(400);
        die(json_encode(['error' => 'Content type and content ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_archive 
        SET status = ?, reason = ?, updated_at = NOW()
        WHERE id = ? AND content_type = ? AND content_id = ?
    ");

    $stmt->execute([
        $status,
        $reason,
        $archiveId,
        $contentType,
        $contentId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $archiveId,
        'message' => 'Archive record updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
