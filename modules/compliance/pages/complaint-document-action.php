<?php

require_once __DIR__ . '/../../../database/db.php';

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;
$targetStatus = isset($_GET['target_status']) ? trim($_GET['target_status']) : '';
$documentType = isset($_GET['document_type']) ? trim($_GET['document_type']) : '';
$template = isset($_GET['template']) ? trim($_GET['template']) : '';
$templateCode = isset($_GET['template_code']) ? trim($_GET['template_code']) : '';
$hrSignatory = isset($_GET['hr_signatory']) ? trim($_GET['hr_signatory']) : '';
$employeeId = isset($_GET['employee_id']) ? trim($_GET['employee_id']) : '';

if ($complaintId <= 0 || $targetStatus === '' || $documentType === '' || $template === '' || $templateCode === '' || $employeeId === '') {
    header('Location: ?page=complaint-workflow&id=' . $complaintId . '&msg=error|Invalid parameters');
    exit;
}

$db = (new Database())->getConnection();

if (!$db instanceof PDO) {
    header('Location: ?page=complaint-workflow&id=' . $complaintId . '&msg=error|Database connection failed');
    exit;
}

$currentStatus = '';
$stmt = $db->prepare("SELECT status FROM lc_complaints WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $complaintId]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
$currentStatus = $current['status'] ?? '';

$validTransitions = [
    'under_initial_review'        => ['under_investigation', 'closed', 'for_decision'],
    'under_investigation'         => ['pending_employee_response', 'closed', 'under_initial_review', 'for_decision', 'closed_no_violation', 'closed_warning_issued', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved'],
    'pending_employee_response'   => ['for_decision', 'under_investigation', 'closed'],
    'for_decision'                => ['closed_no_violation', 'closed_warning_issued', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved', 'closed', 'under_investigation'],
    'closed_no_violation'         => ['under_investigation', 'under_initial_review'],
    'closed_warning_issued'       => ['under_investigation', 'under_initial_review'],
    'closed_suspension'           => ['under_investigation', 'under_initial_review'],
    'closed_termination_recommended' => ['under_investigation', 'under_initial_review'],
    'closed_resolved'             => ['under_investigation', 'under_initial_review'],
    'closed'                      => ['under_investigation', 'under_initial_review', 'for_decision'],
];

if (!in_array($targetStatus, $validTransitions[$currentStatus] ?? [], true)) {
    header('Location: ?page=complaint-workflow&id=' . $complaintId . '&msg=error|Invalid decision for current status');
    exit;
}

$HUMAN_LABELS = [
    'closed_no_violation'          => 'Dismissed - No Violation',
    'closed_warning_issued'        => 'Written Warning Issued',
    'closed_suspension'            => 'Suspension Issued',
    'closed_termination_recommended' => 'Final Decision - Termination Recommended',
    'closed_resolved'              => 'Resolved',
    'closed'                       => 'Closed',
];

$decisionLabel = $HUMAN_LABELS[$targetStatus] ?? ucfirst(str_replace('_', ' ', $targetStatus));
$userId = $_SESSION['employee_id'] ?? 1;

$db->beginTransaction();
try {
    $update = $db->prepare("UPDATE lc_complaints SET status = :status, updated_at = NOW() WHERE id = :id");
    $update->execute([':status' => $targetStatus, ':id' => $complaintId]);
    
    $historyInsert = $db->prepare(
        "INSERT INTO lc_complaint_decision_history (complaint_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
         VALUES (:complaint_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
    );
    $historyInsert->execute([
        ':complaint_id'   => $complaintId,
        ':action'         => 'finalize_decision',
        ':old_status'     => $currentStatus,
        ':new_status'     => $targetStatus,
        ':decision_label' => $decisionLabel,
        ':performed_by'   => $userId,
        ':notes'          => 'Decision finalized: ' . ucfirst(str_replace('_', ' ', $currentStatus)) . ' -> ' . $decisionLabel,
    ]);
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    header('Location: ?page=complaint-workflow&id=' . $complaintId . '&msg=error|' . rawurlencode($e->getMessage()));
    exit;
}

$generateUrl = '?page=generate-document&employee_id=' . urlencode($employeeId) . '&document_type=' . urlencode($documentType) . '&template=' . urlencode($template) . '&template_code=' . urlencode($templateCode) . '&hr_signatory=' . rawurlencode($hrSignatory) . '&generate=1';
header('Location: ' . $generateUrl);
exit;
