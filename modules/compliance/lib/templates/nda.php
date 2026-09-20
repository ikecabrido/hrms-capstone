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
$hrSignatory = lc_get_signature_image();

$today = date('F d, Y');
?>

<div class="dg-template-frame">


<div style="
        margin-top:15px;
        padding:18px 22px;
        border:1px solid #d7dbe3;
        border-radius:8px;
        background:#fff;
        font-family:'Times New Roman', Times, serif;
        font-size:12px;
        line-height:1.65;
        color:#222;
    ">

        <h2 style="
            margin:0;
            text-align:center;
            text-transform:uppercase;
            letter-spacing:.06em;
            font-size:20px;
        ">
            NON-DISCLOSURE AGREEMENT
        </h2>

        <p style="
            margin:8px 0 20px;
            text-align:center;
            color:#666;
            font-size:12px;
        ">
            CONFIDENTIALITY AND NON-DISCLOSURE
        </p>

        <hr style="margin:0 0 20px;">

        <table style="
            width:100%;
            border-collapse:collapse;
            margin-bottom:24px;
        ">

            <tr>
                <td style="width:170px;padding:6px 0;"><strong>Employee Name</strong></td>
                <td><?= $fullName ?></td>
            </tr>

            <tr>
                <td style="padding:6px 0;"><strong>Employee Number</strong></td>
                <td><?= $employeeNo ?: '________________'; ?></td>
            </tr>

            <tr>
                <td style="padding:6px 0;"><strong>Department</strong></td>
                <td><?= $department ?: '________________'; ?></td>
            </tr>

            <tr>
                <td style="padding:6px 0;"><strong>Position</strong></td>
                <td><?= $position ?: '________________'; ?></td>
            </tr>

            <tr>
                <td style="padding:6px 0;"><strong>Agreement Date</strong></td>
                <td><?= $today ?></td>
            </tr>

        </table>

        <div style="
            text-align:justify;
            line-height:1.65;
            margin-bottom:30px;
        ">

            <p>
                This Non-Disclosure Agreement ("Agreement") is entered into on
                <strong><?= $today ?></strong>
                between
                <strong>BESTLINK College of the Philippines</strong>
                ("Employer") and
                <strong><?= $fullName ?></strong>
                ("Employee").
            </p>

            <p>
                The Employee acknowledges that during the course of employment,
                confidential information including but not limited to employee
                records, student information, financial records, operational
                procedures, research materials, institutional policies, strategic
                plans, and other proprietary information may be accessed or
                disclosed.
            </p>

            <p>
                The Employee agrees to maintain the confidentiality of all
                information acquired during employment and shall not disclose,
                reproduce, distribute, or use such confidential information for
                personal benefit or for any unauthorized purpose without the prior
                written consent of the Employer, except when disclosure is required
                by law.
            </p>

            <p>
                This obligation shall remain effective throughout the period of
                employment and shall continue even after the termination,
                resignation, retirement, or separation of the Employee from the
                institution.
            </p>

            <p>
                Any violation of this Agreement may subject the Employee to
                disciplinary action, civil liability, and other legal remedies
                available under applicable Philippine laws and institutional
                policies.
            </p>

      
        </div>
        <div style="margin-top:40px;">

            <div style="margin-bottom:28px;">

                                <img src="/hrms-capstone/modules/compliance/assets/notary.png" style="width:340px;height:auto;display:inline-block;opacity:0.5;mix-blend-mode:multiply;">
<div style="position: relative; display: inline-block;">
                    <div style="position: absolute; top: 0; left: 0; z-index: 2;">
                        <?= $hrSignatory ?>
                    </div>
                    <div style="position: relative; z-index: 1; padding-top: 38px;">
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
            margin-top:40px;
            padding:12px 14px;
            border:1px solid #d8dce4;
            border-radius:8px;
            background:#fafbfc;
            font-size:10px;
            line-height:1.6;
            color:#666;
        ">

            <strong>Academic Disclaimer</strong><br><br>

            This Employment Contract is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.

            The employee information, employer details, employment terms, compensation, positions, em_departments, signatures, dates, and all other information contained in this document are fictitious, system-generated, or used exclusively for demonstration purposes. This document does not constitute an actual employment agreement and should not be interpreted as legally binding.

            This document is intended only to demonstrate the document generation, document template management, and legal compliance functionalities of the proposed Human Resource Management System. It should not be used as a substitute for legal advice or official employment documentation.

            Any resemblance to actual persons, organizations, institutions, or events is purely coincidental.

        </div>


    </div>







