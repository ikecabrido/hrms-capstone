<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

if (!($db instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$complaintId = isset($_REQUEST['complaint_id']) ? (int) $_REQUEST['complaint_id'] : 0;
$action      = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : '';
$userId      = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;

if ($complaintId <= 0 || $action === '') {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $errUrl = '?page=complaint-workflow&id=' . (int)$complaintId . '&msg=error|' . rawurlencode('Invalid request. Missing required parameters.');
        http_response_code(400);
        header('Content-Type: text/html');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invalid Request</title></head><body>';
        echo '<script>window.location.href=' . json_encode($errUrl) . ';</script>';
        echo '<p>Invalid request. Redirecting…</p>';
        echo '</body></html>';
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$complaintTable = 'lc_complaints';
try {
    $db->query("SELECT 1 FROM $complaintTable LIMIT 1");
} catch (Throwable $e) {
    $complaintTable = 'lc_complaints';
}

$HUMAN_LABELS = [
    'closed_no_violation'          => 'Dismissed – No Violation',
    'closed_warning_issued'        => 'Written Warning Issued',
    'closed_suspension'            => 'Suspension Issued',
    'closed_termination_recommended' => 'Final Decision – Termination Recommended',
    'closed_resolved'              => 'Resolved',
    'closed'                       => 'Closed',
    'under_investigation'          => 'Under Investigation',
    'under_initial_review'         => 'Initial Review',
    'pending_employee_response'    => 'Awaiting Employee Response',
    'for_decision'                 => 'For Decision',
];

function cw_decision_label(string $status, array $map): string {
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

try {
    $stmt = $db->prepare("SELECT id, status FROM `$complaintTable` WHERE id = :id");
    $stmt->execute([':id' => $complaintId]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
        exit;
    }

    $currentStatus = $complaint['status'];

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

    $newStatus    = null;
    $decisionLabel = '';
    $notes         = '';

    if ($action === 'advance') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        $nextMap = [
            'under_initial_review'       => 'under_investigation',
            'under_investigation'        => 'pending_employee_response',
            'pending_employee_response'  => 'for_decision',
            'for_decision'               => 'closed_no_violation',
            'closed_no_violation'        => 'closed',
            'closed_warning_issued'      => 'closed',
            'closed_suspension'          => 'closed',
            'closed_termination_recommended' => 'closed',
            'closed_resolved'            => 'closed',
            'closed'                     => 'closed',
        ];
        if (in_array($nextMap[$currentStatus] ?? null, $allowed, true)) {
            $newStatus = $nextMap[$currentStatus];
            $decisionLabel = cw_decision_label($newStatus, $HUMAN_LABELS);
            $notes = 'Advanced from: ' . cw_decision_label($currentStatus, $HUMAN_LABELS);
        }
    } elseif ($action === 'reopen') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        $reopenMap = [
            'under_investigation'        => 'under_initial_review',
            'pending_employee_response'  => 'under_investigation',
            'for_decision'               => 'under_investigation',
            'closed_no_violation'        => 'under_investigation',
            'closed_warning_issued'      => 'under_investigation',
            'closed_suspension'          => 'under_investigation',
            'closed_termination_recommended' => 'under_investigation',
            'closed_resolved'            => 'under_investigation',
            'closed'                     => 'under_investigation',
        ];
        if (in_array($reopenMap[$currentStatus] ?? null, $allowed, true)) {
            $newStatus = $reopenMap[$currentStatus];
            $decisionLabel = 'Case Reopened';
            $notes = 'Reopened from: ' . cw_decision_label($currentStatus, $HUMAN_LABELS);
        }
    } elseif ($action === 'record_decision' || $action === 'finalize_decision') {
        $targetStatus = isset($_REQUEST['target_status']) ? trim((string) $_REQUEST['target_status']) : '';
        $allowed = $validTransitions[$currentStatus] ?? [];
        if ($targetStatus === '' || !in_array($targetStatus, $allowed, true)) {
            $msg = 'Invalid decision for the current status.';
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $errUrl = '?page=complaint-workflow&id=' . (int)$complaintId . '&msg=error|' . rawurlencode($msg);
                http_response_code(400);
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invalid Decision</title></head><body>';
                echo '<script>window.location.href=' . json_encode($errUrl) . ';</script>';
                echo '<p>' . htmlspecialchars($msg) . ' Redirecting…</p>';
                echo '</body></html>';
                exit;
            }
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        $newStatus    = $targetStatus;
        $decisionLabel = cw_decision_label($newStatus, $HUMAN_LABELS);
        $notes         = 'Decision finalized: ' . cw_decision_label($currentStatus, $HUMAN_LABELS) . ' → ' . $decisionLabel;
    } elseif ($action === 'close') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        if (in_array('closed', $allowed, true)) {
            $newStatus = 'closed';
            $decisionLabel = cw_decision_label('closed', $HUMAN_LABELS);
            $notes = 'Closed from: ' . cw_decision_label($currentStatus, $HUMAN_LABELS);
        }
    } elseif ($action === 'record_response') {
        $responseText = isset($_POST['employee_response']) ? trim((string) $_POST['employee_response']) : '';
        if ($responseText === '') {
            echo json_encode(['success' => false, 'message' => 'Employee response text is required.']);
            exit;
        }
        $db->beginTransaction();
        try {
            $update = $db->prepare("UPDATE `$complaintTable` SET employee_response = :response, employee_response_date = NOW(), updated_at = NOW() WHERE id = :id");
            $update->execute([':response' => $responseText, ':id' => $complaintId]);
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Employee response recorded successfully.',
                'complaint_id' => (int) $complaint['id'],
            ]);
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'assign_investigator') {
        $employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
            exit;
        }
        $empCheck = $db->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM em_employees WHERE employee_id = :id LIMIT 1");
        $empCheck->execute([':id' => $employeeId]);
        $employee = $empCheck->fetch(PDO::FETCH_ASSOC);
        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }
        $db->beginTransaction();
        try {
            $update = $db->prepare("UPDATE `$complaintTable` SET `assigned_to` = :owner_id, `assigned_name` = :assigned_name, `updated_at` = NOW() WHERE `id` = :id");
            $update->execute([':owner_id' => $employeeId, ':assigned_name' => $employee['full_name'], ':id' => $complaintId]);
            $db->commit();
            echo json_encode([
                'success'       => true,
                'message'       => 'Investigator assigned successfully.',
                'employee_name' => $employee['full_name'],
                'complaint_id'  => (int) $complaintId,
            ]);
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'clear_investigator') {
        $db->beginTransaction();
        try {
            $update = $db->prepare("UPDATE `$complaintTable` SET `assigned_to` = NULL, `assigned_name` = NULL, `updated_at` = NOW() WHERE `id` = :id");
            $update->execute([':id' => $complaintId]);
            $db->commit();
            echo json_encode([
                'success'      => true,
                'message'      => 'Investigator cleared.',
                'complaint_id' => (int) $complaintId,
            ]);
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    }

    if (!$newStatus) {
        echo json_encode(['success' => false, 'message' => 'This action is not allowed for the current status.']);
        exit;
    }

    $db->beginTransaction();

    $update = $db->prepare("UPDATE `$complaintTable` SET status = :status, updated_at = NOW() WHERE id = :id");
    $update->execute([':status' => $newStatus, ':id' => $complaintId]);

    $historyInsert = $db->prepare(
        "INSERT INTO `lc_complaint_decision_history`
            (complaint_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
         VALUES
            (:complaint_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
    );
    $historyInsert->execute([
        ':complaint_id'   => $complaintId,
        ':action'         => $action,
        ':old_status'     => $currentStatus,
        ':new_status'     => $newStatus,
        ':decision_label' => $decisionLabel,
        ':performed_by'   => $userId,
        ':notes'          => $notes,
    ]);

    $db->commit();

    $isGetRequest = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET');

    if ($action === 'finalize_decision' && $isGetRequest) {
        $returnUrl = '?page=complaint-workflow&id=' . (int)$complaintId . '&msg=success|Decision finalized: ' . rawurlencode(str_replace('_', ' ', $newStatus));
        http_response_code(200);
        header('Content-Type: text/html');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Decision Finalized</title></head><body>';
        echo '<script>window.location.href=' . json_encode($returnUrl) . ';</script>';
        echo '<p>Decision finalized successfully. Redirecting…</p>';
        echo '</body></html>';
        exit;
    }

    echo json_encode([
        'success'      => true,
        'message'      => $decisionLabel ? 'Status updated: ' . $decisionLabel : 'Status updated successfully.',
        'new_status'   => $newStatus,
        'complaint_id' => (int) $complaint['id'],
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

