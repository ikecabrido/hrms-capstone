<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$templateCode = 'employee_handbook';
$templateRecord = lc_get_active_template($db, $templateCode);

$handbookContent = '';
$version = '1.0';
$effectiveDate = '';
$templateId = 0;

if ($templateRecord) {
    $handbookContent = $templateRecord['template_content'] ?? '';
    $version = $templateRecord['version'] ?? '1.0';
    $effectiveDate = $templateRecord['effective_date'] ?? '';
    $templateId = (int) ($templateRecord['template_id'] ?? 0);
}

if (empty($handbookContent)) {
    $handbookContent = lc_get_fallback_template('employee_handbook');
}

$employeeId = 0;
$isLoggedInEmployee = false;
$ackStatus = null;
$overrideEmployeeId = 0;
$isOnboardingMode = !empty($_GET['onboarding']);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!empty($_GET['override_employee_id'])) {
    $overrideEmployeeId = (int) $_GET['override_employee_id'];
}

if (!empty($_SESSION['employee_id'])) {
    $employeeId = (int) $_SESSION['employee_id'];
    $isLoggedInEmployee = true;

    if ($templateId > 0 && $employeeId > 0) {
        $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
        if (!$ackStatus) {
            $empStmt = $db->prepare("SELECT first_name, last_name FROM em_employees WHERE employee_id = :id LIMIT 1");
            $empStmt->execute([':id' => $employeeId]);
            $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
            if ($emp) {
                lc_upsert_handbook_acknowledgement($db, $employeeId, $templateId, $version, 'Viewed');
                $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
            }
        }
    }
}

if ($overrideEmployeeId > 0) {
    $employeeId = $overrideEmployeeId;
    $isLoggedInEmployee = false;
}

$action = $_GET['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedInEmployee && $templateId > 0 && $action === 'acknowledge_handbook') {
    $acknowledge = isset($_POST['acknowledge']) && $_POST['acknowledge'] === '1';
    if ($acknowledge) {
        $accountId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $success = lc_upsert_handbook_acknowledgement($db, $employeeId, $templateId, $version, 'Acknowledged', $accountId);
        $message = $success ? 'ack_success' : 'ack_error';
        $ackStatus = lc_get_handbook_acknowledgement($db, $employeeId, $templateId);
    }
}

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
} elseif ($isOnboardingMode && $overrideEmployeeId > 0) {
    try {
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON e.department_id = d.department_id
            LEFT JOIN em_positions p ON e.position_id = p.position_id
            WHERE e.employee_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $overrideEmployeeId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$emp) {
            $stmt = $db->prepare("
                SELECT e.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
                FROM new_hire_table e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions p ON p.position_id = e.position_id
                WHERE e.candidate_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $overrideEmployeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$emp) {
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON d.department_name = rh.department
                LEFT JOIN em_positions p ON p.position_name = rh.position
                WHERE rh.id = :id OR rh.application_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $overrideEmployeeId]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if ($emp) {
            $parts = array_filter([$emp['first_name'] ?? '', $emp['middle_name'] ?? '', $emp['last_name'] ?? '']);
            $employeeData['full_name'] = trim(implode(' ', $parts));
            $employeeData['employee_no'] = $emp['employee_code'] ?? $emp['employee_no'] ?? '';
            $employeeData['position_name'] = $emp['position_name'] ?? '';
            $employeeData['department_name'] = $emp['department_name'] ?? '';
            $employeeData['address'] = $emp['current_address'] ?? $emp['address'] ?? '';
            $employeeData['phone_number'] = $emp['mobile_no'] ?? $emp['phone_no'] ?? $emp['phone_number'] ?? '';
            $employeeData['email'] = $emp['email'] ?? '';
            $employeeData['birthdate'] = $emp['birth_date'] ?? $emp['birthdate'] ?? '';
            $employeeData['date_hired'] = $emp['date_hired'] ?? $emp['hire_date'] ?? $emp['hired_at'] ?? '';
            $employeeData['sex'] = $emp['gender'] ?? $emp['sex'] ?? '';
            $employeeData['marital_status'] = $emp['civil_status'] ?? $emp['marital_status'] ?? '';
            $employeeData['employment_status'] = $emp['employment_status'] ?? '';
            $employeeData['nationality'] = $emp['nationality'] ?? 'Filipino';
        }
    } catch (Throwable $e) {
        $employeeData['full_name'] = 'Employee #' . $overrideEmployeeId;
    }
}

if (empty($employeeData['full_name'])) {
    $employeeData['full_name'] = 'Employee #' . $employeeId;
}
if (empty($employeeData['employee_no'])) {
    $employeeData['employee_no'] = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
}

$handbookContent = lc_replace_placeholders($handbookContent, [], []);

$hasMarkdown = preg_match('/^(#{1,6}\s|[-*+]\s|\d+\.\s)/m', $handbookContent);
if ($hasMarkdown) {
    $handbookContent = dg_markdown_to_html($handbookContent);
}

$empStmt = $db->prepare("SELECT first_name, middle_name, last_name, employee_code FROM em_employees WHERE employee_id = :id LIMIT 1");
$empStmt->execute([':id' => $employeeId]);
$emp = $empStmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    $empStmt = $db->prepare("SELECT first_name, NULL AS middle_name, last_name, NULL AS employee_code FROM rao_hired WHERE id = :id OR application_id = :id LIMIT 1");
    $empStmt->execute([':id' => $employeeId]);
    $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
}
$employeeName = $emp ? trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')) : 'Employee';

$today = date('F d, Y');
$hrSignatory = lc_get_signature_image();
$hrSignatoryName = htmlspecialchars((string) ($_GET['hr_signatory'] ?? ''), ENT_QUOTES);
if ($hrSignatoryName === '') {
    $hrSignatoryName = 'Blythe Enriquez';
}

?>

<?php if ($message === 'ack_success'): ?>
<div class="dg-template-frame">
    <div class="dg-success" style="padding:12px 16px; border-radius:10px; background:rgba(47,158,110,.12); color:#1f7a52; border:1px solid rgba(47,158,110,.25); margin-bottom:16px;">
        <i class="bi bi-check-circle"></i> Handbook acknowledged successfully. Thank you.
    </div>
</div>
<?php elseif ($message === 'ack_error'): ?>
<div class="dg-template-frame">
    <div class="dg-error" style="padding:12px 16px; border-radius:10px; background:rgba(214,72,74,.10); color:#a3272a; border:1px solid rgba(214,72,74,.22); margin-bottom:16px;">
        <i class="bi bi-exclamation-triangle"></i> Failed to record acknowledgement. Please try again.
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

    <table class="document-information">
        <tr>
            <td class="info-label">Document</td>
            <td class="info-value">Employee Handbook</td>
        </tr>
        <tr>
            <td class="info-label">Version</td>
            <td class="info-value"><?= htmlspecialchars($version) ?></td>
        </tr>
        <tr>
            <td class="info-label">Effective Date</td>
            <td class="info-value"><?= $effectiveDate ? date('F d, Y', strtotime($effectiveDate)) : '________________' ?></td>
        </tr>
        <tr>
            <td class="info-label">Governing Law</td>
            <td class="info-value">Philippine Labor Code (PD 442) and applicable company policies</td>
        </tr>
    </table>

    <table class="document-information">
        <tr>
            <td class="info-label">Employee Name</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Department</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Position</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Date Hired</td>
            <td class="info-value">________________</td>
        </tr>
    </table>

    <div class="document-body">
        <?= $handbookContent ?>
    </div>

    <?php if ($isLoggedInEmployee): ?>
        <?php if ($ackStatus && $ackStatus['status'] === 'Acknowledged'): ?>
        <?php elseif ($ackStatus && $ackStatus['status'] === 'Viewed'): ?>
                <div class="document-notary">
        <img src="<?= $protocol . $host . '/hrms-capstone/modules/compliance/assets/notary.png' ?>" alt="Notary Seal">
    </div>
<div class="document-signature" style="margin-top:24px;">
                <form method="post" action="" style="display:inline-flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="acknowledge_handbook">
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
                <form method="post" action="" style="display:inline-flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="acknowledge_handbook">
                    <label style="display:flex; align-items:center; gap:8px; font-size:.85rem; cursor:pointer; background:#fff; padding:10px 16px; border-radius:8px; border:1px solid var(--border,#e4e8ee);">
                        <input type="checkbox" name="acknowledge" value="1" required style="width:16px; height:16px; accent-color:#1f7a52;">
                        I have read and understood the Employee Handbook and agree to abide by its policies.
                    </label>
                    <button type="submit" class="dg-btn-generate" style="padding:9px 18px; border-radius:8px; border:none; background:linear-gradient(135deg, #1f7a52 0%, #166534 100%); color:#fff; font-weight:700; cursor:pointer;">
                        <i class="bi bi-check-lg"></i> Acknowledge Handbook
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
