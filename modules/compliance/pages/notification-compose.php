<?php
require_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../../../database/db.php';

$skipModuleHeader = false;

$mode = strtolower((string) ($_GET['mode'] ?? ($_POST['mode'] ?? '')));
$notificationKey = (string) ($_GET['notification_key'] ?? ($_POST['notification_key'] ?? ''));
$notificationId = isset($_GET['notification_id']) ? (int) $_GET['notification_id'] : 0;
$notificationId = max($notificationId, isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0);

if ($mode === '') {
    http_response_code(400);
    echo 'Missing required parameter: mode is required to compose a notification.';
    exit;
}

if (!in_array($mode, ['reply', 'forward', 'new'], true)) {
    http_response_code(400);
    echo 'Invalid mode';
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!($db instanceof PDO)) {
  http_response_code(500);
  echo 'Database connection unavailable.';
  exit;
}

$emailConfigPath = __DIR__ . '/lib/config/email_config.php';
$emailConfig = is_file($emailConfigPath) ? require $emailConfigPath : [];

$employeeId = $_SESSION['employee_id'] ?? null;

$senderInfoName = '';
$senderInfoRole = '';
$senderInfoEmail = $_SESSION['email'] ?? '';

if ($employeeId) {
    $stmt = $db->prepare('SELECT CONCAT(e.first_name, IFNULL(CONCAT(" ", e.middle_name, " "), " "), e.last_name) AS full_name, r.role_name FROM em_employees e LEFT JOIN user_account ua ON e.employee_id = ua.employee_id LEFT JOIN em_roles r ON ua.role_id = r.role_id WHERE e.employee_id = :employee_id LIMIT 1');
    $stmt->execute(['employee_id' => $employeeId]);
    $sRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sRow) {
        $senderInfoName = trim((string) $sRow['full_name']);
        $senderInfoRole = trim((string) ($sRow['role_name'] ?? ''));
    }
}

$senderEmail = '';
$notifMessage = '';
$notifType = '';
$isUnread = false;
$notifTitle = '';

if ($notificationId > 0) {
    $hasStatusColumn = false;
    $hasIsReadColumn = false;
    $colsStmt = $db->query("SHOW COLUMNS FROM lc_notifications");
    while ($col = $colsStmt->fetch(PDO::FETCH_ASSOC)) {
        $field = strtolower($col['Field'] ?? '');
        if ($field === 'status') $hasStatusColumn = true;
        if ($field === 'is_read') $hasIsReadColumn = true;
    }

    $hasSenderEmailColumn = false;
    $colsStmt2 = $db->query("SHOW COLUMNS FROM lc_notifications");
    while ($col2 = $colsStmt2->fetch(PDO::FETCH_ASSOC)) {
        $field2 = strtolower($col2['Field'] ?? '');
        if ($field2 === 'sender_email') {
            $hasSenderEmailColumn = true;
            break;
        }
    }

    $senderEmailSelect = $hasSenderEmailColumn ? ', sender_email, email' : ', email';

    if ($hasStatusColumn) {
        $sql = 'SELECT title, message, type, status' . $senderEmailSelect . ' FROM lc_notifications WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notifTitle = (string) ($row['title'] ?? '');
            $notifMessage = (string) ($row['message'] ?? '');
            $notifType = (string) ($row['type'] ?? '');
            $status = (string) ($row['status'] ?? 'Viewed');
            $isUnread = $status === 'Unread';
            $senderEmail = (string) ($row['sender_email'] ?? '');
            if ($senderEmail === '' && !empty($row['email'])) {
                $senderEmail = (string) $row['email'];
            }
        }
    } elseif ($hasIsReadColumn) {
        $sql = 'SELECT title, message, type, is_read' . $senderEmailSelect . ' FROM lc_notifications WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notifTitle = (string) ($row['title'] ?? '');
            $notifMessage = (string) ($row['message'] ?? '');
            $notifType = (string) ($row['type'] ?? '');
            $isUnread = ((int) ($row['is_read'] ?? 0)) === 0;
            $senderEmail = (string) ($row['sender_email'] ?? '');
            if ($senderEmail === '' && !empty($row['email'])) {
                $senderEmail = (string) $row['email'];
            }
        }
    } else {
        $sql = 'SELECT title, message, type' . $senderEmailSelect . ' FROM lc_notifications WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $notifTitle = (string) ($row['title'] ?? '');
            $notifMessage = (string) ($row['message'] ?? '');
            $notifType = (string) ($row['type'] ?? '');
            $senderEmail = (string) ($row['sender_email'] ?? '');
            if ($senderEmail === '' && !empty($row['email'])) {
                $senderEmail = (string) $row['email'];
            }
        }
    }
}

$origSenderName = '';
$origSenderEmail = '';
$origSubject = '';
$origMessage = '';

$requestRecipientEmail = trim((string) ($_GET['to_recipient_email'] ?? ''));
$requestRecipientName = trim((string) ($_GET['to_recipient_name'] ?? ''));
$requestRecipientNo = trim((string) ($_GET['to_recipient_no'] ?? ''));
$requestRecipientDept = trim((string) ($_GET['to_recipient_dept'] ?? ''));
$composeBody = trim((string) ($_GET['body'] ?? ''));

if ($notificationId > 0) {
    if (filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        $origSenderEmail = $senderEmail;
    }

    $origSubject = (string) ($notifType ?: $notificationKey);
    $origMessage = (string) ($notifMessage ?: $composeBody);

    if ($origSenderName === '') {
        $stmt = $db->prepare('SELECT first_name, last_name FROM em_employees WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $origSenderEmail ?: $requestRecipientEmail]);
        $nameRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($nameRow) {
            $origSenderName = trim($nameRow['first_name'] . ' ' . $nameRow['last_name']);
        }
    }
}

if ($origSenderName === '') {
    $origSenderName = $senderInfoName !== '' ? $senderInfoName : 'User';
}

$composeRecipientEmail = '';
$composeRecipientName = $requestRecipientName;
$resolvedRecipientNo = $requestRecipientNo;

if (filter_var($requestRecipientEmail, FILTER_VALIDATE_EMAIL)) {
    $composeRecipientEmail = $requestRecipientEmail;
} elseif (filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    $composeRecipientEmail = $senderEmail;
} elseif ($requestRecipientName !== '' || $requestRecipientNo !== '') {
    $conds = [];
    $params = [];
    if ($requestRecipientNo !== '') {
        $conds[] = 'e.employee_code = :no';
        $params[':no'] = $requestRecipientNo;
    }
    if ($requestRecipientName !== '') {
        $conds[] = 'e.first_name LIKE :name_first OR e.last_name LIKE :name_last';
        $params[':name_first'] = '%' . $requestRecipientName . '%';
        $params[':name_last'] = '%' . $requestRecipientName . '%';
    }
    if ($conds !== []) {
        $sql = 'SELECT e.email, e.first_name, e.last_name, e.employee_code FROM em_employees e WHERE (' . implode(' OR ', $conds) . ') AND e.email IS NOT NULL AND e.email <> \'\' ORDER BY e.employee_id ASC LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $composeRecipientEmail = (string) ($row['email'] ?? '');
            if ($composeRecipientName === '' && ($row['first_name'] || $row['last_name'])) {
                $composeRecipientName = trim($row['first_name'] . ' ' . $row['last_name']);
            }
            if ($resolvedRecipientNo === '' && !empty($row['employee_code'])) {
                $resolvedRecipientNo = (string) $row['employee_code'];
            }
        }
    }
}

if ($composeRecipientEmail === '' && $requestRecipientDept !== '') {
    $stmt = $db->prepare('SELECT e.email, e.first_name, e.last_name, e.employee_code FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id WHERE d.department_name = :dept AND e.email IS NOT NULL AND e.email <> \'\' ORDER BY e.employee_id ASC LIMIT 1');
    $stmt->execute([':dept' => $requestRecipientDept]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $composeRecipientEmail = (string) ($row['email'] ?? '');
        if ($composeRecipientName === '' && ($row['first_name'] || $row['last_name'])) {
            $composeRecipientName = trim($row['first_name'] . ' ' . $row['last_name']);
        }
        if ($resolvedRecipientNo === '' && !empty($row['employee_code'])) {
            $resolvedRecipientNo = (string) $row['employee_code'];
        }
    }
}

$documentName = trim((string) ($_GET['document_name'] ?? ''));
$documentContext = [];

if ($documentName !== '' && ($composeRecipientEmail === '' || $composeRecipientName === '')) {
    $stmt = $db->prepare('
        SELECT d.employee_id, e.first_name, e.last_name, e.email, e.employee_code,
               COALESCE(dept.department_name, "N/A") AS department,
               COALESCE(pos.position_name, "N/A") AS position,
                d.created_at AS issue_date
        FROM employee_documents d
        LEFT JOIN em_employees e ON e.employee_id = d.employee_id
        LEFT JOIN em_departments dept ON dept.department_id = e.department_id
        LEFT JOIN em_positions pos ON pos.position_id = e.position_id
        WHERE d.document_name = :name
        ORDER BY d.created_at DESC
        LIMIT 1
    ');
    $stmt->execute([':name' => $documentName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if ($composeRecipientEmail === '' && !empty($row['email'])) {
            $composeRecipientEmail = (string) $row['email'];
        }
        if ($composeRecipientName === '' && ($row['first_name'] || $row['last_name'])) {
            $composeRecipientName = trim($row['first_name'] . ' ' . $row['last_name']);
        }
        if ($resolvedRecipientNo === '' && !empty($row['employee_code'])) {
            $resolvedRecipientNo = (string) $row['employee_code'];
        }
        $documentContext = [
            'document_name' => $documentName,
            'employee_name' => $composeRecipientName ?: trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'department'    => (string) ($row['department'] ?? ''),
            'position'      => (string) ($row['position'] ?? ''),
            'issued_date'   => !empty($row['issue_date']) ? date('F d, Y', strtotime($row['issue_date'])) : date('F d, Y'),
        ];
    }
}

$attachmentContractId = isset($_GET['attachment_contract_id']) ? (int) $_GET['attachment_contract_id'] : 0;
$attachmentName = trim((string) ($_GET['attachment_name'] ?? ''));
$attachmentUrl = rawurldecode(trim((string) ($_GET['attachment_url'] ?? '')));

if ($mode === 'forward' && $attachmentContractId > 0 && ($attachmentName === '' || $attachmentUrl === '')) {
    $stmt = $db->prepare('SELECT contract_number FROM lc_contracts WHERE contract_id = :cid LIMIT 1');
    $stmt->execute([':cid' => $attachmentContractId]);
    $cRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cRow && !empty($cRow['contract_number'])) {
        $cNum = $cRow['contract_number'];
        $pdfName = 'contract_' . $cNum . '.pdf';
        if ($attachmentName === '') {
            $attachmentName = $pdfName;
        }
        if ($attachmentUrl === '') {
            $attachmentUrl = '/hrms-capstone/modules/compliance/pages/labor-law-compliance/uploads/contracts/' . rawurlencode($pdfName);
        }
    }
}

$defaultSubject = trim((string) ($_GET['subject'] ?? ''));
$composeBody = trim((string) ($_GET['body'] ?? ''));

$contractId = isset($_GET['contract_id']) ? (int) $_GET['contract_id'] : 0;
$redirectTo = trim((string) ($_GET['redirect_to'] ?? ''));

if ($composeBody === '' && !empty($_GET['body_token'])) {
    $bodyToken = (string) $_GET['body_token'];
    if (!empty($_SESSION['lc_body_tokens'][$bodyToken]['body'])) {
        $composeBody = (string) $_SESSION['lc_body_tokens'][$bodyToken]['body'];
    }
}

if ($mode === 'reply' || $mode === 'forward') {
    $subjectSource = $notifTitle !== '' ? $notifTitle : ($notificationKey !== 'warning' ? $notificationKey : '');
    $prefix = $mode === 'reply' ? 'Re: ' : 'Fwd: ';
    $regex = $mode === 'reply' ? '/^re:\s*/i' : '/^fwd:\s*/i';
    if ($subjectSource !== '' && !preg_match($regex, $subjectSource)) {
        $defaultSubject = $prefix . $subjectSource;
    } elseif ($subjectSource !== '') {
        $defaultSubject = $subjectSource;
    }
}

if ($notificationId === 0 && $composeRecipientName !== '') {
    if ($defaultSubject === '') {
        $defaultSubject = $mode === 'reply' ? 'Re: Message' : 'Message to ' . $composeRecipientName;
    }
    if ($composeBody === '') {
        $composeBody = "Dear " . $composeRecipientName . ",\n\n";
    }
}

if ($notificationKey !== '') {
    try {
        $stmt = $db->query('CREATE TABLE IF NOT EXISTS `lc_email_templates` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `template_code` VARCHAR(100) DEFAULT NULL,
            `scenario` VARCHAR(100) DEFAULT NULL,
            `component` ENUM(\'subject\',\'body\') NOT NULL,
            `component_order` INT(11) DEFAULT 0,
            `template_text` TEXT DEFAULT NULL,
            `status` ENUM(\'Active\',\'Inactive\') DEFAULT \'Active\',
            `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
            `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_template_code` (`template_code`),
            KEY `idx_scenario` (`scenario`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $checkStmt = $db->prepare('SELECT COUNT(*) FROM lc_email_templates WHERE (template_code = :key OR scenario = :key) AND status = "Active"');
        $checkStmt->execute([':key' => $notificationKey]);
        $hasTemplate = (int) $checkStmt->fetchColumn() > 0;

        if (!$hasTemplate && $notificationKey === 'document_reminder') {
            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'document_reminder\', \'document_reminder\', \'subject\', 1, \'Reminder: {document_name} - Action Required\', \'Active\')')->execute();
            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'document_reminder\', \'document_reminder\', \'body\', 2, \'Dear {employee_name},\n\nThis is a friendly reminder regarding your document: {document_name}.\n\nPlease review the details and take the necessary action at your earliest convenience. If you have any questions or need assistance, feel free to reach out to the HR department.\n\nDocument Details:\n- Document Name: {document_name}\n- Employee: {employee_name}\n- Department: {department}\n- Position: {position}\n- Issued Date: {issued_date}\n\nThank you for your attention to this matter.\n\nBest regards,\nHR Department\', \'Active\')')->execute();
        }

        if (!$hasTemplate && $notificationKey === 'risk_assessment_notification') {
            $riskSubject = 'Risk Assessment Notification: ' . ($_GET['risk_category'] ?? 'Workplace') . ' - ' . ($_GET['risk_severity'] ?? 'General');
            $riskBody = "Please refer to the risk detected and needs prompt resolution:\n\n";
            $riskBody .= ($_GET['risk_description'] ?? '') . "\n\n";
            $riskBody .= "Risk Category: " . ($_GET['risk_category'] ?? 'Workplace Accident') . "\n";
            $riskBody .= "Severity: " . ($_GET['risk_severity'] ?? 'Critical') . "\n";
            $riskBody .= "Mitigation Plan: " . ($_GET['mitigation_plan'] ?? '') . "\n\n";
            $riskBody .= "Best regards,\nHR Department";

            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'risk_assessment_notification\', \'risk_assessment_notification\', \'subject\', 1, :subject, \'Active\')')->execute([':subject' => $riskSubject]);
            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'risk_assessment_notification\', \'risk_assessment_notification\', \'body\', 2, :body, \'Active\')')->execute([':body' => $riskBody]);
        }

        if (!$hasTemplate && $notificationKey === 'policy_reminder') {
            $policySubject = 'Reminder: Policy Acknowledgement Required - {policy_title}';
            $policyBody = "Dear {employee_name},\n\nThis is a friendly reminder that you have a pending policy acknowledgement requiring your attention.\n\nPolicy Details:\n- Policy Code: {policy_code}\n- Policy Title: {policy_title}\n- Version: {policy_version}\n- Effective Date: {policy_effective_date}\n- Acknowledgement Deadline: {policy_ack_deadline}\n\nPlease review the policy and submit your acknowledgement at your earliest convenience. If you have any questions or need assistance, feel free to reach out to the HR department.\n\nBest regards,\nHR Department";

            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'policy_reminder\', \'policy_reminder\', \'subject\', 1, :subject, \'Active\')')->execute([':subject' => $policySubject]);
            $db->prepare('INSERT INTO lc_email_templates (template_code, scenario, component, component_order, template_text, status) VALUES (\'policy_reminder\', \'policy_reminder\', \'body\', 2, :body, \'Active\')')->execute([':body' => $policyBody]);
        }
    } catch (Throwable $e) {
    }

    $tplSubject = '';
    $tplBody = '';

    try {
        $stmt = $db->prepare('SELECT component, template_text FROM lc_email_templates WHERE (template_code = :key OR scenario = :key) AND status = "Active" ORDER BY component_order ASC');
        $stmt->execute([':key' => $notificationKey]);
        $tplSubject = '';
        $tplBody = '';
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['component'] === 'subject' && $tplSubject === '') $tplSubject = $row['template_text'];
            if ($row['component'] === 'body' && $tplBody === '') $tplBody = $row['template_text'];
        }

        $explicitBody = trim((string) ($_GET['body'] ?? ''));
        if ($explicitBody === '' && $tplBody !== '') {
            $composeBody = $tplBody;
        }
        if ($defaultSubject === '' && $tplSubject !== '') {
            $defaultSubject = $tplSubject;
        }
    } catch (Throwable $e) {
    }
}

$requestedTemplateCode = trim((string) ($_GET['template_code'] ?? ''));

$docTypeMap = [
    'coe' => 'coe',
    'employment_contract' => 'employment_contract',
    'contract_renewal' => 'contract_renewal',
    'contract_extension' => 'contract_extension',
    'salary_rectification' => 'salary_rectification',
    'leave_agreement' => 'leave_agreement',
    'study_leave' => 'study_leave',
    'suspension_notice' => 'suspension_notice',
    'notice_of_decision' => 'notice_of_decision',
    'termination_decision' => 'termination_decision',
    'nda' => 'nda',
    'clearance_survey' => 'clearance_survey',
    'training_bond' => 'training_bond',
    'non_compete' => 'non_compete',
    'written_warning' => 'written_warning',
    'nte' => 'nte',
    'exit_clearance' => 'exit_clearance',
    'exit_acknowledgement' => 'exit_acknowledgement',
    'quitclaim' => 'quitclaim',
    'return_service' => 'return_to_work_agreement',
    'employee_handbook' => 'employee_handbook',
];

$attachmentNameMap = [
    'coe' => 'COE',
    'employment_contract' => 'Employment_Contract',
    'contract_renewal' => 'Contract_Renewal',
    'contract_extension' => 'Contract_Extension',
    'salary_rectification' => 'Salary_Rectification_Agreement',
    'leave_agreement' => 'Leave_Agreement',
    'study_leave' => 'Study_Leave_Agreement',
    'suspension_notice' => 'Suspension_Notice',
    'notice_of_decision' => 'Notice_of_Decision',
    'termination_decision' => 'Termination_Decision',
    'nda' => 'NDA',
    'clearance_survey' => 'Clearance_Survey',
    'training_bond' => 'Training_Bond',
    'non_compete' => 'Non_Compete_Agreement',
    'written_warning' => 'Written_Warning',
    'nte' => 'NTE',
    'exit_clearance' => 'Exit_Clearance',
    'exit_acknowledgement' => 'Exit_Acknowledgement',
    'quitclaim' => 'Quitclaim',
    'return_service' => 'Return_to_Work_Agreement',
    'employee_handbook' => 'Employee_Handbook',
];

$composeEmployeeId = '';

if ($composeRecipientEmail !== '' && filter_var($composeRecipientEmail, FILTER_VALIDATE_EMAIL)) {
    $stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $composeRecipientEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $composeEmployeeId = (string) $row['employee_id'];
    }
}

if ($composeEmployeeId === '' && $composeRecipientName !== '') {
    $nameParts = explode(' ', $composeRecipientName);
    $firstName = array_shift($nameParts);
    $lastName = implode(' ', $nameParts);
    $stmt = $db->prepare('SELECT employee_id FROM em_employees WHERE first_name = :first_name AND last_name = :last_name LIMIT 1');
    $stmt->execute([':first_name' => $firstName, ':last_name' => $lastName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $composeEmployeeId = (string) $row['employee_id'];
    }
}

if ($composeEmployeeId === '' && !empty($_GET['employee_id'])) {
    $composeEmployeeId = (string) $_GET['employee_id'];
}

if ($requestedTemplateCode !== '' && $composeEmployeeId !== '') {
    $documentType = $docTypeMap[$requestedTemplateCode] ?? $requestedTemplateCode;
    $templateFile = $requestedTemplateCode . '.php';
    $friendlyDocName = $attachmentNameMap[$requestedTemplateCode] ?? ucfirst(str_replace('_', ' ', $documentType));

    if ($attachmentUrl === '') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $attachmentUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/generate-document.php?employee_id=' . urlencode($composeEmployeeId) . '&document_type=' . urlencode($documentType) . '&template=' . urlencode($templateFile) . '&template_code=' . urlencode($requestedTemplateCode) . '&hr_signatory=' . urlencode($_GET['hr_signatory'] ?? '') . '&generate=1';

        if (in_array($requestedTemplateCode, ['employment_contract', 'contract_renewal', 'contract_extension'], true)) {
            $attachmentUrl .= '&contract_type=' . urlencode((string) ($_GET['contract_type'] ?? 'Regular'));
            $attachmentUrl .= '&contract_start_date=' . urlencode((string) ($_GET['contract_start_date'] ?? date('Y-m-d')));
            $attachmentUrl .= '&contract_end_date=' . urlencode((string) ($_GET['contract_end_date'] ?? date('Y-m-d', strtotime('+1 year'))));
            $attachmentUrl .= '&contract_salary_input=' . urlencode((string) ($_GET['contract_salary_input'] ?? ''));
        }

        if ($requestedTemplateCode === 'nte') {
            $attachmentUrl .= '&incident_date=' . urlencode((string) ($_GET['incident_date'] ?? ''));
            $attachmentUrl .= '&incident_time=' . urlencode((string) ($_GET['incident_time'] ?? ''));
            $attachmentUrl .= '&incident_location=' . urlencode((string) ($_GET['incident_location'] ?? ''));
            $attachmentUrl .= '&policy_violated=' . urlencode((string) ($_GET['policy_violated'] ?? ''));
            $attachmentUrl .= '&incident_description=' . urlencode((string) ($_GET['incident_description'] ?? ''));
        }

        $attachmentName = $friendlyDocName . '_' . preg_replace('/[^A-Za-z0-9]/', '', $composeRecipientName ?: 'Employee') . '.pdf';
    }

    if ($composeBody === '' || str_starts_with($composeBody, 'Dear ')) {
        $composeBody = "Dear " . ($composeRecipientName ?: 'Employee') . ",\n\n";
        $composeBody .= "Please find attached the " . $friendlyDocName . " for your review and signature.\n\n";
        $composeBody .= "This document contains the relevant terms and details pertaining to your employment and compliance with company policies. Kindly review the document carefully. If you have any questions or require clarification on any of the terms, please do not hesitate to contact the Human Resources Department.\n\n";
        $composeBody .= "Best regards,\n";
        $composeBody .= "HR Department\n";
        $composeBody .= "Bestlink College of the Philippines\n";
    }
}

if ($requestedTemplateCode === 'nte' && $composeEmployeeId !== '') {
    $employeeData = ['full_name' => '', 'employee_no' => '', 'department_name' => '', 'position_name' => ''];
    try {
        $stmt = $db->prepare('SELECT e.*, COALESCE(d.department_name, \'N/A\') AS department_name, COALESCE(p.position_name, \'N/A\') AS position_name FROM em_employees e LEFT JOIN em_departments d ON d.department_id = e.department_id LEFT JOIN em_positions p ON p.position_id = e.position_id WHERE e.employee_id = :id LIMIT 1');
        $stmt->execute([':id' => $composeEmployeeId]);
        $empRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($empRow) {
            $employeeData['full_name'] = trim((string) ($empRow['full_name'] ?? ''));
            $employeeData['employee_no'] = (string) ($empRow['employee_code'] ?? '');
            $employeeData['department_name'] = (string) ($empRow['department_name'] ?? '');
            $employeeData['position_name'] = (string) ($empRow['position_name'] ?? '');
        }
    } catch (Throwable $e) {
    }

    $incidentDate = trim((string) ($_GET['incident_date'] ?? ''));
    $incidentTime = trim((string) ($_GET['incident_time'] ?? ''));
    $incidentLocation = trim((string) ($_GET['incident_location'] ?? ''));
    $incidentDescription = trim((string) ($_GET['incident_description'] ?? ''));
    $policyViolated = trim((string) ($_GET['policy_violated'] ?? ''));
    $today = date('F d, Y');
    $fullName = $employeeData['full_name'] ?: $composeRecipientName;

    $composeBody = "NOTICE TO EXPLAIN (NTE)\n";
    $composeBody .= "Administrative Due Process Notice Requiring Written Explanation\n\n";
    $composeBody .= "Date Issued: {$today}\n";
    $composeBody .= "Employee Name: {$fullName}\n";
    $composeBody .= "Employee Number: " . ($employeeData['employee_no'] ?: '________________') . "\n";
    $composeBody .= "Department: " . ($employeeData['department_name'] ?: '________________') . "\n";
    $composeBody .= "Position: " . ($employeeData['position_name'] ?: '________________') . "\n\n";
    $composeBody .= "Dear {$fullName},\n\n";
    $composeBody .= "This Notice to Explain (NTE) is issued in accordance with the Company's Code of Conduct, disciplinary procedures, the Philippine Labor Code, applicable Department of Labor and Employment (DOLE) regulations, and the principles of administrative due process.\n\n";
    $composeBody .= "You are hereby directed to submit a written explanation regarding the alleged act, omission, incident, or policy violation described below. This notice is issued to provide you with a fair and reasonable opportunity to present your side before any administrative action or decision is made.\n\n";
    $composeBody .= "Details of the Alleged Incident\n\n";
    $composeBody .= "Date of Incident: " . ($incidentDate ?: '______________________________________________') . "\n";
    $composeBody .= "Time: " . ($incidentTime ?: '______________________________________________') . "\n";
    $composeBody .= "Location: " . ($incidentLocation ?: '______________________________________________') . "\n";
    $composeBody .= "Incident Description: " . ($incidentDescription ?: '______________________________________________') . "\n";
    $composeBody .= "Policy / Rule Violated: " . ($policyViolated ?: '[Not specified]') . "\n\n";
    $composeBody .= "You are directed to submit your written explanation together with any supporting documents, evidence, or witness statements within the period prescribed by the Human Resources Department.\n\n";
    $composeBody .= "Failure to submit your explanation within the prescribed period, without a valid reason, may be considered a waiver of your opportunity to explain. The Company may proceed with the administrative evaluation based on the available records and evidence.\n\n";
    $composeBody .= "Please be advised that the issuance of this Notice does not constitute a finding of guilt nor the imposition of disciplinary action. It is issued solely to ensure compliance with the requirements of administrative due process and to provide you with an opportunity to be heard.\n\n";
    $composeBody .= "Please refer to the attached Notice to Explain (NTE) document for the complete details, formal record, and further instructions related to this notice.\n\n";
    $composeBody .= "Regards,\n";
    $composeBody .= ($_GET['hr_signatory'] ?? '') . "\n";
    $composeBody .= "HR Directress\n\n";
    $composeBody .= "---\n";
    $composeBody .= "This is a system-generated NTE for academic demonstration purposes.\n";

    if ($attachmentUrl === '' && $composeEmployeeId !== '') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $attachmentUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/generate-document.php?employee_id=' . urlencode($composeEmployeeId) . '&document_type=nte&template=nte.php&template_code=nte&hr_signatory=' . urlencode($_GET['hr_signatory'] ?? '') . '&incident_date=' . urlencode($_GET['incident_date'] ?? '') . '&incident_time=' . urlencode($_GET['incident_time'] ?? '') . '&incident_location=' . urlencode($_GET['incident_location'] ?? '') . '&policy_violated=' . urlencode($_GET['policy_violated'] ?? '') . '&incident_description=' . urlencode($_GET['incident_description'] ?? '') . '&generate=1';
        $attachmentName = 'NTE_' . preg_replace('/[^A-Za-z0-9]/', '', $fullName) . '.pdf';
    }
}

$hasForwardAttachment = $attachmentContractId > 0 || $attachmentUrl !== '';

if ($mode === 'new' && $documentContext !== []) {
    $defaultSubject = str_replace(
        array_map(function ($k) { return '{' . $k . '}'; }, array_keys($documentContext)),
        array_values($documentContext),
        $defaultSubject
    );
    $composeBody = str_replace(
        array_map(function ($k) { return '{' . $k . '}'; }, array_keys($documentContext)),
        array_values($documentContext),
        $composeBody
    );
}

if ($mode === 'new' && $notificationKey === 'risk_assessment_notification') {
    $riskContext = [
        'risk_category'    => (string) ($_GET['risk_category'] ?? 'Workplace Accident'),
        'risk_severity'    => (string) ($_GET['risk_severity'] ?? 'Critical'),
        'risk_description' => (string) ($_GET['risk_description'] ?? ''),
        'mitigation_plan'  => (string) ($_GET['mitigation_plan'] ?? ''),
        'employee_name'    => $composeRecipientName ?: 'Employee',
        'employee_no'      => $resolvedRecipientNo ?: 'N/A',
    ];

    $defaultSubject = str_replace(
        array_map(function ($k) { return '{' . $k . '}'; }, array_keys($riskContext)),
        array_values($riskContext),
        $defaultSubject
    );
    $composeBody = str_replace(
        array_map(function ($k) { return '{' . $k . '}'; }, array_keys($riskContext)),
        array_values($riskContext),
        $composeBody
    );
}

if ($notificationKey === 'policy_reminder') {
    $policyId = isset($_GET['policy_id']) ? (int) $_GET['policy_id'] : 0;
    $policyContext = [
        'policy_code' => '',
        'policy_title' => '',
        'policy_version' => '',
        'policy_effective_date' => '',
        'policy_ack_deadline' => '',
        'employee_name' => $composeRecipientName ?: 'Employee',
        'employee_no' => $resolvedRecipientNo ?: 'N/A',
    ];

    if ($policyId > 0) {
        try {
            $stmt = $db->prepare('SELECT policy_code, title, version, effective_date, acknowledgement_deadline FROM lc_policies WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $policyId]);
            $pRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pRow) {
                $policyContext['policy_code'] = (string) ($pRow['policy_code'] ?? '');
                $policyContext['policy_title'] = (string) ($pRow['title'] ?? '');
                $policyContext['policy_version'] = (string) ($pRow['version'] ?? '');
                $policyContext['policy_effective_date'] = !empty($pRow['effective_date']) ? date('F d, Y', strtotime($pRow['effective_date'])) : '';
                $policyContext['policy_ack_deadline'] = !empty($pRow['acknowledgement_deadline']) ? date('F d, Y', strtotime($pRow['acknowledgement_deadline'])) : '';
            }
        } catch (Throwable $e) {
        }
    }

    if ($tplSubject !== '') {
        $defaultSubject = str_replace(
            array_map(function ($k) { return '{' . $k . '}'; }, array_keys($policyContext)),
            array_values($policyContext),
            $tplSubject
        );
    }
    $composeBody = str_replace(
        array_map(function ($k) { return '{' . $k . '}'; }, array_keys($policyContext)),
        array_values($policyContext),
        $composeBody
    );
}

$sendToMany = true;
$replySenderEmailAllowed = true;

$webBase = '/hrms-capstone/modules/compliance/';

?>
<section class="cc-module">
  <div class="nc-grid-2">

    <div class="nc-compose-wrap">
      <div class="nc-step">
        <div class="nc-step-header">
          <span class="nc-step-number">1</span>
          <span class="nc-step-label">Sender Information</span>
          <div class="nc-toolbar-right">
            <a href="/hrms-capstone/modules/compliance/index.php?page=sent-history" class="nc-toolbar-btn">
              <i class="bi bi-clock-history"></i> Sent History
            </a>
          </div>
        </div>
        <div class="nc-step-body nc-step-body--info">
          <div class="nc-info-card">
            <div class="nc-info-card-icon"><i class="bi bi-person"></i></div>
            <div class="nc-info-card-body">
              <div class="nc-info-card-label">Sender</div>
              <div class="nc-info-card-name"><?php echo htmlspecialchars($senderInfoName); ?></div>
              <?php if ($senderInfoEmail !== ''): ?>
              <div class="nc-info-card-email"><?php echo htmlspecialchars($senderInfoEmail); ?></div>
              <?php endif; ?>
              <div class="nc-info-card-email"><?php echo htmlspecialchars($senderInfoRole); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="nc-step">
        <div class="nc-step-header">
          <span class="nc-step-number">2</span>
          <span class="nc-step-label">Recipients</span>
        </div>
        <div class="nc-step-body">
          <div class="nc-recipient-chips" id="ncRecipientChips">
            <button type="button" class="nc-add-chip-btn" id="ncAddChipBtn">
              <i class="bi bi-plus-lg"></i> Add Recipient
            </button>
          </div>

          <div class="nc-search-icon-wrap" id="ncSearchWrap" style="display:none;">
            <i class="bi bi-search"></i>
            <input type="text" id="ncRecipientSearch" class="nc-recipient-search-input" placeholder="Search by name, ID, or email..." autocomplete="off" />
          </div>

          <input type="hidden" name="to_recipients" id="toRecipients" value="" />

          <div class="nc-recipient-results" id="recipientResults" role="listbox" aria-label="Employee search results"></div>

          <div class="nc-compose-hint">
            <?php echo $sendToMany ? 'Multiple recipients allowed.' : 'Single recipient only.'; ?>
          </div>

          <div class="nc-validation" id="recipientValidation" style="display:none;">Please select at least one recipient.</div>
        </div>
      </div>

      <div class="nc-step">
        <div class="nc-step-header">
          <span class="nc-step-number">3</span>
          <span class="nc-step-label">Email Details</span>
        </div>
        <div class="nc-step-body nc-step-body--details">
          <div>
            <label class="nc-form-label" for="subject">Subject</label>
            <input class="nc-form-control" type="text" name="subject" id="subject" value="<?php echo htmlspecialchars($defaultSubject); ?>" placeholder="Enter subject..." />
          </div>
          <div>
            <label class="nc-form-label" for="body">Message</label>
            <textarea class="nc-form-control" name="body" id="body" rows="8" placeholder="Write your message..."><?php echo htmlspecialchars($composeBody); ?></textarea>
          </div>
        </div>
      </div>

      <div class="nc-step">
        <div class="nc-step-header">
          <span class="nc-step-number">4</span>
          <span class="nc-step-label">Attachments</span>
        </div>
        <div class="nc-step-body nc-step-body--atts">
          <div class="nc-attachment-dropzone" id="ncAttachmentDropzone">
            <i class="bi bi-cloud-arrow-up"></i>
            <div class="nc-attachment-dropzone-text">Drop files here or <strong>browse files</strong></div>
            <div class="nc-attachment-dropzone-sub">PDF, DOCX, PNG, JPG — max 5MB each, up to 3 files</div>
            <div class="nc-attachment-types">
              <span class="nc-attachment-type-badge"><i class="bi bi-filetype-pdf"></i> PDF</span>
              <span class="nc-attachment-type-badge"><i class="bi bi-filetype-docx"></i> DOCX</span>
              <span class="nc-attachment-type-badge"><i class="bi bi-filetype-png"></i> PNG</span>
              <span class="nc-attachment-type-badge"><i class="bi bi-filetype-jpg"></i> JPG</span>
            </div>
            <input type="file" id="lcAttachmentInput" style="display:none;" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif" />
          </div>
          <div class="nc-attachment-list" id="lcAttachmentList"></div>

          <?php if ($hasForwardAttachment): ?>
          <div class="nc-forwarded-chip" id="lcAttachmentChip">
            <i class="bi bi-paperclip"></i>
            <span class="nc-attachment-name"><?php echo htmlspecialchars($attachmentName ?: 'contract.pdf'); ?></span>
            <span class="nc-forwarded-note">Forwarded from original notification</span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($notificationId > 0 && ($notifMessage !== '' || $notifType !== '' || $notificationKey !== '')): ?>
      <div class="nc-step">
        <div class="nc-step-header">
          <span class="nc-step-number"><i class="bi bi-info-lg"></i></span>
          <span class="nc-step-label">Original Notification</span>
        </div>
        <div class="nc-step-body">
          <button type="button" class="nc-original-toggle" id="ncOriginalToggle">
            <i class="bi bi-chevron-right"></i> Show original notification details
          </button>
          <div class="nc-original-content" id="ncOriginalContent">
            <div class="nc-original-card">
              <div class="nc-preview-row">
                <span class="nc-preview-label">Key</span>
                <span class="nc-preview-value"><?php echo htmlspecialchars($notificationKey); ?></span>
              </div>
              <?php if ($notifType !== ''): ?>
              <div class="nc-preview-row">
                <span class="nc-preview-label">Type</span>
                <span class="nc-preview-value"><?php echo htmlspecialchars($notifType); ?></span>
              </div>
              <?php endif; ?>
              <?php if ($notificationId > 0): ?>
              <div class="nc-preview-row">
                <span class="nc-preview-label">ID</span>
                <span class="nc-preview-value">#<?php echo (int) $notificationId; ?></span>
              </div>
              <?php endif; ?>
              <?php if ($notifMessage !== ''): ?>
              <div class="nc-original-message">
                <?php echo nl2br(htmlspecialchars($notifMessage)); ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="nc-sticky-footer">
        <div class="nc-footer-left">
          <i class="bi bi-check-circle nc-footer-icon--success"></i>
          <span id="ncDraftStatus">Ready to send</span>
        </div>
        <div class="nc-footer-right">
          <button type="button" class="nc-btn nc-btn-ghost" onclick="window.location.href='<?= !empty($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8') : 'javascript:history.back()'; ?>'">
            <i class="bi bi-x-lg"></i> Cancel
          </button>
          <button type="button" class="nc-btn nc-btn-primary" onclick="window.ncSubmitCompose()">
            <i class="bi bi-send"></i> Send
          </button>
        </div>
      </div>

    </div>

    <aside class="nc-preview-sidebar">
      <div class="nc-preview-header">
        <i class="bi bi-eye"></i> Email Preview
        <span class="nc-preview-hint">Live preview</span>
      </div>
      <div class="nc-preview-body" id="ncPreviewBody">
      </div>
    </aside>

  </div>
</section>

<script>
window.__ncConfig = {
  sendToMany: <?php echo $sendToMany ? 'true' : 'false'; ?>,
  webBase: '<?php echo addslashes($webBase); ?>',
   composeMode: '<?php echo addslashes($mode); ?>',
   notificationId: <?php echo (int) $notificationId; ?>,
   notificationKey: '<?php echo addslashes($notificationKey); ?>',
   templateCode: '<?php echo addslashes($requestedTemplateCode); ?>',
   documentType: '<?php echo addslashes($docTypeMap[$requestedTemplateCode] ?? ''); ?>',
  replySenderEmail: '<?php echo addslashes($origSenderEmail); ?>',
  origSenderName: '<?php echo addslashes($origSenderName); ?>',
  originalSubject: <?php echo json_encode($origSubject); ?>,
  originalMessage: <?php echo json_encode($origMessage); ?>,
  attachmentContractId: <?php echo (int) $attachmentContractId; ?>,
  attachmentName: '<?php echo addslashes($attachmentName); ?>',
  attachmentUrl: '<?php echo addslashes($attachmentUrl); ?>',
   composeBody: <?php echo json_encode($composeBody); ?>,
    preselectedName: '<?php echo addslashes($composeRecipientName); ?>',
    preselectedEmail: '<?php echo addslashes($composeRecipientEmail); ?>',
    composeEmployeeId: '<?php echo addslashes($composeEmployeeId); ?>',
   contractId: <?php echo (int) $contractId; ?>,
   redirectTo: '<?php echo addslashes($redirectTo); ?>',
   contractSalaryInput: '<?php echo addslashes((string) ($_GET['contract_salary_input'] ?? '')); ?>',
    companyEmail: '<?php echo addslashes((string) ($emailConfig['company_email'] ?? 'hr@bestlink.edu.ph')); ?>',
  companyWebsite: '<?php echo addslashes((string) ($emailConfig['company_website'] ?? 'www.bestlinkcollege.edu.ph')); ?>',
  companyAddress: '<?php echo addslashes((string) ($emailConfig['company_address'] ?? 'Quirino Highway, Brgy. Minuyan Proper, City of San Jose del Monte, Bulacan')); ?>'
};
</script>


