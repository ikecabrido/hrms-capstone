<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../../../../database/db.php';
require_once dirname(__DIR__) . '/ajax/document_template_helper.php';

$employeeId   = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '';
$documentType = isset($_GET['document_type']) ? trim((string) $_GET['document_type']) : '';

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
                    SELECT e.*, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, e.employee_code AS employee_no, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
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

$generateUrl = 'generate_document.php?employee_id=' . urlencode($employeeId) . '&document_type=' . urlencode($documentType) . '&template=' . urlencode(basename(__FILE__));
if (!empty($_GET['template_code'])) {
    $generateUrl .= '&template_code=' . urlencode($_GET['template_code']);
}
foreach (['hr_signatory', 'contract_start_date', 'contract_end_date', 'contract_type', 'contract_salary_input'] as $key) {
    if (!empty($_GET[$key])) {
        $generateUrl .= '&' . $key . '=' . urlencode($_GET[$key]);
    }
}
foreach (['hr_signatory', 'contract_start_date', 'contract_end_date', 'contract_type', 'contract_salary_input', 'incident_date', 'incident_time', 'incident_location', 'incident_description', 'policy_violated'] as $key) {
    if (!empty($_GET[$key])) {
        $generateUrl .= '&' . $key . '=' . urlencode($_GET[$key]);
    }
}
?>

<div class="dg-template-frame">
<div class="dg-actions">
 

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
            NOTICE TO EXPLAIN (NTE)
        </h2>

        <p style="
            margin:10px 0 30px;
            text-align:center;
            color:#666;
            font-size:14px;
        ">
            Administrative Due Process Notice Requiring Written Explanation
        </p>

        <hr style="margin:0 0 30px;">

        <table style="
            width:100%;
            border-collapse:collapse;
            margin-bottom:35px;
        ">

            <tr>
                <td style="width:190px;padding:9px 0;"><strong>Date Issued</strong></td>
                <td><?= $today ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Employee Name</strong></td>
                <td><?= $fullName ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Employee Number</strong></td>
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

        </table>

        <div class="dg-document-body" style="
            text-align:justify;
            line-height:1.85;
            margin-bottom:35px;
        ">

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

        <h3 style="margin:0 0 18px;">
            Details of the Alleged Incident
        </h3>

        <table style="
            width:100%;
            border-collapse:collapse;
            margin-bottom:35px;
        ">

            <tr>
                <td style="width:190px;padding:9px 0;"><strong>Date of Incident</strong></td>
                <td><?= $incidentDate !== '' ? htmlspecialchars($incidentDate) : '______________________________________________' ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Time</strong></td>
                <td><?= $incidentTime !== '' ? htmlspecialchars($incidentTime) : '______________________________________________' ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Location</strong></td>
                <td><?= $incidentLocation !== '' ? htmlspecialchars($incidentLocation) : '______________________________________________' ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;vertical-align:top;">
                    <strong>Incident Description</strong>
                </td>

                <td style="padding-top:9px;">

                    <?php if ($incidentDescription !== ''): ?>
                        <div style="line-height:1.8; white-space: pre-wrap;"><?= htmlspecialchars($incidentDescription) ?></div>
                    <?php else: ?>
                        <div style="
                            min-height:110px;
                            border-bottom:1px solid #888;
                        "></div>
                    <?php endif; ?>

                </td>

            </tr>

            <tr>
                <td style="padding:9px 0;vertical-align:top;">
                    <strong>Policy / Rule Violated</strong>
                </td>

                <td style="padding-top:9px;">
                    <?= $policyViolated !== '' ? htmlspecialchars($policyViolated) : '______________________________________________' ?>
                </td>

            </tr>

        </table>

        <div class="dg-document-body" style="
            text-align:justify;
            line-height:1.85;
            margin-bottom:45px;
        ">

            <p>
                You are directed to submit your written explanation together with any supporting documents, evidence, or witness statements within the period prescribed by the Human Resources Department.
            </p>

            <p>
                Failure to submit your explanation within the prescribed period, without a valid reason, may be considered a waiver of your opportunity to explain. The Company may proceed with the administrative evaluation based on the available records and evidence.
            </p>

            <p>
                Please be advised that the issuance of this Notice does <strong>not</strong> constitute a finding of guilt nor the imposition of disciplinary action. It is issued solely to ensure compliance with the requirements of administrative due process and to provide you with an opportunity to be heard.
            </p>

    
        <div style="margin-top:60px;">

            <div style="margin-bottom:40px;">

                                <img src="/hrms-capstone/modules/compliance/assets/notary.png" style="width:340px;height:auto;display:inline-block;opacity:0.5;mix-blend-mode:multiply;">
<div style="position: relative; display: inline-block;">
                    <div style="position: absolute; top: 0; left: 0; z-index: 2;">
                        <?= $hrSignatory ?>
                    </div>
                    <div style="position: relative; z-index: 1; padding-top: 45px;">
                        <strong>Blythe Lewis</strong>

                        <br>

                        HR Directress

                        <br><br>

                        Date: <?= $today ?>
                    </div>
                </div>

            </div>

            <div>

                <strong>Employee</strong>

                <br><br>

                <strong><?= htmlspecialchars($fullName) ?></strong>

                <br>

                <?= htmlspecialchars($position ?: 'Employee') ?>

                <br><br>

                _______________________________

                <br>

                Employee Signature

                <br>

                Date: <?= $today ?>

            </div>

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







