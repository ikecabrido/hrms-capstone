<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $certId = isset($data['certificate_id']) ? (int)$data['certificate_id'] : 0;

    if ($certId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid certificate ID']));
    }

    $pdo = (new Database())->getConnection();

    // Verify the certificate exists and is active
    $stmt = $pdo->prepare("SELECT id, status, learner_id, course_id FROM ld_certificate WHERE id = :id AND status = 'active' LIMIT 1");
    $stmt->execute([':id' => $certId]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cert) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Certificate not found or already archived']));
    }

    // Archive (revoke) the certificate
    $updateStmt = $pdo->prepare("UPDATE ld_certificate SET status = 'archived' WHERE id = :id");
    $updateStmt->execute([':id' => $certId]);

    echo json_encode([
        'success' => true,
        'message' => 'Certificate revoked successfully'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
