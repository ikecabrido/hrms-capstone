<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$employeeId = $data['employee_id'];

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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();

$today = date('F d, Y');
$documentTitle = 'Certificate of Employment';
?>

<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">CERTIFICATE OF EMPLOYMENT</h2>
        <p class="document-subtitle">TO WHOM IT MAY CONCERN</p>
    </div>

    <div class="document-body">
        <p>
            This is to certify that <strong><?= $fullName ?></strong> is employed by
            <strong>BESTLINK College of the Philippines</strong> and has been serving as
            <strong><?= $position ?: '________________' ?></strong> under the
            <strong><?= $department ?: '________________' ?></strong> Department since
            <strong><?= (($employee['date_hired'] ?? $employee['hire_date'] ?? '') ? date('F d, Y', strtotime($employee['date_hired'] ?? $employee['hire_date'] ?? '')) : '________________') ?></strong>.
        </p>

        <p>
            Based on the records maintained by the Human Resources Department, the employee has rendered service in accordance with the terms and conditions of employment established by the institution. This Certificate of Employment is issued upon the employee's request for whatever lawful purpose it may serve.
        </p>

        <p>
            Issued this <strong><?= $today ?></strong> at BESTLINK College of the Philippines.
        </p>
    </div>

        <div class="document-notary">
        <img src="/hrms-capstone/modules/compliance/assets/notary.png" alt="Notary Seal">
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
        This document is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.
    </div>
</div>





