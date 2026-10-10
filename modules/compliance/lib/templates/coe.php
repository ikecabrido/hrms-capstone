
<style id="coe-production-css">
/* =========================================================
   BCP HRMS - Certificate of Employment
   Production CSS
   ========================================================= */

.document-preview {
    width: 210mm;
    min-height: 297mm;
    margin: 30px auto;
    padding: 20mm 22mm;

    box-sizing: border-box;
    position: relative;
    overflow: hidden;

    background: #fff;
    color: #222;

    font-family: Arial, Helvetica, sans-serif;
    font-size: 11.5pt;
    line-height: 1.7;

    border: 1px solid #d9d9d9;
    box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
}


/* =========================
   HEADER
   ========================= */

.document-header {
    text-align: center;
    margin-bottom: 32px;
}

.document-title {
    margin: 0 0 8px;

    font-size: 18pt;
    font-weight: 700;
    line-height: 1.25;

    letter-spacing: .8px;
    color: #111;
}

.document-subtitle {
    margin: 0;

    font-size: 10.5pt;
    font-weight: 600;

    letter-spacing: 1.4px;
    color: #555;
}


/* =========================
   DOCUMENT BODY
   ========================= */

.document-body {
    margin-top: 10px;
}

.document-body p {
    margin: 0 0 20px;

    line-height: 1.8;
    text-align: justify;
    text-justify: inter-word;
}

.document-body strong {
    font-weight: 700;
    color: #111;
}


/* =========================
   SIGNATURE AREA
   ========================= */

.document-signature-area {
    position: relative;
    margin-top: 55px;
}

.document-signature {
    display: grid;
    grid-template-columns: 1fr 1fr;

    gap: 60px;
    margin-top: 70px;
}

.document-signature-block {
    min-width: 0;
    text-align: center;
}


/* Signature image */

.sig-image {
    height: 70px;

    display: flex;
    align-items: flex-end;
    justify-content: center;

    margin-bottom: 2px;
}

.sig-image img {
    display: block;

    width: auto;
    height: 70px;
    max-width: 180px;

    object-fit: contain;
}


/* Signature text */

.sig-text {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sig-name {
    min-height: 22px;

    font-size: 10.5pt;
    font-weight: 700;

    color: #111;
}

.sig-role {
    margin-top: 2px;

    font-size: 10pt;
    font-weight: 600;

    color: #333;
}

.sig-date {
    margin-top: 3px;

    font-size: 9pt;
    color: #666;
}


/* Employee signature */

.document-signature-block:last-child {
    padding-top: 70px;
}


/* =========================
   SMALL NOTARY STAMP
   ========================= */

.document-notary {
    position: absolute;

    top: -5px;
    right: 10px;

    width: 48px;
    height: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    z-index: 5;

    opacity: .9;
}

.document-notary img {
    display: block;

    width: 48px;
    height: 48px;

    max-width: 100%;
    max-height: 100%;

    object-fit: contain;

    pointer-events: none;
    user-select: none;
}


/* =========================
   DISCLAIMER
   ========================= */

.document-disclaimer {
    margin-top: 55px;
    padding: 12px 15px;

    border: 1px solid #d7d7d7;
    border-left: 3px solid #888;

    background: #f8f8f8;

    font-size: 8.5pt;
    line-height: 1.55;

    color: #666;
}

.document-disclaimer strong {
    color: #444;
}


/* =========================
   TABLET
   ========================= */

@media screen and (max-width: 850px) {

    .document-preview {
        width: calc(100% - 30px);
        min-height: auto;

        margin: 15px auto;
        padding: 35px 30px;
    }

    .document-signature {
        gap: 30px;
    }
}


/* =========================
   MOBILE
   ========================= */

@media screen and (max-width: 600px) {

    .document-preview {
        width: calc(100% - 20px);

        min-height: auto;
        margin: 10px auto;
        padding: 28px 20px;

        border: 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
    }

    .document-title {
        font-size: 14pt;
    }

    .document-subtitle {
        font-size: 9pt;
    }

    .document-body p {
        text-align: left;
    }

    .document-signature {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .document-signature-block:last-child {
        padding-top: 0;
    }

    .document-notary {
        position: static;

        width: 45px;
        height: 45px;

        margin-left: auto;
        margin-bottom: -20px;
    }

    .document-notary img {
        width: 45px;
        height: 45px;
    }
}


/* =========================
   PRINT / PDF
   ========================= */

@media print {

    @page {
        size: A4 portrait;
        margin: 0;
    }

    html,
    body {
        margin: 0;
        padding: 0;

        background: #fff;
    }

    .document-preview {
        width: 210mm;
        min-height: 297mm;

        margin: 0;
        padding: 20mm 22mm;

        border: 0;
        box-shadow: none;

        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .document-notary {
        width: 48px;
        height: 48px;
    }

    .document-notary img {
        width: 48px;
        height: 48px;
    }

    .document-signature,
    .document-disclaimer {
        break-inside: avoid;
    }
}
</style>

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
$rawDateHired = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
$dateHired  = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '';

$documentPurpose = trim((string) ($documentPurpose ?? ''));

if ($documentPurpose === '') {
    $documentPurpose = 'Employment Verification';
}

$documentPurposeHtml = htmlspecialchars($documentPurpose, ENT_QUOTES, 'UTF-8');

$today = date('F d, Y');
?>

<div class="dg-template-frame">

<div style="
        margin-top:20px;
        padding:25px 30px;
        border:1px solid #d7dbe3;
        border-radius:8px;
        background:#fff;
        font-family: Arial, sans-serif;
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
            CERTIFICATE OF EMPLOYMENT
        </h2>

        <p style="
            margin:10px 0 30px;
            text-align:center;
            color:#666;
            font-size:14px;
        ">
            TO WHOM IT MAY CONCERN
        </p>


        <div class="dg-document-body" style="
            text-align:justify;
            line-height:1.85;
            margin-bottom:45px;
        ">

            <p>
                This is to certify that <strong><?= $fullName ?></strong> is employed by
                <strong>BESTLINK College of the Philippines</strong> and has been serving as
                <strong><?= $position ?: '________________' ?></strong> under the
                <strong><?= $department ?: '________________' ?></strong> Department since
                <strong><?= $dateHired ?: '________________' ?></strong>.
            </p>

            <p>
                Based on the records maintained by the Human Resources Department, the employee has rendered service in accordance with the terms and conditions of employment established by the institution.
            </p>

            <p>
                This certificate is issued for the purpose of
                <strong><?= $documentPurposeHtml ?></strong>.
            </p>

            <p>
                Issued this <strong><?= $today ?></strong> at BESTLINK College of the Philippines.
            </p>

                        <img src="/modules/compliance/assets/notary.png" style="width:340px;height:auto;display:inline-block;opacity:0.5;mix-blend-mode:multiply;">
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







