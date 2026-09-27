<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$documentId = isset($input['document_id']) ? (int) $input['document_id'] : 0;
$action = isset($input['action']) ? trim((string) $input['action']) : '';

if ($documentId <= 0 || $action === '') {
    error_log('verify-api invalid input: ' . json_encode($input));
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($action === 'verify') {
        $userId = (int) ($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? 0);
        $uid = $userId > 0 ? $userId : null;

        $db->prepare("
            UPDATE lc_document_verification
            SET verification_status = 'Verified',
                verified_by = :uid,
                verified_at = NOW(),
                updated_at = NOW()
            WHERE document_id = :id
              AND verification_status IN ('Pending', 'Rejected')
        ")->execute([':uid' => $uid, ':id' => $documentId]);

        if ((int)$db->query("SELECT COUNT(*) FROM lc_document_verification WHERE document_id = " . (int)$documentId)->fetchColumn() === 0) {
            $db->prepare("
                INSERT INTO lc_document_verification (document_id, verification_status, verified_by, verified_at, verification_notes)
                VALUES (:id, 'Verified', :uid, NOW(), NULL)
            ")->execute([':uid' => $uid, ':id' => $documentId]);
        }

        $stmt = $db->query("SELECT verification_status FROM lc_document_verification WHERE document_id = " . (int)$documentId . " LIMIT 1");
        $newStatus = $stmt && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) ? ($row['verification_status'] ?? 'Pending') : 'Pending';

        if ($newStatus === 'Verified') {
            echo json_encode(['success' => true, 'message' => 'Document verified successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Document not found or already verified.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
