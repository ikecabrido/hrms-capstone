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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''));
$employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''));
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''));
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''));
$rawDateHired  = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
$dateHired     = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '';
$lastWorkingDay = !empty($employee['last_working_day']) ? date('F d, Y', strtotime($employee['last_working_day'])) : date('F d, Y');
$hrSignatory = lc_get_signature_image();

$today = date('F d, Y');
$documentTitle = 'Clearance Survey';
?>

<div class="dg-template-frame">


  <div style="margin-top:18px; padding:22px; border:1px solid var(--border-strong,#d3d9e2); border-radius:10px; background:#ffffff;">
    <p style="margin:0 0 14px; font-weight:700; color:var(--text-900,#1b2430); font-size:1rem; text-align:center; text-transform:uppercase; letter-spacing:0.04em;">CLEARANCE SURVEY</p>
    <p style="margin:0 0 6px; font-size:0.82rem; color:var(--text-600,#5b6472); text-align:center;">Exit Clearance Checklist and Sign-Off Form</p>
    <hr style="border:0; border-top:1px solid var(--border,#e4e8ee); margin:10px 0 16px;">

   <div class="dg-document-body">

    <p style="
        margin:0 0 18px;
        text-align:justify;
        line-height:1.85;
    ">
        This <strong>Exit Clearance Checklist</strong> certifies that
        <strong><?= $fullName ?></strong>, Employee No.
        <strong><?= $employeeNo ?></strong>, assigned as
        <strong><?= $position ?: '________________'; ?></strong> under the
        <strong><?= $department ?: '________________'; ?></strong> Department,
        is undergoing the Company's exit clearance process in accordance with
        Company policies and applicable Philippine labor laws.
    </p>

    <p style="
        margin:0 0 22px;
        text-align:justify;
        line-height:1.85;
    ">
        Each concerned office shall certify that the Employee has settled all
        obligations, returned Company properties, and completed all required
        separation procedures before final clearance and release of employment
        documents and benefits.
    </p>

    <table style="
        width:100%;
        border-collapse:collapse;
        font-size:13px;
        margin-top:20px;
    ">

        <thead>

            <tr style="background:#f5f7fa;">

                <th style="padding:12px;border:1px solid #d7dbe3;text-align:left;">
                    Department / Office
                </th>

                <th style="padding:12px;border:1px solid #d7dbe3;text-align:left;">
                    Clearance Requirement
                </th>

                <th style="padding:12px;border:1px solid #d7dbe3;text-align:center;width:120px;">
                    Status
                </th>

                <th style="padding:12px;border:1px solid #d7dbe3;text-align:center;width:160px;">
                    Authorized Signature
                </th>

            </tr>

        </thead>

        <tbody>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Immediate Supervisor</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">Turnover of duties completed</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Department Head</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">No pending departmental accountabilities</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Human Resources</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">Exit documents completed</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Finance</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">No outstanding financial obligations</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">IT / MIS</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">Company equipment and system access cleared</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Property Custodian</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">ID card, keys, uniforms and Company property returned</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

            <tr>
                <td style="padding:12px;border:1px solid #d7dbe3;">Legal / Compliance</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">No pending legal or compliance obligations</td>
                <td style="padding:12px;border:1px solid #d7dbe3;text-align:center;">____________</td>
                <td style="padding:12px;border:1px solid #d7dbe3;">________________</td>
            </tr>

        </tbody>

    </table>

    <p style="
        margin-top:24px;
        text-align:justify;
        line-height:1.85;
    ">
        Completion of this Exit Clearance Checklist signifies that all required
        accountabilities have been settled. The Employee understands that the
        processing of final pay, Certificate of Employment, government
        documents, tax certificates, and other separation benefits shall be
        subject to the successful completion of this clearance process and the
        applicable Company policies.
    </p>
   
        </div>
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







