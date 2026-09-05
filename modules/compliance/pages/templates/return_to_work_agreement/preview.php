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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();

$leaveReason = !empty($_GET['leave_reason']) ? $_GET['leave_reason'] : '________________';

$today = date('F d, Y');
$documentTitle = 'Return-to-Work Agreement';

$employer = lc_get_active_employer($db);
$templateRecord = lc_get_document_template($db, $templateCode);

if ($templateRecord && !empty($templateRecord['template_content'])) {
    $documentBody = lc_replace_placeholders($templateRecord['template_content'], $employee, $employer);
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $documentBody = lc_replace_placeholders(lc_get_fallback_template('return_service'), $employee, $employer);
    $governingLaw = 'Philippine Labor Code';
}
?>

<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">RETURN-TO-WORK AGREEMENT</h2>
        <p class="document-subtitle">EMPLOYEE RETURN-TO-WORK ACKNOWLEDGEMENT</p>
    </div>

    <hr class="document-separator">

    <table class="document-information">

        <tr>
            <td class="info-label">Employee Name</td>
            <td class="info-value"><?= $fullName ?></td>
        </tr>

        <tr>
            <td class="info-label">Employee Number</td>
            <td class="info-value"><?= $employeeNo ?></td>
        </tr>

        <tr>
            <td class="info-label">Department</td>
            <td class="info-value"><?= $department ?></td>
        </tr>

        <tr>
            <td class="info-label">Position</td>
            <td class="info-value"><?= $position ?></td>
        </tr>

        <tr>
            <td class="info-label">Reason for Leave</td>
            <td class="info-value"><?= htmlspecialchars($leaveReason) ?></td>
        </tr>

        <tr>
            <td class="info-label">Date Issued</td>
            <td class="info-value"><?= $today ?></td>
        </tr>

    </table>

    <div class="document-body">
        <?= $documentBody ?>
    </div>

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
            <div class="sig-date">Date: <?= $today ?></div>
        </div>

    </div>

    <div class="document-disclaimer">
        <strong>Academic Disclaimer</strong><br><br>
        This document is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.
    </div>
</div>





