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
    $db = (new Database())->getConnection();

    if ($action === 'verify') {
        $userId = (int) ($_SESSION['user']['employee_id'] ?? $_SESSION['employee_id'] ?? 0);
        $uid = $userId > 0 ? $userId : null;

        $db->prepare("
            UPDATE em_documents
            SET verification_status = 'Verified',
                verified_by = :uid,
                verified_at = NOW()
            WHERE document_id = :id
              AND verification_status IN ('Pending', 'Rejected')
        ")->execute([':uid' => $uid, ':id' => $documentId]);

        $stmt = $db->prepare("SELECT verification_status FROM em_documents WHERE document_id = :id LIMIT 1");
        $stmt->execute([':id' => $documentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $newStatus = $row ? ($row['verification_status'] ?? 'Pending') : 'Pending';

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
