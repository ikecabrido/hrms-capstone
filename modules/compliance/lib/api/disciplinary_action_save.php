<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

if (!($db instanceof PDO)) {
    throw new RuntimeException('Database connection is unavailable.');
}

set_exception_handler(function (Throwable $e) {
    error_log('disciplinary_action_save.php 500 error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
    exit;
});

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!function_exists('dcsj_send_json')) {
    function dcsj_send_json($payload, $code = 200) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if ($code !== 200) { http_response_code($code); }
        echo json_encode($payload);
        exit;
    }
}

$currentUserId = (int) ($_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1);

$action = strtolower(trim((string) ($_POST['action'] ?? '')));

if ($action !== 'save_document_request') {
    dcsj_send_json(['success' => false, 'message' => 'Unknown action.'], 400);
}

$employeeId    = isset($_POST['employee_id']) ? trim((string) $_POST['employee_id']) : '';
$documentType  = isset($_POST['document_type']) ? trim((string) $_POST['document_type']) : '';
$templateCode  = isset($_POST['template_code']) ? trim((string) $_POST['template_code']) : '';
$hrSignatory = isset($_POST['hr_signatory']) ? trim((string) $_POST['hr_signatory']) : '';
$policyViolated      = isset($_POST['policy_violated']) ? trim((string) $_POST['policy_violated']) : '';
$incidentDescription = isset($_POST['incident_description']) ? trim((string) $_POST['incident_description']) : '';
$complaintId         = isset($_POST['complaint_id']) ? trim((string) $_POST['complaint_id']) : '';
$incidentDate        = isset($_POST['incident_date']) ? trim((string) $_POST['incident_date']) : '';

if ($employeeId === '' || $templateCode === '') {
    dcsj_send_json(['success' => false, 'message' => 'Missing required parameters (employee_id or template_code).'], 400);
}

$docTypeLabelMap = [
    'termination_decision' => 'Termination Decision',
    'written_warning'      => 'Written Warning',
    'suspension_notice'    => 'Suspension Notice',
    'notice_of_decision'   => 'Notice of Decision',
    'nte'                  => 'Notice to Explain (NTE)',
];
$documentTypeLabel = $_POST['document_type'] ?? ($docTypeLabelMap[$templateCode] ?? ucfirst(str_replace('_', ' ', $templateCode)));

try {
    $db->beginTransaction();

    $maxId = 0;
    $idCheck = $db->query('SELECT COALESCE(MAX(request_id), 0) AS max_id FROM lc_document_requests');
    if ($idRow = $idCheck->fetch(PDO::FETCH_ASSOC)) {
        $maxId = (int) ($idRow['max_id'] ?? 0);
    }
    $newRequestId = $maxId + 1;

    $stmt = $db->prepare("
        INSERT INTO lc_document_requests
            (request_id, employee_id, rao_hired_id, document_type, request_status, archived, signature_status, requires_signature,
             created_at, required_by, assigned_to, priority, notes, template_code)
        VALUES
            (:request_id, :employee_id, :rao_hired_id, :document_type, 'pending', 0, 'none', 1, NOW(), NULL, :assigned_to, 'High', :notes, :template_code)
    ");
    $stmt->execute([
        ':request_id'    => $newRequestId,
        ':employee_id'    => (int) $employeeId,
        ':rao_hired_id'  => null,
        ':document_type' => $documentTypeLabel,
        ':assigned_to'    => $currentUserId > 0 ? $currentUserId : null,
        ':notes'          => 'Disciplinary document (complaint_id: ' . ($complaintId !== '' ? $complaintId : 'N/A') . ').',
        ':template_code'  => $templateCode,
    ]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('disciplinary_action_save.php INSERT error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    dcsj_send_json(['success' => false, 'message' => 'Failed to save document request: ' . $e->getMessage()], 500);
}

$redirectParams = [
    'mode'             => 'reply',
    'notification_key' => 'warning',
    'to_recipient_email' => '',
    'to_recipient_name'  => '',
    'template_code'      => $templateCode,
    'scenario'         => 'general',
];

if ($employeeId !== '') {
    $redirectParams['employee_id'] = $employeeId;
    $redirectParams['document_type'] = $documentType !== '' ? $documentType : $templateCode;
}

$empStmt = $db->prepare('SELECT email, CONCAT(first_name, " ", COALESCE(middle_name, ""), " ", last_name) AS full_name FROM em_employees WHERE employee_id = :id LIMIT 1');
$empStmt->execute([':id' => (int) $employeeId]);
$empRow = $empStmt->fetch(PDO::FETCH_ASSOC);
if ($empRow) {
    $redirectParams['to_recipient_email'] = (string) ($empRow['email'] ?? '');
    $redirectParams['to_recipient_name'] = trim((string) ($empRow['full_name'] ?? ''));
}

$redirectParams['hr_signatory'] = $hrSignatory !== '' ? $hrSignatory : ($empRow ? (string) ($empRow['full_name'] ?? '') : '');

if ($templateCode === 'nte' && $policyViolated !== '') {
    $redirectParams['policy_violated'] = $policyViolated;
}
if ($templateCode === 'nte' && $incidentDescription !== '') {
    $redirectParams['incident_description'] = $incidentDescription;
}
if ($incidentDate !== '') {
    $redirectParams['incident_date'] = $incidentDate;
}
$redirectParams['template_code'] = $templateCode;

$redirect = '/hrms-capstone/modules/compliance/index.php?page=notification-compose&' . http_build_query($redirectParams);

dcsj_send_json([
    'success'    => true,
    'message'    => 'Document request saved. Redirecting to notification composer...',
    'redirect'   => $redirect,
    'request_id' => (int) $newRequestId,
]);
