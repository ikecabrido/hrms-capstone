<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../../../auth/session.php';
require_once __DIR__ . '/../../lib/vendor/autoload.php';
require_once __DIR__ . '/../../lib/services/EmailService.php';
require_once __DIR__ . '/../../lib/services/EmailTemplate.php';
require_once __DIR__ . '/../../lib/ajax/document_template_helper.php';

use App\Services\EmailService;
use App\Services\EmailTemplate;

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$employeeId = isset($input['employee_id']) ? (int) $input['employee_id'] : 0;
$templateCode = trim((string) ($input['template_code'] ?? 'employee_handbook'));
$subject = trim((string) ($input['subject'] ?? ''));
$body = trim((string) ($input['body'] ?? ''));
$senderId = $_SESSION['employee_id'] ?? null;

if ($subject === '') {
    $subject = 'Action Required: Employee Handbook Acknowledgement';
}

if ($body === '') {
    $body = "Dear {{employee_name}},\n\n";
    $body .= "Greetings!\n\n";
    $body .= "We are pleased to provide you with the latest version of the **Employee Handbook of {company_name}**.\n\n";
    $body .= "The handbook contains important information about our workplace policies, procedures, standards, employee responsibilities, and other guidelines that help promote a professional, respectful, and productive work environment.\n\n";
    $body .= "**Handbook Details**\n\n";
    $body .= "* **Version:** {version}\n";
    $body .= "* **Effective Date:** {effective_date}\n";
    $body .= "* **Employee No.:** {employee_no}\n";
    $body .= "* **Department:** {department}\n";
    $body .= "* **Position:** {position}\n\n";
    $body .= "Please take time to carefully read and familiarize yourself with the contents of the handbook. The policies and guidelines contained therein are intended to help you understand your responsibilities and the standards expected of all employees.\n\n";
    $body .= "**Please review the handbook here:**\n\n";
    $body .= "{preview_url}\n\n";
    $body .= "After reviewing the handbook, please complete the required acknowledgement using the link below:\n\n";
    $body .= "**Acknowledge Employee Handbook:**\n";
    $body .= "{ack_url}\n\n";
    $body .= "Your acknowledgement confirms that you have received and reviewed the Employee Handbook and understand that you are responsible for becoming familiar with and complying with the applicable company policies and guidelines.\n\n";
    $body .= "If you have any questions or need clarification regarding any provision in the handbook, please coordinate with the Human Resources Department.\n\n";
    $body .= "Thank you for your cooperation and commitment to maintaining a professional workplace.\n\n";
    $body .= "Sincerely,\n\n";
    $body .= "**Human Resources Department**\n";
    $body .= "{company_name}";
}

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

if ($subject === '' || $body === '') {
    echo json_encode(['success' => false, 'message' => 'Subject and body are required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$employeeEmail = '';
$employeeName = 'Employee';
$employeeNo = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
$departmentName = '';
$positionName = '';

try {
    $stmt = $db->prepare("
        SELECT e.email, e.first_name, e.middle_name, e.last_name, e.employee_code,
               COALESCE(d.department_name, '') AS department_name,
               COALESCE(p.position_name, '') AS position_name
        FROM em_employees e
        LEFT JOIN em_departments d ON e.department_id = d.department_id
        LEFT JOIN em_positions p ON e.position_id = p.position_id
        WHERE e.employee_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $employeeId]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($emp) {
        $employeeEmail = (string) ($emp['email'] ?? '');
        $employeeName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
        $employeeNo = (string) ($emp['employee_code'] ?? $employeeNo);
        $departmentName = (string) ($emp['department_name'] ?? '');
        $positionName = (string) ($emp['position_name'] ?? '');
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load employee: ' . $e->getMessage()]);
    exit;
}

if ($employeeEmail === '' || !filter_var($employeeEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Employee email is missing or invalid']);
    exit;
}

$employer = lc_get_active_employer($db);
$employerName = (string) ($employer['name'] ?? 'Bestlink College of the Philippines');

$templateRecord = lc_get_active_template($db, $templateCode);
$version = (string) ($templateRecord['version'] ?? '1.0');
$effectiveDate = (string) ($templateRecord['effective_date'] ?? '');
$templateId = (int) ($templateRecord['template_id'] ?? 0);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . $host . '/hrms-capstone';

$ackUrl = $baseUrl . '/modules/compliance/pages/handbook-acknowledge.php';
$previewUrl = $baseUrl . '/modules/compliance/?page=preview-document&template_code=employee_handbook&mode=preview';

$personalizedBody = str_replace(
    ['{employee_name}', '{employee_no}', '{department}', '{position}', '{version}', '{effective_date}', '{ack_url}', '{preview_url}', '{company_name}'],
    [$employeeName, $employeeNo, $departmentName ?: 'N/A', $positionName ?: 'N/A', $version, $effectiveDate ? date('F d, Y', strtotime($effectiveDate)) : 'N/A', $ackUrl, $previewUrl, $employerName],
    $body
);

$personalizedBody = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $personalizedBody);

$htmlTemplatePath = __DIR__ . '/email-template.html';
if (is_file($htmlTemplatePath)) {
    $html = (string) file_get_contents($htmlTemplatePath);
    $html = str_replace(
        [
            '{{subject}}',
            '{{employee_name}}',
            '{{employee_no}}',
            '{{department}}',
            '{{position}}',
            '{{version}}',
            '{{effective_date}}',
            '{{body_content}}',
            '{{ack_url}}',
            '{{company_name}}',
            '{{company_address}}',
            '{{company_email}}',
            '{{company_website}}',
            '{{preview_url}}',
            '{{date}}',
            '{{time}}',
            '{{year}}',
        ],
        [
            htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($employeeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($employeeNo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($departmentName ?: 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($positionName ?: 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($version, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($effectiveDate ? date('F d, Y', strtotime($effectiveDate)) : 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $personalizedBody,
            htmlspecialchars($ackUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($employerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) ($employer['address'] ?? 'Quirino Highway, Brgy. Minuyan Proper, City of San Jose del Monte, Bulacan'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) ($employer['email'] ?? 'hr@bestlink.edu.ph'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars((string) ($employer['website'] ?? 'www.bestlinkcollege.edu.ph'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($previewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars(date('F j, Y'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars(date('g:i A'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            (int) date('Y'),
        ],
        $html
    );
} else {
    $html = EmailTemplate::buildHtml($subject, $personalizedBody, $employeeName, $employerName, $baseUrl);
}

$altBody = EmailTemplate::buildText($subject, $personalizedBody, $employeeName, $employerName);

$mailer = EmailService::getInstance();
$attachmentUrl = $baseUrl . '/modules/compliance/pages/generate-document.php?employee_id=' . urlencode((string) $employeeId) . '&document_type=employee_handbook&template=employee_handbook.php&template_code=employee_handbook&generate=1';
$attachmentName = 'Employee_Handbook_' . preg_replace('/[^A-Za-z0-9]/', '', $employeeName) . '_v' . preg_replace('/[^A-Za-z0-9]/', '', $version) . '.pdf';

$tempAttachment = null;
try {
    if (preg_match('#^https?://#i', $attachmentUrl)) {
        $tempAttachment = tempnam(sys_get_temp_dir(), 'eh_att_');
        $remoteContent = @file_get_contents($attachmentUrl);
        if ($remoteContent !== false && $remoteContent !== '') {
            file_put_contents($tempAttachment, $remoteContent);
        } else {
            $tempAttachment = null;
        }
    } else {
        $tempAttachment = realpath($attachmentUrl);
    }
} catch (Throwable $e) {
    $tempAttachment = null;
}

$mail = $mailer->getMail();
EmailTemplate::embedLogo($mail);
EmailTemplate::embedSignatory($mail);

if ($tempAttachment !== false && $tempAttachment !== null && is_file($tempAttachment)) {
    try {
        $mail->addAttachment($tempAttachment, $attachmentName);
    } catch (Throwable $e) {
        error_log('employee_handbook email: attachment error: ' . $e->getMessage());
    }
}

$sent = $mailer->send(
    ['email' => $employeeEmail, 'name' => $employeeName],
    $subject,
    $html,
    $altBody
);

$emailFailed = !$sent;
$emailError = $emailFailed ? 'SMTP send failed. Check mail logs.' : '';

if (!$emailFailed) {
    try {
        $senderEmail = '';
        if ($senderId) {
            $sStmt = $db->prepare('SELECT email FROM em_employees WHERE employee_id = :id LIMIT 1');
            $sStmt->execute([':id' => $senderId]);
            $sRow = $sStmt->fetch(PDO::FETCH_ASSOC);
            $senderEmail = (string) ($sRow['email'] ?? '');
        }

        $db->prepare("
            INSERT INTO lc_notifications
                (employee_id, title, message, type, module, email, sender_email, is_read, notification_type, created_at, updated_at)
            VALUES
                (:employee_id, :title, :message, :type, :module, :email, :sender_email, 0, 'email', NOW(), NOW())
        ")->execute([
            ':employee_id'   => $senderId ?? 0,
            ':title'         => $subject,
            ':message'       => $personalizedBody,
            ':type'          => 'employee_handbook_notification',
            ':module'        => 'compliance',
            ':email'         => $employeeEmail,
            ':sender_email'  => $senderEmail,
        ]);

        $db->prepare("
            INSERT INTO lc_sent_history
                (employee_id, title, message, type, department, module, email, sender_email, is_read, created_at, updated_at)
            VALUES
                (:employee_id, :title, :message, :type, :department, :module, :email, :sender_email, 0, NOW(), NOW())
        ")->execute([
            ':employee_id'   => $senderId ?? 0,
            ':title'         => $subject,
            ':message'       => $personalizedBody,
            ':type'          => 'employee_handbook_notification',
            ':department'    => null,
            ':module'        => 'compliance',
            ':email'         => $employeeEmail,
            ':sender_email'  => $senderEmail,
        ]);

        $db->prepare("
            INSERT INTO lc_document_requests
                (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code)
            VALUES
                (:employee_id, :rao_hired_id, :document_type, 'Pending', 'Medium', NULL, 1, 'none', :template_code)
        ")->execute([
            ':employee_id'   => $employeeId,
            ':rao_hired_id'  => null,
            ':document_type' => 'Employee Handbook',
            ':template_code' => $templateCode,
        ]);

        if ($templateId > 0) {
            $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
            if (!$ackStatus) {
                lc_upsert_handbook_acknowledgement($db, $employeeId, $templateId, $version, 'Viewed');
            }
        }
    } catch (Throwable $e) {
        error_log('employee_handbook email: notification/request error: ' . $e->getMessage());
    }
}

echo json_encode([
    'success' => !$emailFailed,
    'message' => $emailFailed ? $emailError : 'Employee handbook sent successfully.',
    'email_error' => $emailError,
    'recipient' => $employeeEmail,
    'attachment' => $attachmentName,
]);
