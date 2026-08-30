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

    $moderationId = (int)$data['id'];
    $contentType = $data['content_type'] ?? null;
    $contentId = $data['content_id'] ?? null;
    $status = $data['status'] ?? 'pending';
    $reason = $data['reason'] ?? null;
    $resolution = $data['resolution'] ?? null;
    $moderatorNotes = $data['moderator_notes'] ?? null;

    if (!$contentType || !$contentId) {
        http_response_code(400);
        die(json_encode(['error' => 'Content type and content ID are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_moderation_queue 
        SET status = ?, reason = ?, resolution = ?, moderator_notes = ?, 
            reviewed_at = NOW()
        WHERE id = ? AND content_type = ? AND content_id = ?
    ");

    $stmt->execute([
        $status,
        $reason,
        $resolution,
        $moderatorNotes,
        $moderationId,
        $contentType,
        $contentId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $moderationId,
        'message' => 'Moderation queue updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
