<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../../../../database/db.php';
require_once dirname(__DIR__) . '/ajax/document_template_helper.php';

$employeeId   = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '';
$documentType = isset($_GET['document_type']) ? trim((string) $_GET['document_type']) : '';
$templateCode = isset($_GET['template_code']) ? trim((string) $_GET['template_code']) : $documentType;

$employee = null;
$sourceLabel = 'em_employees';

    if ($employeeId !== '') {
        try {
            if (!isset($db)) {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
            $stmt = $db->prepare("
                SELECT e.*, COALESCE(d.department_name, '') AS department_name, COALESCE(p.position_name, '') AS position_name
                FROM new_hire_table e
                LEFT JOIN em_departments d ON d.department_id = e.department_id
                LEFT JOIN em_positions   p ON p.position_id = e.position_id
                WHERE e.candidate_id = :id
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
                 SELECT 
                     e.*, 
                     CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                     e.employee_code AS employee_no,
                     COALESCE(d.department_name, 'N/A') AS department_name, 
                     COALESCE(p.position_name, 'N/A') AS position_name
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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$rawDateHired  = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
$dateHired     = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '';
$today = date('F d, Y');
$documentTitle = 'Salary Rectification Agreement';

$employer = lc_get_active_employer($db);
$templateRecord = lc_get_document_template($db, $templateCode);

if ($templateRecord && !empty($templateRecord['template_content'])) {
    $documentBody = lc_replace_placeholders($templateRecord['template_content'], $employee, $employer);
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $documentBody = lc_replace_placeholders(lc_get_fallback_template('salary_rectification_agreement'), $employee, $employer);
    $governingLaw = 'Philippine Labor Code (PD 442)';
}

$generateUrl = 'generate_document.php?employee_id=' . urlencode($employeeId) . '&document_type=' . urlencode($documentType) . '&template=' . urlencode(basename(__FILE__)) . '&template_code=' . urlencode($templateCode);
foreach (['hr_signatory', 'contract_start_date', 'contract_end_date', 'contract_type', 'contract_salary_input', 'original_salary', 'rectification_date', 'effective_date'] as $key) {
    if (!empty($_GET[$key])) {
        $generateUrl .= '&' . $key . '=' . urlencode($_GET[$key]);
    }
}
?>
<div style="
    margin-top:20px;
    padding:25px 30px;
    border:1px solid #d7dbe3;
    border-radius:8px;
    background:#fff;
    font-family:'Times New Roman', Times, serif;
    font-size:13px;
    line-height:1.8;
    color:#222;
">

    <h2 style="
        margin:0;
        text-align:center;
        text-transform:uppercase;
        letter-spacing:.08em;
        font-size:26px;
    ">
        SALARY RECTIFICATION AGREEMENT
    </h2>

    <p style="
        margin:10px 0 30px;
        text-align:center;
        color:#666;
        font-size:14px;
    ">
        (Amendment to Employment Contract)
    </p>

    <p style="
        margin:10px 0 30px;
        text-align:center;
        color:#666;
        font-size:14px;
    ">
        Governed by <?= $governingLaw ?: 'Philippine Labor Code (Presidential Decree No. 442)' ?>
    </p>

    <hr style="margin:0 0 30px;">

    <table style="
        width:100%;
        border-collapse:collapse;
        margin-bottom:35px;
    ">

        <tr>
            <td style="width:190px;padding:9px 0;"><strong>Employee Name</strong></td>
            <td><?= $fullName ?></td>
        </tr>

        <tr>
            <td style="padding:9px 0;"><strong>Employee ID</strong></td>
            <td><?= $employeeNo ?: '________________'; ?></td>
        </tr>

        <tr>
            <td style="padding:9px 0;"><strong>Department</strong></td>
            <td><?= $department ?: '________________'; ?></td>
        </tr>

        <tr>
            <td style="padding:9px 0;"><strong>Position</strong></td>
            <td><?= $position ?: '________________'; ?></td>
        </tr>

        <tr>
            <td style="padding:9px 0;"><strong>Date of Rectification</strong></td>
            <td><?= htmlspecialchars((string) ($_GET['rectification_date'] ?? $today), ENT_QUOTES) ?></td>
        </tr>

    </table>

    <div class="dg-document-body" style="
        text-align:justify;
        line-height:1.85;
        margin-bottom:45px;
    ">

        <?= $documentBody ?>

    </div>

    <div style="
        margin-top:60px;
        padding:16px 18px;
        border:1px solid #d8dce4;
        border-radius:8px;
        background:#fafbfc;
        font-size:11px;
        line-height:1.8;
        color:#666;
    ">

        <strong>Academic Disclaimer</strong><br><br>

        This Employment Contract is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.

        The employee information, employer details, employment terms, compensation, positions, em_departments, signatures, dates, and all other information contained in this document are fictitious, system-generated, or used exclusively for demonstration purposes. This document does not constitute an actual employment agreement and should not be interpreted as legally binding.

        This document is intended only to demonstrate the document generation, document template management, and legal compliance functionalities of the proposed Human Resource Management System. It should not be used as a substitute for legal advice or official employment documentation.

        Any resemblance to actual persons, organizations, institutions, or events is purely coincidental.

    </div>

</div>







