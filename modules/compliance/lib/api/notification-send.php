<?php
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../lib/vendor/autoload.php';

if (file_exists(__DIR__ . '/../../lib/services/EmailService.php')) {
    require_once __DIR__ . '/../../lib/services/EmailService.php';
}
if (file_exists(__DIR__ . '/../../lib/services/EmailTemplate.php')) {
    require_once __DIR__ . '/../../lib/services/EmailTemplate.php';
}
if (file_exists(__DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/PHPMailer.php';
}
if (file_exists(__DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/Exception.php')) {
    require_once __DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/Exception.php';
}
if (file_exists(__DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/SMTP.php')) {
    require_once __DIR__ . '/../../lib/vendor/phpmailer/phpmailer/src/SMTP.php';
}

header('Content-Type: application/json');

set_exception_handler(function ($e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $error['message']]);
        exit;
    }
});

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$mode = strtolower((string) ($input['mode'] ?? ''));
$notificationId = (int) ($input['notification_id'] ?? 0);
$notificationKey = (string) ($input['notification_key'] ?? '');
$recipientsRaw = trim((string) ($input['recipients'] ?? ''));
$subject = trim((string) ($input['subject'] ?? ''));
$body = trim((string) ($input['body'] ?? ''));
$attachmentUrl = trim((string) ($input['attachment_url'] ?? ''));
$attachmentName = trim((string) ($input['attachment_name'] ?? ''));
$department = trim((string) ($input['department'] ?? ''));

if (!in_array($mode, ['reply', 'forward', 'new'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mode']);
    exit;
}
if ($mode === 'reply' && $notificationId <= 0) {
    $notificationId = 0;
}
if ($notificationKey === '') {
    $notificationKey = 'notification';
}
if ($recipientsRaw === '' || $subject === '' || $body === '') {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
if (!$db instanceof PDO) {
    throw new RuntimeException('Database connection unavailable');
}

$senderId = $_SESSION['employee_id'] ?? null;
$senderName = 'HR & Legal Compliance Office';
$senderEmail = '';
if ($senderId) {
    $stmt = $db->prepare('SELECT first_name, last_name, email FROM em_employees WHERE employee_id = :id LIMIT 1');
    $stmt->execute([':id' => $senderId]);
    $nameRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($nameRow) {
        $senderName = trim($nameRow['first_name'] . ' ' . $nameRow['last_name']);
        $senderEmail = (string) ($nameRow['email'] ?? '');
    }
}

$recipients = array_values(array_filter(array_map('trim', explode(',', $recipientsRaw))));
$validRecipients = [];
foreach ($recipients as $recipient) {
    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $validRecipients[] = $recipient;
    }
}

if ($validRecipients === []) {
    echo json_encode(['success' => false, 'message' => 'No valid recipients']);
    exit;
}

$originalSubject = '';
$originalMessage = '';
if ($notificationId > 0) {
    $stmt = $db->prepare('SELECT title, message FROM lc_notifications WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $notificationId]);
    $orig = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($orig) {
        $originalSubject = (string) ($orig['title'] ?? '');
        $originalMessage = (string) ($orig['message'] ?? '');
    }
}

if ($mode === 'reply' || $mode === 'forward') {
    $prefix = $mode === 'reply' ? 'Re: ' : 'Fwd: ';
    $regex = $mode === 'reply' ? '/^re:\s*/i' : '/^fwd:\s*/i';
    if ($subject !== '' && !preg_match($regex, $subject)) {
        $subject = $prefix . $subject;
    }
}

try {
    $db->beginTransaction();

    foreach ($validRecipients as $recipient) {
        $stmt = $db->prepare('
            INSERT INTO lc_notifications
                (employee_id, title, message, type, module, email, sender_email, is_read, notification_type, created_at, updated_at)
            VALUES
                (:employee_id, :title, :message, :type, :module, :email, :sender_email, :is_read, "email", NOW(), NOW())
        ');
        $stmt->execute([
            ':employee_id'   => $senderId,
            ':title'         => $subject,
            ':message'       => $body,
            ':type'          => $notificationKey,
            ':module'        => 'compliance',
            ':email'         => $recipient,
            ':sender_email'  => $senderEmail,
            ':is_read'       => 0,
        ]);

        $db->prepare('
            INSERT INTO lc_sent_history
                (employee_id, title, message, type, department, module, email, sender_email, is_read, created_at, updated_at)
            VALUES
                (:employee_id, :title, :message, :type, :department, :module, :email, :sender_email, :is_read, NOW(), NOW())
        ')->execute([
            ':employee_id'   => $senderId,
            ':title'         => $subject,
            ':message'       => $body,
            ':type'          => $notificationKey,
            ':department'    => $department !== '' ? $department : null,
            ':module'        => 'compliance',
            ':email'         => $recipient,
            ':sender_email'  => $senderEmail,
            ':is_read'       => 0,
        ]);
    }

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to save notification: ' . $e->getMessage()]);
    exit;
}

$emailFailed = false;
$emailError = '';
    $mailer = \App\Services\EmailService::getInstance();

if (!$mailer->isLogMode()) {
    $recipientName = count($validRecipients) === 1
    ? $validRecipients[0]
    : 'Employee';
        $html = \App\Services\EmailTemplate::buildHtml(
        $subject,
        $body,
        $recipientName,
        'Bestlink College of the Philippines',
        '',
        '',
        true
    );

    $altBody = \App\Services\EmailTemplate::buildText($subject, $body, $recipientName);

    $mail = $mailer->getMail();
    \App\Services\EmailTemplate::embedLogo($mail);
    \App\Services\EmailTemplate::embedSignatory($mail);

    if ($attachmentUrl !== '' && $attachmentName !== '') {
        $tempAttachment = null;
        try {
            if (preg_match('#^https?://#i', $attachmentUrl)) {
                $tempAttachment = tempnam(sys_get_temp_dir(), 'nc_att_');
                $remoteContent = @file_get_contents($attachmentUrl);
                if ($remoteContent !== false && $remoteContent !== '') {
                    file_put_contents($tempAttachment, $remoteContent);
                } else {
                    $tempAttachment = null;
                }
            } else {
                $tempAttachment = realpath($attachmentUrl);
            }
            if ($tempAttachment !== false && $tempAttachment !== null && is_file($tempAttachment)) {
                $mail->addAttachment($tempAttachment, $attachmentName);
            }
        } catch (Throwable $e) {
            error_log('notification-send: attachment error: ' . $e->getMessage());
        }
    }

    $addresses = [];
    foreach ($validRecipients as $email) {
        $addresses[] = ['email' => $email, 'name' => $email];
    }

    $sent = $mailer->send($addresses, $subject, $html, $altBody);
    if (!$sent) {
        $emailFailed = true;
        $emailError = 'SMTP send failed. Check mail logs.';
    }
}

$templateCode = trim((string) ($input['template_code'] ?? ''));
$documentType  = trim((string) ($input['document_type'] ?? ''));
$notificationKey = (string) ($input['notification_key'] ?? '');
$employeeIdFromPayload = trim((string) ($input['employee_id'] ?? ''));
$contractId = isset($input['contract_id']) ? (int) $input['contract_id'] : 0;

$disciplineTemplateMap = [
    'written_warning'            => 'closed_warning_issued',
    'suspension_notice'          => 'closed_suspension',
    'termination_decision'       => 'closed_termination_recommended',
    'notice_of_decision'         => 'closed_termination_recommended',
];

$autoCloseStatus = $disciplineTemplateMap[$templateCode] ?? null;

if ($autoCloseStatus !== null && !$emailFailed) {
    $targetEmployeeId = '';
    if ($employeeIdFromPayload !== '') {
        $targetEmployeeId = $employeeIdFromPayload;
    } else {
        $resolved = nc_resolve_employee_id($db, $validRecipients[0] ?? '');
        $targetEmployeeId = $resolved !== null ? (string)$resolved : '';
    }
    if ($targetEmployeeId !== '') {
        nc_auto_close_complaint_after_email($db, (int)$targetEmployeeId, $autoCloseStatus, $templateCode, $senderId ?? 0);
    }
}

if ($templateCode === 'contract_renewal') {
    $salaryInput = trim((string) ($input['contract_salary_input'] ?? ''));
    $targetEmployeeId = $employeeIdFromPayload !== '' ? (int) $employeeIdFromPayload : 0;
    if ($targetEmployeeId === 0) {
        $resolved = nc_resolve_employee_id($db, $validRecipients[0] ?? '');
        $targetEmployeeId = $resolved ?? 0;
    }

    if ($targetEmployeeId > 0 && $salaryInput !== '' && is_numeric($salaryInput)) {
        try {
            $db->beginTransaction();

            $db->prepare("
                UPDATE em_employees
                SET negotiated_salary = :salary,
                    updated_at = NOW()
                WHERE employee_id = :eid
            ")->execute([
                ':salary' => number_format((float) $salaryInput, 2, '.', ''),
                ':eid' => $targetEmployeeId,
            ]);

            $db->prepare("
                DELETE FROM lc_document_requests
                WHERE employee_id = :eid
                  AND (rao_hired_id IS NULL OR rao_hired_id = 0)
                  AND document_type = 'Contract Renewal'
                  AND template_code = 'contract_renewal'
                ORDER BY created_at DESC
                LIMIT 1
            ")->execute([':eid' => $targetEmployeeId]);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('notification-send contract_renewal apply error: ' . $e->getMessage());
        }
    }
}

if ($notificationKey === 'contract_renewal_reminder' && $contractId > 0 && !$emailFailed) {
    try {
        $db->prepare("UPDATE lc_contracts SET renewal_reminder_sent_at = NOW() WHERE contract_id = :cid")
            ->execute([':cid' => $contractId]);
    } catch (Throwable $e) {
        error_log('notification-send contract_renewal_reminder update error: ' . $e->getMessage());
    }
}

function nc_resolve_employee_id(PDO $db, string $email): ?int {
    if ($email === '') return null;
    try {
        $stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['employee_id'])) {
            return (int) $row['employee_id'];
        }
    } catch (Throwable $e) {}
    return null;
}

function nc_auto_close_complaint_after_email(PDO $db, int $employeeId, string $closeStatus, string $templateCode, int $performedBy): void {
    $closedStatuses = ['closed','closed_no_violation','closed_warning_issued','closed_suspension','closed_termination_recommended','closed_resolved'];
    try {
        $placeholders = array_map(fn($i) => ":closed_$i", array_keys($closedStatuses));
        $stmt = $db->prepare("SELECT id, status FROM lc_complaints WHERE employee_id = :eid AND status NOT IN (" . implode(',', $placeholders) . ") ORDER BY id DESC LIMIT 1");
        $params = [':eid' => $employeeId];
        foreach ($closedStatuses as $i => $status) {
            $params[":closed_$i"] = $status;
        }
        $stmt->execute($params);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$complaint) {
            return;
        }

        $currentStatus = (string) ($complaint['status'] ?? '');
        $complaintId = (int) ($complaint['id'] ?? 0);

        $humanLabels = [
            'closed_warning_issued'     => 'Written Warning Issued',
            'closed_suspension'         => 'Suspension Issued',
            'closed_termination_recommended' => 'Termination Recommended',
        ];
        $decisionLabel = $humanLabels[$closeStatus] ?? 'Closed';

        $db->beginTransaction();
        try {
            $update = $db->prepare("UPDATE lc_complaints SET status = :status, updated_at = NOW() WHERE id = :id");
            $update->execute([':status' => $closeStatus, ':id' => $complaintId]);

            $historyInsert = $db->prepare(
                "INSERT INTO lc_complaint_decision_history
                    (complaint_id, action, old_status, new_status, decision_label, performed_by, notes, created_at)
                 VALUES
                    (:complaint_id, :action, :old_status, :new_status, :decision_label, :performed_by, :notes, NOW())"
            );
            $historyInsert->execute([
                ':complaint_id'   => $complaintId,
                ':action'         => 'finalize_decision',
                ':old_status'     => $currentStatus,
                ':new_status'     => $closeStatus,
                ':decision_label' => $decisionLabel,
                ':performed_by'   => $performedBy > 0 ? $performedBy : null,
                ':notes'          => 'Auto-closed: ' . $templateCode . ' document email sent successfully.',
            ]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('auto_close_complaint_after_email error: ' . $e->getMessage());
        }
    } catch (Throwable $e) {
        error_log('auto_close_complaint_after_email lookup error: ' . $e->getMessage());
    }
}

$response = [
    'success' => true,
    'message' => 'Notification sent successfully',
    'emailed' => !$emailFailed,
];
if ($emailFailed) {
    $response['message'] = 'Notification saved, but email delivery failed.';
    $response['email_error'] = $emailError;
}

echo json_encode($response);

