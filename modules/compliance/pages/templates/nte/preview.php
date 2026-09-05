<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$employeeId = $data['employee_id'];
$sourceTable = $data['source_table'] ?? 'em_employees';
$idColumn    = $data['id_column'] ?? 'employee_id';

$employee = null;
if ($employeeId !== '') {
    try {
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

lc_apply_meta_overrides($employee);

$fullName   = htmlspecialchars((string) ($employee['full_name'] ?? ''));
$employeeNo = htmlspecialchars((string) ($employee['employee_no'] ?? ''));
$department = htmlspecialchars((string) ($employee['department_name'] ?? ''));
$position   = htmlspecialchars((string) ($employee['position_name'] ?? ''));
$hrSignatory = lc_get_signature_image();

$incidentDate       = isset($_GET['incident_date']) ? trim((string) $_GET['incident_date']) : '';
$incidentTime       = isset($_GET['incident_time']) ? trim((string) $_GET['incident_time']) : '';
$incidentLocation   = isset($_GET['incident_location']) ? trim((string) $_GET['incident_location']) : '';
$incidentDescription = isset($_GET['incident_description']) ? trim((string) $_GET['incident_description']) : '';
$policyViolated     = isset($_GET['policy_violated']) ? trim((string) $_GET['policy_violated']) : '';

$policyOptions = [
    'Labor Code',
    'DOLE Standard',
    'Working Hours',
    'Benefits',
    'Contract',
    'Safety',
    'Other',
];

$today = date('F d, Y');
?>

<div class="dg-template-frame">
<div class="dg-actions">


<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">NOTICE TO EXPLAIN (NTE)</h2>
        <p class="document-subtitle">Administrative Due Process Notice Requiring Written Explanation</p>
    </div>

    <hr class="document-separator">

    <table class="document-information">

        <tr>
            <td class="info-label">Date Issued</td>
            <td class="info-value"><?= $today ?></td>
        </tr>

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

    </table>

    <div class="document-body">

        <p>
            Dear <strong><?= $fullName ?></strong>,
        </p>

        <p>
            This <strong>Notice to Explain (NTE)</strong> is issued in accordance with the Company's Code of Conduct, disciplinary procedures, the Philippine Labor Code, applicable Department of Labor and Employment (DOLE) regulations, and the principles of administrative due process.
        </p>

        <p>
            You are hereby directed to submit a written explanation regarding the alleged act, omission, incident, or policy violation described below. This notice is issued to provide you with a fair and reasonable opportunity to present your side before any administrative action or decision is made.
        </p>

    </div>

    <div class="document-section-header">
        Details of the Alleged Incident
    </div>

    <table class="document-information">

        <tr>
            <td class="info-label">Date of Incident</td>
            <td class="info-value"><?= $incidentDate !== '' ? htmlspecialchars($incidentDate) : '______________________________________________' ?></td>
        </tr>

        <tr>
            <td class="info-label">Time</td>
            <td class="info-value"><?= $incidentTime !== '' ? htmlspecialchars($incidentTime) : '______________________________________________' ?></td>
        </tr>

        <tr>
            <td class="info-label">Location</td>
            <td class="info-value"><?= $incidentLocation !== '' ? htmlspecialchars($incidentLocation) : '______________________________________________' ?></td>
        </tr>

        <tr>
            <td class="info-label">Incident Description</td>
            <td class="info-value">

                <?php if ($incidentDescription !== ''): ?>
                    <div class="pre-wrap"><?= htmlspecialchars($incidentDescription) ?></div>
                <?php else: ?>
                    <div class="placeholder-line"></div>
                <?php endif; ?>

            </td>

        </tr>

        <tr>
            <td class="info-label">Policy / Rule Violated</td>
            <td class="info-value">
                <?= $policyViolated !== '' ? htmlspecialchars($policyViolated) : '______________________________________________' ?>
            </td>

        </tr>

    </table>

    <div class="document-body">

        <p>
            You are directed to submit your written explanation together with any supporting documents, evidence, or witness statements within the period prescribed by the Human Resources Department.
        </p>

        <p>
            Failure to submit your explanation within the prescribed period, without a valid reason, may be considered a waiver of your opportunity to explain. The Company may proceed with the administrative evaluation based on the available records and evidence.
        </p>

        <p>
            Please be advised that the issuance of this Notice does <strong>not</strong> constitute a finding of guilt nor the imposition of disciplinary action. It is issued solely to ensure compliance with the requirements of administrative due process and to provide you with an opportunity to be heard.
        </p>

        <div class="document-notary">
        <img src="<?= $protocol . $host . '/hrms-capstone/modules/compliance/assets/notary.png' ?>" alt="Notary Seal">
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
            <div class="sig-role"><?= htmlspecialchars($position ?: 'Employee') ?></div>
            <div class="sig-date">Date: <?= $today ?></div>
        </div>

    </div>

    <div class="document-disclaimer">

        <strong>Academic Disclaimer</strong><br><br>

        This Employment Contract is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.

        The employee information, employer details, employment terms, compensation, position, em_departments, signatures, dates, and all other information contained in this document are fictitious, system-generated, or used exclusively for demonstration purposes. This document does not constitute an actual employment agreement and should not be interpreted as legally binding.

        This document is intended only to demonstrate the document generation, document template management, and legal compliance functionalities of the proposed Human Resource Management System. It should not be used as a substitute for legal advice or official employment documentation.

        Any resemblance to actual persons, organizations, institutions, or events is purely coincidental.

    </div>

</div>





