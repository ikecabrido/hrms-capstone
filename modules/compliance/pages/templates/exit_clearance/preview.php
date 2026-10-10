<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$employeeId = $data['employee_id'];
$templateCode = $data['template_code'];

$employee = null;
if ($employeeId !== '') {
    try {
        $sourceTable = $data['source_table'];
        $idColumn = $data['id_column'];
        $stmt = $db->prepare("
            SELECT e.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
            FROM {$sourceTable} e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions   p ON p.position_id = e.position_id
            WHERE e.{$idColumn} = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $employeeId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $employee = null;
    }

    if (!$employee) {
        try {
            $stmt = $db->prepare("
                SELECT e.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM em_employees e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions   p ON p.position_id = e.position_id
                WHERE e.employee_id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $employee = null;
        }
    }
}

if (!$employee) {
    echo '<div class="dg-template-frame"><div class="dg-empty">No employee record found for this document.</div></div>';
    exit;
}

lc_apply_meta_overrides($employee);

if (empty($employee['full_name'])) {
    $parts = array_filter([$employee['first_name'] ?? '', $employee['middle_name'] ?? '', $employee['last_name'] ?? '']);
    $employee['full_name'] = trim(implode(' ', $parts));
}
if (empty($employee['full_name'])) {
    $employee['full_name'] = 'Employee #' . $employeeId;
}
if (empty($employee['employee_no']) && !empty($employee['employee_code'])) {
    $employee['employee_no'] = $employee['employee_code'];
}

$fullName   = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
$department = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position   = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();

$today = date('F d, Y');
$documentTitle = 'Exit Clearance';

$separationDate = trim((string) ($_GET['exit_date'] ?? ''));

$employer = lc_get_active_employer($db);
$templateRecord = lc_get_document_template($db, $templateCode);

if ($templateRecord && !empty($templateRecord['template_content'])) {
    $documentBody = lc_replace_placeholders($templateRecord['template_content'], $employee, $employer);
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $documentBody = '';
    $governingLaw = 'Philippine Labor Code (PD 442)';
}
?>

<div class="dg-template-frame">

<!-- EXIT CLEARANCE ACTION FORM -->
<div class="cx-body exit-clearance-actions">

    <p class="cd-section-title">Employee Information</p>

    <div class="cd-field-row">

        <div class="cd-field">
            <label>Full Name</label>
            <div class="cd-readonly-value"><?= $fullName ?></div>
        </div>

        <div class="cd-field">
            <label>Position</label>
            <div class="cd-readonly-value"><?= $position ?: '________________' ?></div>
        </div>

    </div>

    <div class="cd-field">
        <label>Department</label>
        <div class="cd-readonly-value"><?= $department ?: '________________' ?></div>
    </div>

    <form method="GET" action="" class="exit-clearance-date-form">

        <input type="hidden" name="page" value="preview-document">
        <input type="hidden" name="request_id" value="<?= htmlspecialchars((string)($_GET['request_id'] ?? ''), ENT_QUOTES) ?>">
        <input type="hidden" name="employee_id" value="<?= htmlspecialchars((string)($_GET['employee_id'] ?? $employeeId), ENT_QUOTES) ?>">
        <input type="hidden" name="document_type" value="exit_clearance">
        <input type="hidden" name="template" value="exit_clearance.php">
        <input type="hidden" name="template_code" value="exit_clearance">

        <div class="cd-field">
            <label for="separation_date">Separation Date</label>

            <input
                type="date"
                id="separation_date"
                name="exit_date"
                class="cd-input exit-date-picker"
                value="<?= htmlspecialchars($separationDate, ENT_QUOTES) ?>"
                required
            >
        </div>

        <div class="cd-field exit-clearance-action-buttons">

            <button type="submit" class="cd-btn-save">
                <i class="bi bi-check2-circle"></i>
                Apply Exit Details
            </button>

            <button
                type="button"
                class="cd-btn-email"
                id="sendExitClearanceEmail"
            >
                <i class="bi bi-envelope" aria-hidden="true"></i>
                Send to Email
            </button>

        </div>

    </form>

</div>

<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">EXIT CLEARANCE</h2>
        <p class="document-subtitle">EMPLOYEE SEPARATION CLEARANCE</p>
    </div>

    <hr class="document-separator">

    <table class="document-information">

        <tr>
            <td class="info-label">Employee Name</td>
            <td class="info-value"><?= $fullName ?></td>
        </tr>

        <tr>
            <td class="info-label">Employee Number</td>
            <td class="info-value"><?= $employeeNo ?: '________________'; ?></td>
        </tr>

        <tr>
            <td class="info-label">Department</td>
            <td class="info-value"><?= $department ?: '________________'; ?></td>
        </tr>

        <tr>
            <td class="info-label">Position</td>
            <td class="info-value"><?= $position ?: '________________'; ?></td>
        </tr>

        <tr>
            <td class="info-label">Separation Date</td>
            <td class="info-value">
                  <?php
                  $displayExitDate = trim((string) ($_GET["exit_date"] ?? ""));

                  if ($displayExitDate !== "" && preg_match("/^\d{4}-\d{2}-\d{2}$/", $displayExitDate)) {
                      echo htmlspecialchars(date("F d, Y", strtotime($displayExitDate)), ENT_QUOTES);
                  } else {
                      echo "________________";
                  }
                  ?>
            </td>
        </tr>

        <tr>
            <td class="info-label">Date Issued</td>
            <td class="info-value"><?= $today ?></td>
        </tr>

    </table>

    <div class="document-body">

        <p>
            This Exit Clearance certifies that <strong><?= $fullName ?></strong>,
            employed as
            <strong><?= $position ?: '________________'; ?></strong>
            under the
            <strong><?= $department ?: '________________'; ?></strong>
            Department, is undergoing the employee separation process with
            BESTLINK College of the Philippines.
        </p>

        <p>
            Prior to the release of the Employee's final pay, Certificate of Employment,
            and other separation benefits, all company properties, records,
            accountabilities, and financial obligations must be properly settled
            and cleared by the responsible offices.
        </p>

        <p>
            The Employee is required to obtain the necessary approvals from each
            department listed below. Clearance shall only be considered complete
            after all required signatures have been secured and all outstanding
            obligations have been settled.
        </p>

    </div>

    <table class="document-table">

        <tr>
            <th>Department / Office</th>
            <th>Status</th>
            <th>Authorized Signature</th>
        </tr>

        <tr>
            <td>Immediate Supervisor</td>
            <td>__________</td>
            <td></td>
        </tr>

        <tr>
            <td>Human Resources Department</td>
            <td>__________</td>
            <td></td>
        </tr>

        <tr>
            <td>Accounting / Finance</td>
            <td>__________</td>
            <td></td>
        </tr>

        <tr>
            <td>Information Technology</td>
            <td>__________</td>
            <td></td>
        </tr>

        <tr>
            <td>Administration / Property Custodian</td>
            <td>__________</td>
            <td></td>
        </tr>

        <tr>
            <td>Library / Other Concerned Office</td>
            <td>__________</td>
            <td></td>
        </tr>

    </table>

        <div class="document-notary">
        <img src="<?= $protocol . $host . '/modules/compliance/assets/notary.png' ?>" alt="Notary Seal">
    </div>
<div class="document-signature">

        <div class="document-signature-block">
            <div class="sig-image">
                <?= $hrSignatory ?>
            </div>
            <div class="sig-text">
                <div class="sig-name"><?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?></div>
                <div class="sig-role">HR Directress</div>
                <div class="sig-date">Date: <?= $today ?></div>
            </div>
        </div>

        <div class="document-signature-block">
            <div class="sig-name">Employee</div>
            <div class="sig-name"><?= htmlspecialchars($fullName) ?></div>
            <div class="sig-date">Date: <?= $today ?></div>
        </div>

    </div>

    <div class="document-disclaimer">
        <strong>Academic Disclaimer</strong><br><br>
        This Exit Clearance is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.

        The employee information, employer details, employment terms, compensation, position, em_departments, signatures, dates, and all other information contained in this document are fictitious, system-generated, or used exclusively for demonstration purposes. This document does not constitute an actual employment record and should not be interpreted as legally binding.

        This document is intended only to demonstrate the document generation and legal compliance functionalities of the proposed Human Resource Management System.

        Any resemblance to actual persons, organizations, institutions, or events is purely coincidental.

    </div>

</div>

</div>






