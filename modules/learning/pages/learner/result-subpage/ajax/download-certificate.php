<?php

session_start();

require_once dirname(__FILE__, 5) . '/classes/certificate.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $certificateId = (int) ($_GET['certificate_id'] ?? 0);

    if ($certificateId <= 0) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Certificate ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Ownership is checked directly against the certificate row rather than
    // trusting anything else supplied by the client.
    $stmt = $pdo->prepare(
        'SELECT id, learner_id, course_id, file_path, status
         FROM ld_certificate
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $certificateId]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Certificate not found.']);
        exit;
    }

    if ((int) $certificate['learner_id'] !== $learnerId) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'This certificate does not belong to you.']);
        exit;
    }

    if (($certificate['status'] ?? '') !== 'active') {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Certificate is unavailable.']);
        exit;
    }

    if (empty($certificate['file_path'])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'No certificate file has been generated yet.']);
        exit;
    }

    $absolutePath = dirname(__FILE__, 5) . '/assets/uploads/certificates/' . basename($certificate['file_path']);

    if (!file_exists($absolutePath)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Certificate file is missing from storage.']);
        exit;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="certificate-' . $certificateId . '.pdf"');
    header('Content-Length: ' . filesize($absolutePath));
    readfile($absolutePath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to download certificate.',
    ]);
}
