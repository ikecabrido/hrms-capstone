<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../lib/ajax/document_template_helper.php';

$pageTitle = 'Employee Handbook';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
if (!$db instanceof PDO) {
    throw new RuntimeException('Unable to establish a database connection.');
}

$employeeId = 0;
$isLoggedInEmployee = false;
$isLegalUser = false;
$ackStatus = null;
$templateId = 0;
$version = '1.0';
$handbookContent = '';
$effectiveDate = '';
$message = '';
$messageType = '';

if (function_exists('hm_is_legal_role')) {
    $isLegalUser = hm_is_legal_role();
}

if (!empty($_SESSION['employee_id'])) {
    $employeeId = (int) $_SESSION['employee_id'];
    $isLoggedInEmployee = true;
}

$templateRecord = lc_get_active_template($db, 'employee_handbook');
if ($templateRecord) {
    $handbookContent = $templateRecord['template_content'] ?? '';
    $version = $templateRecord['version'] ?? '1.0';
    $effectiveDate = $templateRecord['effective_date'] ?? '';
    $templateId = (int) ($templateRecord['template_id'] ?? 0);
}

if (empty($handbookContent)) {
    $handbookContent = lc_get_fallback_template('employee_handbook');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLegalUser) {
    if (isset($_POST['save_handbook'])) {
        $content = trim((string) ($_POST['handbook_content'] ?? ''));
        $newVersion = trim((string) ($_POST['version'] ?? '1.0'));
        $newEffectiveDate = trim((string) ($_POST['effective_date'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'Active'));

        if ($content === '') {
            $message = 'Handbook content cannot be empty.';
            $messageType = 'error';
        } else {
            try {
                if (!empty($templateRecord['template_id'])) {
                    $db->prepare("
                        UPDATE lc_document_templates
                        SET template_content = :content, version = :version, effective_date = :ed, status = :status, updated_at = NOW()
                        WHERE template_id = :id
                    ")->execute([
                        ':content' => $content,
                        ':version' => $newVersion,
                        ':ed' => $newEffectiveDate ?: null,
                        ':status' => $status,
                        ':id' => $templateRecord['template_id'],
                    ]);
                } else {
                    $db->prepare("
                        INSERT INTO lc_document_templates
                            (template_code, template_name, version, status, template_content, effective_date, created_by_role)
                        VALUES
                            (:code, :name, :version, :status, :content, :ed, 'legal')
                    ")->execute([
                        ':code' => 'employee_handbook',
                        ':name' => 'Employee Handbook',
                        ':version' => $newVersion,
                        ':status' => $status,
                        ':content' => $content,
                        ':ed' => $newEffectiveDate ?: null,
                    ]);
                }
                $message = 'Handbook saved successfully.';
                $messageType = 'success';
                $templateRecord = lc_get_active_template($db, 'employee_handbook');
                $handbookContent = $templateRecord['template_content'] ?? '';
                $version = $templateRecord['version'] ?? '1.0';
                $effectiveDate = $templateRecord['effective_date'] ?? '';
                $templateId = (int) ($templateRecord['template_id'] ?? 0);
            } catch (Throwable $e) {
                $message = 'Failed to save: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['send_handbook'])) {
        $targetEmployeeId = (int) ($_POST['target_employee_id'] ?? 0);
        if ($targetEmployeeId > 0) {
            try {
                $employer = lc_get_active_employer($db);
                $employerName = (string) ($employer['name'] ?? 'Bestlink College of the Philippines');

                $db->prepare("
                    INSERT INTO lc_document_requests
                        (employee_id, rao_hired_id, document_type, request_status, priority, notes, requires_signature, signature_status, template_code)
                    VALUES
                        (:employee_id, :rao_hired_id, :document_type, 'Pending', 'Medium', NULL, 1, 'none', 'employee_handbook')
                ")->execute([
                    ':employee_id' => $targetEmployeeId,
                    ':rao_hired_id' => null,
                    ':document_type' => 'Employee Handbook',
                ]);

                $empRow = $db->prepare("
                    SELECT first_name, middle_name, last_name, employee_code, email
                    FROM em_employees
                    WHERE employee_id = :id
                    LIMIT 1
                ");
                $empRow->execute([':id' => $targetEmployeeId]);
                $recipient = $empRow->fetch(PDO::FETCH_ASSOC);

                $senderIdSession = (int) ($_SESSION['employee_id'] ?? 0);
                $senderRow = $db->prepare('SELECT first_name, last_name, email FROM em_employees WHERE employee_id = :id LIMIT 1');
                $senderRow->execute([':id' => $senderIdSession]);
                $sender = $senderRow->fetch(PDO::FETCH_ASSOC);

                $recipientName = 'Employee';
                if ($recipient) {
                    $recipientName = trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['middle_name'] ?? '') . ' ' . ($recipient['last_name'] ?? ''));
                }

                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $protocol . $host . '/hrms-capstone';
                $ackUrl = $baseUrl . '/modules/compliance/pages/handbook-acknowledge.php';

                $emailSubject = 'Action Required: Employee Handbook Acknowledgement';
                $employeeNo = (string) ($recipient['employee_code'] ?? 'EMP-' . str_pad((string) $targetEmployeeId, 6, '0', STR_PAD_LEFT));
                $departmentName = (string) ($recipient['department_name'] ?? 'N/A');
                $positionName = (string) ($recipient['position_name'] ?? 'N/A');
                $effectiveDateFormatted = !empty($effectiveDate) ? date('F d, Y', strtotime($effectiveDate)) : 'N/A';
                $previewUrl = $baseUrl . '/modules/compliance/?page=preview-document&template_code=employee_handbook&mode=preview';

                $emailBody = "Dear {$recipientName},\n\n";
                $emailBody .= "Greetings!\n\n";
                $emailBody .= "We are pleased to provide you with the latest version of the **Employee Handbook of {$employerName}**.\n\n";
                $emailBody .= "The handbook contains important information about our workplace policies, procedures, standards, employee responsibilities, and other guidelines that help promote a professional, respectful, and productive work environment.\n\n";
                $emailBody .= "**Handbook Details**\n\n";
                $emailBody .= "* **Version:** {$version}\n";
                $emailBody .= "* **Effective Date:** {$effectiveDateFormatted}\n";
                $emailBody .= "* **Employee No.:** {$employeeNo}\n";
                $emailBody .= "* **Department:** {$departmentName}\n";
                $emailBody .= "* **Position:** {$positionName}\n\n";
                $emailBody .= "Please take time to carefully read and familiarize yourself with the contents of the handbook. The policies and guidelines contained therein are intended to help you understand your responsibilities and the standards expected of all employees.\n\n";
                $emailBody .= "**Please review the handbook here:**\n\n";
                $emailBody .= $previewUrl . "\n\n";
                $emailBody .= "After reviewing the handbook, please complete the required acknowledgement using the link below:\n\n";
                $emailBody .= "**Acknowledge Employee Handbook:**\n";
                $emailBody .= $ackUrl . "\n\n";
                $emailBody .= "Your acknowledgement confirms that you have received and reviewed the Employee Handbook and understand that you are responsible for becoming familiar with and complying with the applicable company policies and guidelines.\n\n";
                $emailBody .= "If you have any questions or need clarification regarding any provision in the handbook, please coordinate with the Human Resources Department.\n\n";
                $emailBody .= "Thank you for your cooperation and commitment to maintaining a professional workplace.\n\n";
                $emailBody .= "Sincerely,\n\n";
                $emailBody .= "**Human Resources Department**\n";
                $emailBody .= $employerName;

                $emailBody = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $emailBody);

                $htmlTemplatePath = __DIR__ . '/templates/employee_handbook/email-template.html';
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
                            '{{preview_url}}',
                            '{{company_name}}',
                            '{{company_address}}',
                            '{{company_email}}',
                            '{{company_website}}',
                            '{{date}}',
                            '{{time}}',
                            '{{year}}',
                        ],
                        [
                            htmlspecialchars($emailSubject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($employeeNo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($departmentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($positionName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($version, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($effectiveDateFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            nl2br($emailBody),
                            htmlspecialchars($ackUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($previewUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars($employerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars((string) ($employer['address'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars((string) ($employer['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars((string) ($employer['website'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars(date('F j, Y'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            htmlspecialchars(date('g:i A'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                            (int) date('Y'),
                        ],
                        $html
                    );
                } else {
                    $html = \App\Services\EmailTemplate::buildHtml($emailSubject, $emailBody, $recipientName, 'Bestlink College of the Philippines', $baseUrl);
                }

                $altBody = \App\Services\EmailTemplate::buildText($emailSubject, $emailBody, $recipientName, 'Bestlink College of the Philippines');

                $mailer = \App\Services\EmailService::getInstance();
                $mail = $mailer->getMail();
                \App\Services\EmailTemplate::embedLogo($mail);
                \App\Services\EmailTemplate::embedSignatory($mail);

                $attachmentUrl = $baseUrl . '/modules/compliance/pages/generate-document.php?employee_id=' . urlencode((string) $targetEmployeeId) . '&document_type=employee_handbook&template=employee_handbook.php&template_code=employee_handbook&generate=1';
                $attachmentName = 'Employee_Handbook_v' . preg_replace('/[^A-Za-z0-9]/', '', $version) . '.pdf';

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

                if ($tempAttachment !== false && $tempAttachment !== null && is_file($tempAttachment)) {
                    try {
                        $mail->addAttachment($tempAttachment, $attachmentName);
                    } catch (Throwable $e) {
                        error_log('handbook-acknowledge: attachment error: ' . $e->getMessage());
                    }
                }

                $recipientEmail = (string) ($recipient['email'] ?? '');
                $emailSent = false;
                if ($recipientEmail !== '' && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                    $emailSent = $mailer->send(
                        ['email' => $recipientEmail, 'name' => $recipientName],
                        $emailSubject,
                        $html,
                        $altBody
                    );
                }

                if ($emailSent) {
                    $senderEmail = (string) ($sender['email'] ?? '');
                    $db->prepare("
                        INSERT INTO lc_notifications
                            (employee_id, title, message, type, module, email, sender_email, is_read, notification_type, created_at, updated_at)
                        VALUES
                            (:employee_id, :title, :message, :type, :module, :email, :sender_email, 0, 'email', NOW(), NOW())
                    ")->execute([
                        ':employee_id'   => $senderIdSession,
                        ':title'         => $emailSubject,
                        ':message'       => $emailBody,
                        ':type'          => 'employee_handbook_notification',
                        ':module'        => 'compliance',
                        ':email'         => $recipientEmail,
                        ':sender_email'  => $senderEmail,
                    ]);

                    $db->prepare("
                        INSERT INTO lc_sent_history
                            (employee_id, title, message, type, department, module, email, sender_email, is_read, created_at, updated_at)
                        VALUES
                            (:employee_id, :title, :message, :type, :department, :module, :email, :sender_email, 0, NOW(), NOW())
                    ")->execute([
                        ':employee_id'   => $senderIdSession,
                        ':title'         => $emailSubject,
                        ':message'       => $emailBody,
                        ':type'          => 'employee_handbook_notification',
                        ':department'    => null,
                        ':module'        => 'compliance',
                        ':email'         => $recipientEmail,
                        ':sender_email'  => $senderEmail,
                    ]);
                }

                $message = $emailSent ? 'Handbook sent to employee successfully.' : 'Handbook request saved, but email delivery failed.';
                $messageType = $emailSent ? 'success' : 'warning';
            } catch (Throwable $e) {
                $message = 'Failed to send handbook: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Please select an employee.';
            $messageType = 'error';
        }
    }
}

$employer = lc_get_active_employer($db);

$employeeData = [
    'full_name' => '',
    'employee_no' => '',
    'position_name' => '',
    'department_name' => '',
    'address' => '',
    'phone_number' => '',
    'email' => '',
    'birthdate' => '',
    'sex' => '',
    'marital_status' => '',
    'employment_status' => '',
    'nationality' => 'Filipino',
];

if ($isLoggedInEmployee && $employeeId > 0) {
    try {
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($emp) {
            $parts = array_filter([$emp['first_name'] ?? '', $emp['middle_name'] ?? '', $emp['last_name'] ?? '']);
            $employeeData['full_name'] = trim(implode(' ', $parts));
            $employeeData['employee_no'] = $emp['employee_code'] ?? '';
            $employeeData['position_name'] = $emp['position_name'] ?? '';
            $employeeData['department_name'] = $emp['department_name'] ?? '';
            $employeeData['address'] = $emp['current_address'] ?? '';
            $employeeData['phone_number'] = $emp['mobile_no'] ?? $emp['phone_no'] ?? '';
            $employeeData['email'] = $emp['email'] ?? '';
            $employeeData['birthdate'] = $emp['birth_date'] ?? '';
            $employeeData['sex'] = $emp['gender'] ?? '';
            $employeeData['marital_status'] = $emp['civil_status'] ?? '';
            $employeeData['employment_status'] = $emp['employment_status'] ?? '';
            $employeeData['nationality'] = $emp['nationality'] ?? 'Filipino';
        }
    } catch (Throwable $e) {
        $employeeData['full_name'] = 'Employee #' . $employeeId;
    }
}

if (empty($employeeData['full_name'])) {
    $employeeData['full_name'] = 'Employee #' . $employeeId;
}
if (empty($employeeData['employee_no'])) {
    $employeeData['employee_no'] = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
}

$renderedContent = lc_replace_placeholders($handbookContent, $employeeData, $employer);

if (!$isLegalUser) {
    $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLegalUser && isset($_POST['acknowledge'])) {
    $acknowledge = !empty($_POST['acknowledge']);
    if ($acknowledge && $templateId > 0) {
        $accountId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $success = lc_upsert_handbook_acknowledgement($db, $employeeId, $templateId, $version, 'Acknowledged', $accountId);
        $message = $success ? 'Handbook acknowledged successfully.' : 'Failed to record acknowledgement.';
        $messageType = $success ? 'success' : 'error';
        $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
    }
}

$empStmt = $db->prepare("SELECT first_name, middle_name, last_name, employee_code FROM em_employees WHERE employee_id = :id LIMIT 1");
$empStmt->execute([':id' => $employeeId]);
$emp = $empStmt->fetch(PDO::FETCH_ASSOC);
$employeeName = $emp ? trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) : 'Employee';

$allEmployees = [];
try {
    $stmt = $db->prepare("
        SELECT employee_id, first_name, middle_name, last_name, employee_code
        FROM em_employees
        ORDER BY first_name ASC, last_name ASC
    ");
    $stmt->execute();
    $allEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $allEmployees = [];
}

$previewUrl = '?page=preview-document&template_code=employee_handbook&mode=preview';

?>

<div class="module-content">
    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType === 'success' ? 'success' : 'danger') ?>" style="margin-bottom:16px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($isLegalUser): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0;">Employee Handbook</h2>
                <p style="margin:4px 0 0; color:var(--text-400,#8b93a1); font-size:.9rem;">
                    Version <?= htmlspecialchars($version) ?>
                    <?php if ($effectiveDate): ?> · Effective <?= date('F d, Y', strtotime($effectiveDate)) ?><?php endif; ?>
                </p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="dg-btn-generate" id="openSendModal">
                    <i class="bi bi-send"></i> Send to Employee
                </button>
            </div>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <div class="card-body">
                <form method="post" action="" style="display:flex; flex-direction:column; gap:14px;">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px;">
                        <div class="dg-field">
                            <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Version</label>
                            <input type="text" name="version" value="<?= htmlspecialchars($version) ?>" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem;">
                        </div>
                        <div class="dg-field">
                            <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Effective Date</label>
                            <input type="date" name="effective_date" value="<?= htmlspecialchars($effectiveDate) ?>" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem;">
                        </div>
                        <div class="dg-field">
                            <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Status</label>
                            <select name="status" style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem; background:#fff;">
                                <?php foreach (['Draft','Approved','Active','Inactive','Retired'] as $s): 
                                    $currentStatus = $templateRecord['status'] ?? 'Active';
                                ?>
                                    <option value="<?= htmlspecialchars($s) ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="dg-field">
                        <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1);">Handbook Content</label>
                        <textarea name="handbook_content" rows="20" style="padding:10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem; font-family:inherit; line-height:1.6; resize:vertical; width:100%;"><?= htmlspecialchars($handbookContent) ?></textarea>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="submit" name="save_handbook" value="1" class="dg-btn-generate">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="sendModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; border-radius:12px; padding:24px; max-width:520px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2);">
                <h3 style="margin:0 0 16px; font-size:1.1rem; font-weight:700;">Send Handbook</h3>
                <form method="post" action="" id="sendHandbookForm">
                    <div class="dg-field" style="margin-bottom:16px;">
                        <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1); display:block; margin-bottom:6px;">Search Employee</label>
                        <input type="text" id="employeeSearch" placeholder="Type to search employees..." style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem; width:100%; box-sizing:border-box;">
                    </div>
                    <div class="dg-field" style="margin-bottom:20px;">
                        <label style="font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-400,#8b93a1); display:block; margin-bottom:6px;">Employee</label>
                        <select name="target_employee_id" id="employeeSelect" required style="padding:8px 10px; border-radius:8px; border:1px solid var(--border,#e4e8ee); font-size:.85rem; width:100%; box-sizing:border-box; background:#fff;">
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($allEmployees as $emp): 
                                $fullName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
                            ?>
                                <option value="<?= (int) $emp['employee_id'] ?>"><?= htmlspecialchars($fullName . ' (' . ($emp['employee_code'] ?? '') . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="dg-btn-cancel" id="closeSendModal">Cancel</button>
                        <button type="submit" name="send_handbook" value="1" class="dg-btn-generate">
                            <i class="bi bi-send"></i> Send Handbook
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('sendModal');
            var openBtn = document.getElementById('openSendModal');
            var closeBtn = document.getElementById('closeSendModal');
            var searchInput = document.getElementById('employeeSearch');
            var select = document.getElementById('employeeSelect');

            function openModal() {
                modal.style.display = 'flex';
                if (searchInput) searchInput.value = '';
                if (select) select.value = '';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);

            if (searchInput && select) {
                searchInput.addEventListener('input', function() {
                    var term = this.value.toLowerCase();
                    var options = select.querySelectorAll('option');
                    for (var i = 1; i < options.length; i++) {
                        var text = options[i].text.toLowerCase();
                        options[i].style.display = text.indexOf(term) !== -1 ? '' : 'none';
                    }
                });
            }

            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal();
                });
            }
        });
        </script>

    <?php else: ?>
        <?php if (!$isLoggedInEmployee): ?>
            <div class="alert alert-warning">You must be logged in to acknowledge the handbook.</div>
        <?php else: ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h2 style="margin:0;">Employee Handbook</h2>
                    <p style="margin:4px 0 0; color:var(--text-400,#8b93a1); font-size:.9rem;">
                        Version <?= htmlspecialchars($version) ?>
                        <?php if ($effectiveDate): ?> · Effective <?= date('F d, Y', strtotime($effectiveDate)) ?><?php endif; ?>
                    </p>
                </div>
                <?php if ($ackStatus && $ackStatus['status'] === 'Acknowledged'): ?>
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:999px; background:rgba(47,158,110,.12); color:#1f7a52; font-weight:700; font-size:.85rem;">
                        <i class="bi bi-check-circle-fill"></i> Acknowledged
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType === 'success' ? 'success' : 'danger') ?>" style="margin-bottom:16px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$ackStatus || $ackStatus['status'] !== 'Acknowledged'): ?>
                <div class="card" style="margin-bottom:20px; border-left:4px solid #3b82c4;">
                    <div class="card-body">
                        <p style="margin:0; font-size:.9rem; line-height:1.6;">
                            Dear <strong><?= htmlspecialchars($employeeName) ?></strong>,<br><br>
                            Please review the Employee Handbook below. Once you have read and understood the policies, please check the acknowledgement box and submit.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="document-preview">
                <div class="document-header">
                    <h2 class="document-title">EMPLOYEE HANDBOOK</h2>
                    <p class="document-subtitle">Version <?= htmlspecialchars($version) ?>
                        <?php if ($effectiveDate): ?> · Effective <?= date('F d, Y', strtotime($effectiveDate)) ?><?php endif; ?>
                    </p>
                </div>
                <hr class="document-separator">
                <div class="document-body">
                    <?= $renderedContent ?>
                </div>
                <?php if (!$ackStatus || $ackStatus['status'] !== 'Acknowledged'): ?>
                    <div class="document-signature" style="margin-top:24px;">
                        <form method="post" action="" style="display:inline-flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <label style="display:flex; align-items:center; gap:8px; font-size:.85rem; cursor:pointer; background:#fff; padding:10px 16px; border-radius:8px; border:1px solid var(--border,#e4e8ee);">
                                <input type="checkbox" name="acknowledge" value="1" required style="width:16px; height:16px; accent-color:#1f7a52;">
                                I have read and understood the Employee Handbook and agree to abide by its policies.
                            </label>
                            <button type="submit" class="dg-btn-generate" style="padding:9px 18px; border-radius:8px; border:none; background:linear-gradient(135deg, #1f7a52 0%, #166534 100%); color:#fff; font-weight:700; cursor:pointer;">
                                <i class="bi bi-check-lg"></i> Acknowledge Handbook
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="document-signature" style="margin-top:24px;">
                        <div style="padding:14px 18px; border-radius:10px; background:rgba(47,158,110,.10); border:1px solid rgba(47,158,110,.25); display:inline-flex; align-items:center; gap:10px;">
                            <i class="bi bi-check-circle-fill" style="color:#1f7a52; font-size:1.2rem;"></i>
                            <div>
                                <div style="font-weight:700; color:#1f7a52;">Acknowledged</div>
                                <div style="font-size:.78rem; color:var(--text-600,#5a6779);">
                                    on <?= $ackStatus['acknowledged_at'] ? date('F d, Y h:i A', strtotime($ackStatus['acknowledged_at'])) : '—' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="document-disclaimer">
                    <strong>Academic Disclaimer</strong><br><br>
                    This Employee Handbook is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
