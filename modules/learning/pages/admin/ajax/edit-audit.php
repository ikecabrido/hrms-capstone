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

    $auditId = (int)$data['id'];
    $status = $data['status'] ?? 'pending';
    $notes = $data['notes'] ?? null;
    $resolution = $data['resolution'] ?? null;

    $stmt = $pdo->prepare("
        UPDATE ld_audit_log 
        SET status = ?, notes = ?, resolution = ?, resolved_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $status,
        $notes,
        $resolution,
        $auditId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $auditId,
        'message' => 'Audit log updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
