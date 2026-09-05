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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo    = htmlspecialchars((string) ($employee['employee_no'] ?? ''), ENT_QUOTES);
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();

$leaveType     = !empty($_GET['leave_type']) ? $_GET['leave_type'] : '________________';
$leaveStart    = !empty($_GET['leave_start_date']) && strtotime($_GET['leave_start_date']) !== false ? date('F d, Y', strtotime($_GET['leave_start_date'])) : '__________';
$leaveEnd      = !empty($_GET['leave_end_date']) && strtotime($_GET['leave_end_date']) !== false ? date('F d, Y', strtotime($_GET['leave_end_date'])) : '__________';
$leaveDuration = !empty($_GET['leave_duration']) ? $_GET['leave_duration'] : '__________';

$today = date('F d, Y');
$documentTitle = 'Leave Agreement';
?>

<?php
$employer = lc_get_active_employer($db);
$templateRecord = lc_get_document_template($db, $templateCode);

if ($templateRecord && !empty($templateRecord['template_content'])) {
    $documentBody = lc_replace_placeholders($templateRecord['template_content'], $employee, $employer);
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $documentBody = lc_replace_placeholders(lc_get_fallback_template('leave_agreement'), $employee, $employer);
    $governingLaw = 'Philippine Labor Code';
}
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
            LEAVE AGREEMENT
        </h2>

        <p style="
            margin:10px 0 30px;
            text-align:center;
            color:#666;
            font-size:14px;
        ">
            EMPLOYEE LEAVE APPROVAL
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
                <td style="padding:9px 0;"><strong>Leave Type</strong></td>
                <td><?= htmlspecialchars($leaveType ?? '________________'); ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Leave Period</strong></td>
                <td>
                    <?= htmlspecialchars($leaveStart ?? '__________') ?>
                    to
                    <?= htmlspecialchars($leaveEnd ?? '__________') ?>
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Duration</strong></td>
                <td><?= htmlspecialchars($leaveDuration ?? '__________'); ?></td>
            </tr>

            <tr>
                <td style="padding:9px 0;"><strong>Date Issued</strong></td>
                <td><?= $today ?></td>
            </tr>

        </table>

            <p>
                This Leave Agreement certifies that
                <strong><?= $fullName ?></strong>,
                currently employed as
                <strong><?= $position ?: '________________'; ?></strong>
                under the
                <strong><?= $department ?: '________________'; ?></strong>
                Department, has requested a
                <strong><?= htmlspecialchars($leaveType ?? 'leave'); ?></strong>.
            </p>

            <p>
                The requested leave shall cover the period beginning
                <strong><?= htmlspecialchars($leaveStart ?? '__________'); ?></strong>
                until
                <strong><?= htmlspecialchars($leaveEnd ?? '__________'); ?></strong>,
                with an approved duration of
                <strong><?= htmlspecialchars($leaveDuration ?? '__________'); ?></strong>.
            </p>

            <p>
                During the approved leave period, the employee agrees to comply with all institutional leave policies, return to work on the scheduled date unless an authorized extension is granted, and complete any required turnover of duties before the commencement of the leave.
            </p>

            <p>
                This agreement serves as official documentation of the approved leave and shall form part of the employee's personnel records maintained by the Human Resources Department.
            </p>

            <p>
                Executed this
                <strong><?= $today ?></strong>
                at BESTLINK College of the Philippines.
            </p>
        <div style="margin: top 60px;">     

            <div>  

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






