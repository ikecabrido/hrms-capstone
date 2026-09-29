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

try {
    $missing = [];
    $columns = $db->query("SHOW COLUMNS FROM `$complaintTable`")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['termination_reply', 'termination_review_status', 'termination_recommended_at', 'termination_loi_pdf_path', 'termination_loi_submitted_at'] as $col) {
        if (!in_array($col, $columns, true)) {
            $missing[] = $col;
        }
    }
    if ($missing) {
        $db->beginTransaction();
        if (in_array('termination_reply', $missing, true)) {
            $db->exec("ALTER TABLE `$complaintTable` ADD COLUMN termination_reply TEXT DEFAULT NULL");
        }
        if (in_array('termination_review_status', $missing, true)) {
            $db->exec("ALTER TABLE `$complaintTable` ADD COLUMN termination_review_status ENUM('pending','rejected','considered') DEFAULT 'pending'");
        }
        if (in_array('termination_recommended_at', $missing, true)) {
            $db->exec("ALTER TABLE `$complaintTable` ADD COLUMN termination_recommended_at DATETIME DEFAULT NULL");
        }
        if (in_array('termination_loi_pdf_path', $missing, true)) {
            $db->exec("ALTER TABLE `$complaintTable` ADD COLUMN termination_loi_pdf_path VARCHAR(255) DEFAULT NULL AFTER termination_recommended_at");
        }
        if (in_array('termination_loi_submitted_at', $missing, true)) {
            $db->exec("ALTER TABLE `$complaintTable` ADD COLUMN termination_loi_submitted_at DATETIME DEFAULT NULL AFTER termination_loi_pdf_path");
        }
        $db->commit();
    }
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

$HUMAN_LABELS = [
    'closed_no_violation'             => 'Dismissed – No Violation',
    'closed_warning_issued'           => 'Written Warning Issued',
    'closed_second_written_warning'   => 'Second Written Warning Issued',
    'closed_final_written_warning'    => 'Final Written Warning Issued',
    'closed_suspension'               => 'Suspension Issued',
    'closed_termination_recommended'  => 'Final Decision – Termination Recommended',
    'closed_resolved'                 => 'Resolved',
    'closed'                          => 'Closed',
    'under_investigation'             => 'Under Investigation',
    'under_initial_review'            => 'Initial Review',
    'nte_issued'                      => 'NTE Issued',
    'pending_employee_response'       => 'Awaiting Employee Response',
    'for_decision'                    => 'For Decision',
    'termination_employee_reply'      => 'Awaiting Termination Employee Response',
    'termination_reviewed'            => 'Termination Reviewed',
];

function cw_decision_label(string $status, array $map): string {
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

try {
    $stmt = $db->prepare("SELECT id, status, respondent_employee_id FROM `$complaintTable` WHERE id = :id");
    $stmt->execute([':id' => $complaintId]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
        exit;
    }

    $currentStatus = $complaint['status'];

    $validTransitions = [
        'under_initial_review'              => ['under_investigation', 'closed', 'for_decision'],
        'under_investigation'               => ['nte_issued', 'pending_employee_response', 'closed', 'under_initial_review', 'for_decision', 'closed_no_violation', 'closed_warning_issued', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved'],
        'nte_issued'                        => ['pending_employee_response', 'closed', 'under_investigation', 'for_decision'],
        'pending_employee_response'         => ['for_decision', 'under_investigation', 'closed'],
        'for_decision'                      => ['closed_no_violation', 'closed_warning_issued', 'closed_second_written_warning', 'closed_final_written_warning', 'closed_suspension', 'closed_termination_recommended', 'closed_resolved', 'closed', 'under_investigation'],
        'closed_no_violation'               => ['under_investigation', 'under_initial_review'],
        'closed_warning_issued'             => ['under_investigation', 'under_initial_review'],
        'closed_second_written_warning'     => ['under_investigation', 'under_initial_review'],
        'closed_final_written_warning'      => ['under_investigation', 'under_initial_review'],
        'closed_suspension'                 => ['under_investigation', 'under_initial_review'],
        'closed_termination_recommended'    => ['under_investigation', 'under_initial_review', 'termination_employee_reply'],
        'termination_employee_reply'        => ['termination_reviewed', 'under_investigation', 'under_initial_review', 'for_decision'],
        'termination_reviewed'              => ['for_decision', 'under_investigation', 'under_initial_review', 'closed'],
        'closed_resolved'                   => ['under_investigation', 'under_initial_review'],
        'closed'                            => ['under_investigation', 'under_initial_review', 'for_decision'],
    ];

    $newStatus    = null;
    $decisionLabel = '';
    $notes         = '';

    if ($action === 'advance') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        $nextMap = [
            'under_initial_review'          => 'under_investigation',
            'under_investigation'           => 'nte_issued',
            'nte_issued'                    => 'pending_employee_response',
            'pending_employee_response'     => 'for_decision',
            'for_decision'                  => 'closed_no_violation',
            'closed_no_violation'           => 'closed',
            'closed_warning_issued'         => 'closed',
            'closed_second_written_warning' => 'closed',
            'closed_final_written_warning'  => 'closed',
            'closed_suspension'             => 'closed',
            'closed_termination_recommended'=> 'termination_employee_reply',
            'termination_employee_reply'    => 'termination_reviewed',
            'closed_resolved'               => 'closed',
            'closed'                        => 'closed',
        ];
        if (in_array($nextMap[$currentStatus] ?? null, $allowed, true)) {
            $newStatus = $nextMap[$currentStatus];
            $decisionLabel = cw_decision_label($newStatus, $HUMAN_LABELS);
            $notes = 'Advanced from: ' . cw_decision_label($currentStatus, $HUMAN_LABELS);
        }
    } elseif ($action === 'reopen') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        $reopenMap = [
            'under_investigation'           => 'under_initial_review',
            'nte_issued'                    => 'under_investigation',
            'pending_employee_response'     => 'under_investigation',
            'for_decision'                  => 'under_investigation',
            'closed_no_violation'           => 'under_investigation',
            'closed_warning_issued'         => 'under_investigation',
            'closed_second_written_warning' => 'under_investigation',
            'closed_final_written_warning'  => 'under_investigation',
            'closed_suspension'             => 'under_investigation',
            'closed_termination_recommended'=> 'under_investigation',
            'termination_employee_reply'    => 'for_decision',
            'termination_reviewed'          => 'for_decision',
            'closed_resolved'               => 'under_investigation',
            'closed'                        => 'under_investigation',
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
    } elseif ($action === 'submit_termination_loi') {
        $loiNotes = isset($_POST['loi_notes']) ? trim((string) $_POST['loi_notes']) : '';
        $loiFrom = isset($_POST['loi_from']) ? trim((string) $_POST['loi_from']) : '';
        if ($loiNotes === '') {
            echo json_encode(['success' => false, 'message' => 'Letter of Intent content is required.']);
            exit;
        }
        $allowedStatuses = ['closed_termination_recommended', 'termination_employee_reply', 'termination_reviewed'];
        if (!in_array($currentStatus, $allowedStatuses, true)) {
            echo json_encode(['success' => false, 'message' => 'Letter of Intent cannot be recorded for the current status.']);
            exit;
        }

        $loiFilePath = null;
        if (!empty($_FILES['loi_file']['name'])) {
            $uploadDir = __DIR__ . '/../../../assets/termination_loi/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
                exit;
            }
            $fileName = 'LOI-CMP-' . str_pad($complaintId, 5, '0', STR_PAD_LEFT) . '-' . date('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['loi_file']['name']));
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['loi_file']['tmp_name'], $targetPath)) {
                $loiFilePath = '/modules/compliance/assets/termination_loi/' . $fileName;
            }
        }

        $db->beginTransaction();
        try {
            $updateColumns = ['termination_reply = :reply', 'termination_loi_submitted_at = NOW()', 'updated_at = NOW()'];
            $updateParams = [':reply' => $loiNotes, ':id' => $complaintId];
            if ($loiFilePath) {
                $updateColumns[] = 'termination_loi_pdf_path = :loi_path';
                $updateParams[':loi_path'] = $loiFilePath;
            }
            $update = $db->prepare("UPDATE `$complaintTable` SET " . implode(', ', $updateColumns) . " WHERE id = :id");
            $update->execute($updateParams);

            $historyInsert = $db->prepare(
                "INSERT INTO `lc_complaint_decision_history`
                    (complaint_id, employee_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
                 VALUES
                    (:complaint_id, :employee_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
            );
            $historyInsert->execute([
                ':complaint_id'   => $complaintId,
                ':employee_id'    => !empty($complaint['respondent_employee_id']) ? (int)$complaint['respondent_employee_id'] : null,
                ':action'         => 'submit_termination_loi',
                ':old_status'     => $currentStatus,
                ':new_status'     => $currentStatus,
                ':decision_label' => 'Letter of Intent Received',
                ':performed_by'   => $userId,
                ':notes'          => 'Letter of Intent recorded from: ' . $loiFrom,
            ]);
            $db->commit();
            echo json_encode([
                'success'      => true,
                'message'      => 'Letter of Intent recorded successfully.',
                'complaint_id' => (int) $complaint['id'],
            ]);
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'save_termination_loi_pdf') {
        $checkStmt = $db->prepare("SELECT termination_reply, termination_recommended_at FROM `$complaintTable` WHERE id = :id LIMIT 1");
        $checkStmt->execute([':id' => $complaintId]);
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if (!$check || empty($check['termination_reply'])) {
            echo json_encode(['success' => false, 'message' => 'Letter of Intent content is required before saving as PDF.']);
            exit;
        }
        $baseDir = __DIR__ . '/../../../assets/termination_loi/';
        $webDir = '/modules/compliance/assets/termination_loi/';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create directory for PDF storage.']);
            exit;
        }
        $fileName = 'LOI-CMP-' . str_pad($complaintId, 5, '0', STR_PAD_LEFT) . '-' . date('YmdHis') . '.pdf';
        $filePath = $baseDir . $fileName;
        $webPath = $webDir . $fileName;
        $loiBody = (string) $check['termination_reply'];
        $loiSubject = isset($_POST['loi_subject']) ? trim((string) $_POST['loi_subject']) : 'Letter of Intent';
        $caseNumber = 'CMP-' . str_pad($complaintId, 5, '0', STR_PAD_LEFT);
        $htmlContent = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Letter of Intent - ' . htmlspecialchars($caseNumber) . '</title>';
        $htmlContent .= '<style>body{font-family:Arial,sans-serif;margin:40px;color:#1b2430;}h1{font-size:1.1rem;margin-bottom:16px;}h2{font-size:1rem;margin-top:24px;margin-bottom:8px;}.meta{font-size:0.8rem;color:#6b7280;margin-bottom:16px;}.body{white-space:pre-wrap;font-size:0.9rem;line-height:1.6;border:1px solid #e4e8ee;padding:16px;background:#f6f8fb;}.sig{margin-top:40px;font-size:0.85rem;}</style>';
        $htmlContent .= '</head><body>';
        $htmlContent .= '<h1>Letter of Intent</h1>';
        $htmlContent .= '<div class="meta"><strong>Case:</strong> ' . htmlspecialchars($caseNumber) . '<br><strong>Subject:</strong> ' . htmlspecialchars($loiSubject) . '<br><strong>Date:</strong> ' . date('M d, Y g:i A') . '</div>';
        $htmlContent .= '<h2>Content</h2><div class="body">' . nl2br(htmlspecialchars($loiBody)) . '</div>';
        $htmlContent .= '<div class="sig"><strong>HR / Compliance Officer</strong><br>Human Resources Management System</div>';
        $htmlContent .= '</body></html>';
        try {
            file_put_contents($filePath, $htmlContent);
            $update = $db->prepare("UPDATE `$complaintTable` SET termination_loi_pdf_path = :path, updated_at = NOW() WHERE id = :id");
            $update->execute([':path' => $webPath, ':id' => $complaintId]);
            echo json_encode([
                'success'      => true,
                'message'      => 'Letter of Intent saved as PDF.',
                'pdf_path'     => $webPath,
                'complaint_id' => (int) $complaint['id'],
            ]);
            exit;
        } catch (Throwable $e) {
            @unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'submit_termination_review_decision') {
        $reviewDecision = isset($_POST['review_decision']) ? trim((string) $_POST['review_decision']) : '';
        $reviewNotes = isset($_POST['review_notes']) ? trim((string) $_POST['review_notes']) : '';
        $confirmRejected = isset($_POST['confirm_rejected']) ? (bool) $_POST['confirm_rejected'] : false;
        if (!in_array($reviewDecision, ['considered', 'rejected'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid review decision.']);
            exit;
        }
        if ($reviewDecision === 'rejected' && !$confirmRejected) {
            echo json_encode(['success' => false, 'message' => 'Rejection requires explicit confirmation.', 'requires_confirmation' => true]);
            exit;
        }
        $allowedStatuses = ['termination_employee_reply', 'termination_reviewed', 'closed_termination_recommended'];
        if (!in_array($currentStatus, $allowedStatuses, true)) {
            echo json_encode(['success' => false, 'message' => 'Review decision cannot be made for the current status.']);
            exit;
        }
        if ($reviewDecision === 'considered') {
            $newStatus = 'closed';
            $decisionLabel = 'Termination Considered - Case Closed';
            $notes = 'Termination review considered. Letter of Intent accepted. Case closed.';
        } else {
            $newStatus = 'for_decision';
            $decisionLabel = 'Termination Rejected';
            $notes = 'Termination review rejected. Returned to decision stage.';
            if ($reviewNotes !== '') {
                $notes .= ' Notes: ' . $reviewNotes;
            }
        }
        $db->beginTransaction();
        try {
            $updateColumns = ['status = :status', 'termination_review_status = :review_status', 'updated_at = NOW()'];
            $updateParams = [':status' => $newStatus, ':review_status' => $reviewDecision, ':id' => $complaintId];
            $update = $db->prepare("UPDATE `$complaintTable` SET " . implode(', ', $updateColumns) . " WHERE id = :id");
            $update->execute($updateParams);
            $historyInsert = $db->prepare(
                "INSERT INTO `lc_complaint_decision_history`
                    (complaint_id, employee_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
                 VALUES
                    (:complaint_id, :employee_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
            );
            $historyInsert->execute([
                ':complaint_id'   => $complaintId,
                ':employee_id'    => !empty($complaint['respondent_employee_id']) ? (int)$complaint['respondent_employee_id'] : null,
                ':action'         => 'submit_termination_review_decision',
                ':old_status'     => $currentStatus,
                ':new_status'     => $newStatus,
                ':decision_label' => $decisionLabel,
                ':performed_by'   => $userId,
                ':notes'          => $notes,
            ]);
            $db->commit();
            echo json_encode([
                'success'      => true,
                'message'      => 'Review decision recorded: ' . $decisionLabel,
                'new_status'   => $newStatus,
                'complaint_id' => (int) $complaint['id'],
            ]);
            exit;
        } catch (Throwable $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            exit;
        }
    } elseif ($action === 'send_reconsideration_email') {
        $toEmail = isset($_POST['to_email']) ? trim((string) $_POST['to_email']) : '';
        $emailSubject = isset($_POST['email_subject']) ? trim((string) $_POST['email_subject']) : '';
        $emailBody = isset($_POST['email_body']) ? trim((string) $_POST['email_body']) : '';
        if ($toEmail === '' || $emailSubject === '' || $emailBody === '') {
            echo json_encode(['success' => false, 'message' => 'Missing email fields.']);
            exit;
        }
        $validRecipients = array_values(array_filter(array_map('trim', explode(',', $toEmail)), function($e) {
            return filter_var($e, FILTER_VALIDATE_EMAIL);
        }));
        if ($validRecipients === []) {
            echo json_encode(['success' => false, 'message' => 'No valid email recipients.']);
            exit;
        }
        $db->beginTransaction();
        try {
            foreach ($validRecipients as $recipient) {
                $notifColumns = ['employee_id', 'title', 'message', 'type', 'module', 'email', 'sender_email', 'is_read', 'created_at', 'updated_at'];
                $notifValues = [':employee_id', ':title', ':message', ':type', ':module', ':email', ':sender_email', ':is_read', 'NOW()', 'NOW()'];
                $notifParams = [
                    ':employee_id'   => $userId,
                    ':title'         => $emailSubject,
                    ':message'       => $emailBody,
                    ':type'          => 'reconsideration_email',
                    ':module'        => 'compliance',
                    ':email'         => $recipient,
                    ':sender_email'  => '',
                    ':is_read'       => 0,
                ];
                $stmt = $db->prepare('INSERT INTO lc_notifications (' . implode(', ', $notifColumns) . ') VALUES (' . implode(', ', $notifValues) . ')');
                $stmt->execute($notifParams);
                $db->prepare('INSERT INTO lc_sent_history (employee_id, title, message, type, department, module, email, sender_email, is_read, created_at, updated_at) VALUES (:employee_id, :title, :message, :type, :department, :module, :email, :sender_email, :is_read, NOW(), NOW())')->execute([
                    ':employee_id'   => $userId,
                    ':title'         => $emailSubject,
                    ':message'       => $emailBody,
                    ':type'          => 'reconsideration_email',
                    ':department'    => null,
                    ':module'        => 'compliance',
                    ':email'         => $recipient,
                    ':sender_email'  => '',
                    ':is_read'       => 0,
                ]);
            }
            $historyInsert = $db->prepare(
                "INSERT INTO `lc_complaint_decision_history`
                    (complaint_id, employee_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
                 VALUES
                    (:complaint_id, :employee_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
            );
            $historyInsert->execute([
                ':complaint_id'   => $complaintId,
                ':employee_id'    => !empty($complaint['respondent_employee_id']) ? (int)$complaint['respondent_employee_id'] : null,
                ':action'         => 'send_reconsideration_email',
                ':old_status'     => $currentStatus,
                ':new_status'     => $currentStatus,
                ':decision_label' => 'Reconsideration Email Sent',
                ':performed_by'   => $userId,
                ':notes'          => 'Reconsideration email sent to: ' . implode(', ', $validRecipients),
            ]);
            $db->commit();
            echo json_encode([
                'success'      => true,
                'message'      => 'Reconsideration email sent successfully.',
                'complaint_id' => (int) $complaint['id'],
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

    $updateColumns = ['status = :status', 'updated_at = NOW()'];
    $updateParams = [':status' => $newStatus, ':id' => $complaintId];

    if ($newStatus === 'closed_termination_recommended') {
        $updateColumns[] = 'termination_recommended_at = NOW()';
    }

    $update = $db->prepare("UPDATE `$complaintTable` SET " . implode(', ', $updateColumns) . " WHERE id = :id");
    $update->execute($updateParams);

    $historyInsert = $db->prepare(
        "INSERT INTO `lc_complaint_decision_history`
            (complaint_id, employee_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
         VALUES
            (:complaint_id, :employee_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
    );
    $historyInsert->execute([
        ':complaint_id'   => $complaintId,
        ':employee_id'    => !empty($complaint['respondent_employee_id']) ? (int)$complaint['respondent_employee_id'] : null,
        ':action'         => $action,
        ':old_status'     => $currentStatus,
        ':new_status'     => $newStatus,
        ':decision_label' => $decisionLabel,
        ':performed_by'   => $userId,
        ':notes'          => $notes,
    ]);

    if ($newStatus === 'termination_reviewed' && $currentStatus === 'termination_employee_reply') {
        $checkStmt = $db->prepare("SELECT employee_id, respondent_employee_id, employee_response, termination_reply, termination_recommended_at FROM `$complaintTable` WHERE id = :id LIMIT 1");
        $checkStmt->execute([':id' => $complaintId]);
        $check = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($check && !empty($check['termination_recommended_at'])) {
            $hasResponse = !empty($check['employee_response']) || !empty($check['termination_reply']);
            if (!$hasResponse) {
                $recommendedDate = new DateTime($check['termination_recommended_at']);
                $currentDate = new DateTime();
                $businessDays = 0;
                $checkDate = clone $recommendedDate;
                while ($businessDays < 5) {
                    $checkDate->modify('+1 day');
                    $dayOfWeek = (int)$checkDate->format('N');
                    if ($dayOfWeek < 6) {
                        $businessDays++;
                    }
                }
                $deadline = clone $recommendedDate;
                $deadline->modify('+' . $businessDays . ' business days');

                if ($currentDate >= $deadline) {
                    $employeeId = !empty($check['respondent_employee_id']) ? (int)$check['respondent_employee_id'] : (int)$check['employee_id'];

                    $exitCheck = $db->prepare("SELECT id FROM exit_terminations WHERE employee_id = :eid AND comments LIKE :ref LIMIT 1");
                    $exitCheck->execute([
                        ':eid' => $employeeId,
                        ':ref' => '%Complaint#' . $complaintId . '%'
                    ]);

                    if (!$exitCheck->fetchColumn()) {
                        $terminationReason = "Termination recommendation from Complaint #" . $complaintId . ". No letter of intent submitted within 5 business days from " . $recommendedDate->format('M d, Y') . ".";

                        $effectiveDate = clone $recommendedDate;
                        $effectiveDate->modify('+1 day');
                        while ((int)$effectiveDate->format('N') > 5) {
                            $effectiveDate->modify('+1 day');
                        }

                        $insertExit = $db->prepare("
                            INSERT INTO exit_terminations
                                (employee_id, termination_reason, effective_date, comments, submitted_by, status, created_at, updated_at)
                            VALUES
                                (:employee_id, :termination_reason, :effective_date, :comments, :submitted_by, 'pending_review', NOW(), NOW())
                        ");

                        $insertExit->execute([
                            ':employee_id' => $employeeId,
                            ':termination_reason' => $terminationReason,
                            ':effective_date' => $effectiveDate->format('Y-m-d'),
                            ':comments' => "Auto-generated from Complaint #" . $complaintId . ". No letter of intent submitted within 5 business days.",
                            ':submitted_by' => $userId,
                        ]);
                    }
                }
            }
        }
    }

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


