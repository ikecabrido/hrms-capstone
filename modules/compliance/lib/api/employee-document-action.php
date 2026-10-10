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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection unavailable.',
    ]);
    exit;
}

set_exception_handler(function (Throwable $e) {
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

if (!function_exists('send_json')) {
    function send_json($payload, $code = 200) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if ($code !== 200) { http_response_code($code); }
        echo json_encode($payload);
        exit;
    }
}

$currentEmployeeId = $_SESSION['employee_id'] ?? null;
if ($currentEmployeeId === null) {
    send_json(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$action = strtolower(trim((string) ($_POST['action'] ?? '')));

try {
    $db->beginTransaction();

    $docId = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;

    if ($docId <= 0) {
        $db->rollBack();
        send_json(['success' => false, 'message' => 'Invalid document_id.'], 400);
    }

    $doc = null;
    $employeeEmail = null;
    $employeeFullName = null;

    $stmt = $db->prepare("
        SELECT d.document_id, d.document_name, d.verification_status, d.file_path,
               CONCAT(e.first_name, ' ', e.last_name) AS full_name, e.email, e.employee_id,
               dep.department_name
        FROM employee_documents d
        LEFT JOIN em_employees e ON e.employee_id = d.employee_id
        LEFT JOIN em_departments dep ON dep.id = e.department_id
        WHERE d.document_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        $db->rollBack();
        send_json(['success' => false, 'message' => 'Document not found.'], 404);
    }

    $employeeEmail    = $doc['email'] ?? null;
    $employeeFullName = $doc['full_name'] ?? '';

    $notifTitle = '';
    $notifMsg   = '';
    $notifType  = 'info';
    $sendEmail  = false;

    switch ($action) {

        case 'verify_academic':
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

            $stmt = $db->prepare("
                UPDATE employee_documents
                SET status = 'valid',
                    verified_by = :uid,
                    verified_at = NOW()
                WHERE document_id = :id
            ");
            $stmt->execute([':uid' => $currentEmployeeId, ':id' => $docId]);

            if ($notes !== '') {
                try {
                    $db->exec("ALTER TABLE employee_documents ADD COLUMN IF NOT EXISTS verification_notes TEXT DEFAULT NULL");
                } catch (Throwable $e) {
                }
                try {
                    $stmt = $db->prepare("UPDATE employee_documents SET verification_notes = :notes WHERE document_id = :id");
                    $stmt->execute([':notes' => $notes, ':id' => $docId]);
                } catch (Throwable $e) {
                }
            }

            $notifTitle = 'Academic Certificate Verified';
            $notifMsg   = sprintf(
                'Your academic certificate "%s" has been verified by the compliance team.%s',
                $doc['document_name'] ?? 'N/A',
                $notes ? ' Notes: ' . $notes : ''
            );
            $notifType  = 'success';
            $sendEmail  = true;
            break;

        case 'verify':
            $stmt = $db->prepare("
                UPDATE employee_documents
                SET status = 'valid',
                    verified_by = :uid,
                    verified_at = NOW()
                WHERE document_id = :id
            ");
            $stmt->execute([':uid' => $currentEmployeeId, ':id' => $docId]);

            $notifTitle = 'Document Verified';
            $notifMsg   = sprintf(
                'Your document "%s" has been verified by the compliance team.',
                $doc['document_name'] ?? 'N/A'
            );
            $notifType  = 'success';
            $sendEmail  = true;
            break;

        case 'reject':
            $reason = trim((string) ($_POST['reason'] ?? ''));

            if ($reason === '') {
                $db->rollBack();
                send_json(['success' => false, 'message' => 'Rejection reason is required.'], 400);
            }

            $stmt = $db->prepare("
                UPDATE employee_documents
                SET status = 'expired',
                    expiry_date = COALESCE(expiry_date, CURDATE())
                WHERE document_id = :id
            ");
            $stmt->execute([':id' => $docId]);

            $notifTitle = 'Document Rejected';
            $notifMsg   = sprintf(
                'Your document "%s" was rejected. Reason: %s',
                $doc['document_name'] ?? 'N/A',
                $reason
            );
            $notifType = 'danger';
            $sendEmail = true;
            break;

        case 'flag':
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

            $notifTitle = 'Document Flagged';
            $notifMsg   = sprintf(
                'Your document "%s" has been flagged for review.%s',
                $doc['document_name'] ?? 'N/A',
                $notes ? ' Notes: ' . $notes : ''
            );
            $notifType = 'warning';
            $sendEmail = true;
            break;

        case 'archive':
            $stmt = $db->prepare("
                UPDATE employee_documents
                SET status = 'expired'
                WHERE document_id = :id
            ");
            $stmt->execute([':id' => $docId]);

            $notifTitle = 'Document Archived';
            $notifMsg   = sprintf(
                'Your document "%s" has been archived.',
                $doc['document_name'] ?? 'N/A'
            );
            $notifType = 'info';
            $sendEmail = true;
            break;

        case 'request_update':
            $notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;

            $stmt = $db->prepare("
                UPDATE employee_documents
                SET status = 'pending'
                WHERE document_id = :id
            ");
            $stmt->execute([':id' => $docId]);

            $notifTitle = 'Document Update Required';
            $notifMsg   = sprintf(
                'Please re-upload the document "%s".%s',
                $doc['document_name'] ?? 'N/A',
                $notes ? ' Note: ' . $notes : ''
            );
            $notifType = 'warning';
            $sendEmail = true;
            break;

        case 'send_reminder':
            $db->rollBack();

            $empName = (string)($doc['full_name'] ?? '');
            $empEmail = (string)($doc['email'] ?? '');
            $docName = (string)($doc['document_name'] ?? '');

            $checkStmt = $db->prepare('SELECT COUNT(*) FROM lc_notifications WHERE type = :key AND email = :email AND module = :module');
            $checkStmt->execute([
                ':key' => 'document_reminder',
                ':email' => $empEmail,
                ':module' => 'compliance',
            ]);
            if ((int)$checkStmt->fetchColumn() > 0) {
                send_json(['success' => false, 'message' => 'Reminder already sent for this document.'], 409);
            }

            $subject = 'Reminder: Document Submission';
            $message = 'This is a reminder to submit your document "' . $docName . '". Please upload it as soon as possible.';

            $params = [
                'mode' => 'forward',
                'notification_key' => 'document_reminder',
                'to_recipient_email' => $empEmail,
                'to_recipient_name' => $empName,
                'subject' => $subject,
                'document_name' => $docName,
            ];
            if ($message !== '') {
                $params['body'] = '<p>Hello ' . htmlspecialchars($empName, ENT_QUOTES) . ',</p><p>' . htmlspecialchars($message, ENT_QUOTES) . '</p><p>Thank you,<br>HR Management</p>';
            }

            if (!empty($params['body']) && is_string($params['body'])) {
                if (!isset($_SESSION['lc_body_tokens']) || !is_array($_SESSION['lc_body_tokens'])) {
                    $_SESSION['lc_body_tokens'] = [];
                }
                foreach ($_SESSION['lc_body_tokens'] as $k => $v) {
                    if ((time() - (int)($v['ts'] ?? 0)) > 3600) {
                        unset($_SESSION['lc_body_tokens'][$k]);
                    }
                }
                $bodyToken = bin2hex(random_bytes(8));
                $_SESSION['lc_body_tokens'][$bodyToken] = ['body' => $params['body'], 'ts' => time()];
                $params['body_token'] = $bodyToken;
                unset($params['body']);
            }

            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
            $lcDir = realpath(__DIR__ . '/../..');
            $lcWebBase = '';
            if ($docRoot !== false && $lcDir !== false && strpos($lcDir, $docRoot) === 0) {
                $rel = str_replace('\\', '/', substr($lcDir, strlen($docRoot)));
                $lcWebBase = '/' . ltrim($rel, '/') . '/';
            }
            $redirect = $lcWebBase . 'modules/compliance/pages/notification-compose.php?' . http_build_query($params);

            send_json(['success' => true, 'redirect' => $redirect, 'message' => 'Redirecting to notification composer...']);
            break;

        default:
            $db->rollBack();
            send_json(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    $notifEmpId = (int) ($doc['employee_id'] ?? 0);
    $notifEmail = $employeeEmail ?: 'system';

    $stmt = $db->prepare("
        INSERT INTO lc_notifications
            (employee_id, email, title, message, type, is_read, created_at)
        VALUES
            (:eid, :email, :title, :msg, :type, 0, NOW())
    ");
    $stmt->execute([
        ':eid'    => $notifEmpId > 0 ? $notifEmpId : null,
        ':email'  => $notifEmail,
        ':title'  => $notifTitle,
        ':msg'    => $notifMsg,
        ':type'   => $notifType,
    ]);

    if (!empty($employeeEmail) && $notifEmail !== 'system') {
        $db->prepare("
            INSERT INTO lc_sent_history
                (employee_id, email, title, message, type, department, is_read, created_at)
            VALUES
                (:eid, :email, :title, :msg, :type, :department, 0, NOW())
        ")->execute([
            ':eid'         => $notifEmpId > 0 ? $notifEmpId : null,
            ':email'       => $notifEmail,
            ':title'       => $notifTitle,
            ':msg'         => $notifMsg,
            ':type'        => $notifType,
            ':department'  => null,
        ]);
    }

    if ($sendEmail && !empty($employeeEmail)) {
        try {
            if (file_exists(__DIR__ . '/../../services/EmailService.php')) {
                require_once __DIR__ . '/../../services/EmailService.php';
                $mailer = \App\Services\EmailService::getInstance();
                $empId  = (int) ($doc['employee_id'] ?? 0);
                $subjMap = [
                    'verify'            => 'Document Verified',
                    'verify_academic'   => 'Academic Certificate Verified',
                    'reject'            => 'Document Rejected',
                    'flag'              => 'Document Flagged',
                    'archive'           => 'Document Archived',
                    'request_update'    => 'Document Update Required',
                    'send_reminder'     => 'Reminder: Document Submission',
                ];
                $subj = $subjMap[$action] ?? 'Document Notification';

                if ($empId > 0) {
                    $mailer->sendComplianceReminder($empId, $notifMsg);
                } elseif (!empty($employeeEmail)) {
                    $mailer->send(
                        $employeeEmail,
                        $subj,
                        '<p>' . htmlspecialchars($notifMsg, ENT_QUOTES | ENT_HTML5) . '</p>',
                        $notifMsg
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('[employee_document_action] Email send error: ' . $e->getMessage());
        }
    }

    $db->commit();

    $messages = [
        'verify'            => 'Document verified successfully.',
        'verify_academic'   => 'Academic certificate verified successfully.',
        'reject'            => 'Document rejected successfully.',
        'flag'              => 'Document flagged successfully.',
        'archive'           => 'Document archived successfully.',
        'request_update'    => 'Update requested from employee.',
        'send_reminder'     => 'Reminder sent to employee.',
    ];

    send_json([
        'success' => true,
        'message' => $messages[$action] ?? 'Action completed.',
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    send_json(['success' => false, 'message' => 'Action failed: ' . $e->getMessage()], 500);
}

