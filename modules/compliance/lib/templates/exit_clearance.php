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

$today = date('F d, Y');
?>



<div class="dg-template-frame">

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
            EXIT CLEARANCE
        </h2>

        <p style="
            margin:10px 0 30px;
            text-align:center;
            color:#666;
            font-size:14px;
        ">
            EMPLOYEE SEPARATION CLEARANCE
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

            <tr>
                <td style="padding:9px 0;"><strong>Separation Date</strong></td>
                <td>________________</td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Date Issued</strong></td>
                <td><?= $today ?></td>
            </tr>

        </table>

        <div class="dg-document-body" style="
            text-align:justify;
            line-height:1.85;
            margin-bottom:35px;
        ">

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

        <table style="
            width:100%;
            border-collapse:collapse;
            border:1px solid #000;
            margin-bottom:40px;
        ">

            <tr>
                <th style="border:1px solid #000;padding:10px;">Department / Office</th>
                <th style="border:1px solid #000;padding:10px;">Status</th>
                <th style="border:1px solid #000;padding:10px;">Authorized Signature</th>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Immediate Supervisor</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Human Resources Department</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Accounting / Finance</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Information Technology</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Administration / Property Custodian</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

            <tr>
                <td style="border:1px solid #000;padding:10px;">Library / Other Concerned Office</td>
                <td style="border:1px solid #000;padding:10px;">__________</td>
                <td style="border:1px solid #000;padding:10px;"></td>
            </tr>

        </table>

        <table style="
            width:100%;
            margin-top:50px;
        ">

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







