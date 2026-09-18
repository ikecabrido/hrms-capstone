<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/../../../database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['certificate_id'])) {
        echo json_encode(['success' => false, 'error' => 'Certificate ID is required.']);
        exit;
    }

    $certId = (int) $input['certificate_id'];

    // Check certificate exists and is active
    $stmt = $pdo->prepare("SELECT id, status FROM ld_certificate WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $certId]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cert) {
        echo json_encode(['success' => false, 'error' => 'Certificate not found.']);
        exit;
    }

    if ($cert['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Certificate is already revoked.']);
        exit;
    }

    // Revoke (archive) the certificate
    $stmt = $pdo->prepare("UPDATE ld_certificate SET status = 'archived' WHERE id = :id");
    $stmt->execute([':id' => $certId]);

    echo json_encode(['success' => true, 'message' => 'Certificate revoked successfully.']);
} catch (Throwable $e) {
    error_log('revoke-certificate.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
