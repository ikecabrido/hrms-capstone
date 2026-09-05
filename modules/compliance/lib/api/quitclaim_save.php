<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
if ($requestId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

$formData = [];
$allowedFields = [
    'employee_name', 'employee_number', 'department', 'position', 'date_issued',
    'settlement_amount', 'employee_signature', 'employee_id_number',
    'witness_name_1', 'witness_name_2', 'witness_id_1', 'witness_id_2'
];

foreach ($allowedFields as $field) {
    $formData[$field] = isset($_POST[$field]) ? trim((string) $_POST[$field]) : null;
}

$formData['saved_at'] = date('Y-m-d H:i:s');
$formData['saved_by'] = $_SESSION['employee_id'] ?? null;

try {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        UPDATE lc_document_requests 
        SET document_form_data = :form_data,
            updated_at = NOW()
        WHERE request_id = :request_id
    ");
    $stmt->execute([
        ':form_data' => json_encode($formData),
        ':request_id' => $requestId,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Quitclaim form saved successfully.',
        'data' => $formData
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
