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

$documentPurpose = trim((string) ($documentPurpose ?? ''));

if ($documentPurpose === '') {
    $documentPurpose = 'Employment Verification';
}

$documentPurposeHtml = htmlspecialchars($documentPurpose, ENT_QUOTES, 'UTF-8');
?>


<style id="coe-production-css">

/* =========================================================
   BCP HRMS - CERTIFICATE OF EMPLOYMENT
   ========================================================= */

.document-preview {
    width: 195mm !important;
    min-height: 297mm !important;

    margin: 30px auto !important;
    padding: 20mm 22mm !important;

    box-sizing: border-box !important;

    position: relative !important;

    background: #ffffff !important;
    color: #222222 !important;

    font-family: Arial, Helvetica, sans-serif !important;
    font-size: 11.5pt !important;
    line-height: 1.7 !important;

    border: 1px solid #d9d9d9 !important;

    box-shadow:
        0 4px 20px rgba(0, 0, 0, .08) !important;
}


/* =========================================================
   HEADER
   ========================================================= */

.document-header {
    text-align: center !important;
    margin-bottom: 34px !important;
}

.document-title {
    margin: 0 0 8px !important;

    font-size: 18pt !important;
    font-weight: 700 !important;

    line-height: 1.25 !important;
    letter-spacing: .8px !important;

    color: #111111 !important;
}

.document-subtitle {
    margin: 0 !important;

    font-size: 10.5pt !important;
    font-weight: 600 !important;

    letter-spacing: 1.4px !important;

    color: #555555 !important;
}


/* =========================================================
   BODY
   ========================================================= */

.document-body {
    margin-top: 10px !important;
}

.document-body p {
    margin: 0 0 21px !important;

    line-height: 1.85 !important;

    text-align: justify !important;
    text-justify: inter-word !important;
}

.document-body strong {
    font-weight: 700 !important;
    color: #111111 !important;
}


/* =========================================================
   SIGNATURE AREA
   ========================================================= */

.document-signature {
    position: relative !important;

    display: grid !important;
    grid-template-columns: 1fr 1fr !important;

    column-gap: 60px !important;

    margin-top: 75px !important;
    padding-top: 5px !important;
}

.document-signature-block {
    min-width: 0 !important;

    text-align: center !important;
}


/* =========================================================
   HR SIGNATURE
   ========================================================= */

.sig-image {
    height: 70px !important;

    display: flex !important;
    align-items: flex-end !important;
    justify-content: center !important;

    margin-bottom: 3px !important;
}

.sig-image img {
    display: block !important;

    width: auto !important;
    height: 70px !important;

    max-width: 180px !important;

    object-fit: contain !important;
}


/* =========================================================
   SIGNATURE TEXT
   ========================================================= */

.sig-text {
    display: flex !important;

    flex-direction: column !important;
    align-items: center !important;
}

.sig-name {
    min-height: 22px !important;

    font-size: 10.5pt !important;
    font-weight: 700 !important;

    color: #111111 !important;
}

.sig-role {
    margin-top: 2px !important;

    font-size: 10pt !important;
    font-weight: 600 !important;

    color: #333333 !important;
}

.sig-date {
    margin-top: 3px !important;

    font-size: 9pt !important;

    color: #666666 !important;
}


/* Employee signature alignment */

.document-signature-block:last-child {
    padding-top: 70px !important;
}


/* =========================================================
   NOTARY STAMP
   Small stamp positioned near signature area
   ========================================================= */

.document-notary {
    position: absolute !important;

    top: -45px !important;
    right: 50% !important;

    transform: translateX(50%) !important;

    width: 280px !important;
    height: 280px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    z-index: 50 !important;

    opacity: .12 !important;

    pointer-events: none !important;
}

.document-notary img {
    display: block !important;

    width: 280px !important;
    height: 280px !important;

    min-width: 280px !important;
    min-height: 280px !important;

    max-width: 280px !important;
    max-height: 280px !important;

    object-fit: contain !important;

    pointer-events: none !important;
    user-select: none !important;
}


/* =========================================================
   DISCLAIMER
   ========================================================= */

.document-disclaimer {
    margin-top: 55px !important;

    padding: 12px 15px !important;

    border: 1px solid #d7d7d7 !important;
    border-left: 3px solid #888888 !important;

    background: #f8f8f8 !important;

    font-size: 8.5pt !important;
    line-height: 1.55 !important;

    color: #666666 !important;
}

.document-disclaimer strong {
    color: #444444 !important;
}


/* =========================================================
   PRINT
   ========================================================= */

@media print {

    @page {
        size: A4 portrait;
        margin: 0;
    }

    html,
    body {
        margin: 0 !important;
        padding: 0 !important;

        background: #ffffff !important;
    }

    .document-preview {
        width: 195mm !important;
        min-height: 297mm !important;

        margin: 0 !important;

        padding: 20mm 22mm !important;

        border: 0 !important;

        box-shadow: none !important;

        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .document-notary {
        width: 280px !important;
        height: 280px !important;
    }

    .document-notary img {
        width: 280px !important;
        height: 280px !important;
    }

    .document-signature {
        break-inside: avoid !important;
    }

    .document-disclaimer {
        break-inside: avoid !important;
    }
}


/* =========================================================
   MOBILE
   ========================================================= */

@media screen and (max-width: 850px) {

    .document-preview {
        width: calc(100% - 30px) !important;

        min-height: auto !important;

        margin: 15px auto !important;

        padding: 35px 30px !important;
    }
}


@media screen and (max-width: 600px) {

    .document-preview {
        width: calc(100% - 20px) !important;

        min-height: auto !important;

        margin: 10px auto !important;

        padding: 28px 20px !important;

        border: 0 !important;

        box-shadow:
            0 2px 12px rgba(0, 0, 0, .08) !important;
    }

    .document-title {
        font-size: 14pt !important;
    }

    .document-subtitle {
        font-size: 9pt !important;
    }

    .document-body p {
        text-align: left !important;
    }

    .document-signature {
        grid-template-columns: 1fr !important;

        row-gap: 40px !important;

        margin-top: 50px !important;
    }

    .document-signature-block:last-child {
        padding-top: 0 !important;
    }

    .document-notary {
        position: static !important;

        width: 45px !important;
        height: 45px !important;

        margin-left: auto !important;
        margin-bottom: -20px !important;
    }

    .document-notary img {
        width: 45px !important;
        height: 45px !important;
    }
}

</style>

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
            Based on the records maintained by the Human Resources Department, the employee has rendered service in accordance with the terms and conditions of employment established by the institution.
        </p>

        <p>
            This certificate is issued for the purpose of
            <strong><?= $documentPurposeHtml ?></strong>.
        </p>

        <p>
            Issued this <strong><?= $today ?></strong> at BESTLINK College of the Philippines.
        </p>
    </div>
<div class="document-signature">

        <div class="document-notary">
        <img src="/modules/compliance/assets/notary.png" alt="Notary Seal">
    </div>

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






