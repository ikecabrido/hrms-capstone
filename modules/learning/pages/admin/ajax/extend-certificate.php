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
    $newDate = isset($data['valid_until']) ? trim($data['valid_until']) : '';

    if ($certId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid certificate ID']));
    }

    if (empty($newDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD']));
    }

    // Validate the date is in the future
    if (strtotime($newDate) < time()) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'New expiry date must be in the future']));
    }

    $pdo = (new Database())->getConnection();

    // Verify the certificate exists and is active
    $stmt = $pdo->prepare("SELECT id, status, valid_until FROM ld_certificate WHERE id = :id AND status = 'active' LIMIT 1");
    $stmt->execute([':id' => $certId]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cert) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'Certificate not found or already archived']));
    }

    $oldDate = $cert['valid_until'] ? date('M j, Y', strtotime($cert['valid_until'])) : 'No expiry';

    // Update the expiry date
    $updateStmt = $pdo->prepare("UPDATE ld_certificate SET valid_until = :date WHERE id = :id");
    $updateStmt->execute([':date' => $newDate, ':id' => $certId]);

    echo json_encode([
        'success' => true,
        'message' => 'Certificate extended successfully',
        'old_date' => $oldDate,
        'new_date' => date('M j, Y', strtotime($newDate))
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
