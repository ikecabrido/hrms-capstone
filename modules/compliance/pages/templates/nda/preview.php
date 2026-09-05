<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$employeeId = $data['employee_id'] ?? '';
$templateCode = 'nda';
$documentType = 'Non-Disclosure Agreement (NDA)';

    $employee = null;
    if ($employeeId !== '') {
        try {
            if ($sourceTable === 'new_hire_table') {
                $stmt = $db->prepare("
                    SELECT e.*, 
                           e.full_name,
                           COALESCE(d.department_name, '') AS department_name, 
                           COALESCE(p.position_name, '') AS position_name
                    FROM {$sourceTable} e
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions   p ON p.position_id = e.position_id
                    WHERE e.{$idColumn} = :id
                    LIMIT 1
                ");
            } elseif ($sourceTable === 'rao_hired') {
                $stmt = $db->prepare("
                    SELECT rh.*, 
                           CONCAT(COALESCE(rh.first_name, ''), ' ', COALESCE(rh.last_name, '')) AS full_name,
                           COALESCE(d.department_name, 'N/A') AS department_name, 
                           COALESCE(p.position_name, 'N/A') AS position_name
                    FROM rao_hired rh
                    LEFT JOIN em_departments d ON d.department_name = rh.department
                    LEFT JOIN em_positions   p ON p.position_name = rh.position
                    WHERE rh.{$idColumn} = :id
                    LIMIT 1
                ");
            } else {
                $stmt = $db->prepare("
                    SELECT e.*, 
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
                           COALESCE(d.department_name, '') AS department_name, 
                           COALESCE(p.position_name, '') AS position_name
                    FROM {$sourceTable} e
                    LEFT JOIN em_departments d ON d.department_id = e.department_id
                    LEFT JOIN em_positions   p ON p.position_id = e.position_id
                    WHERE e.{$idColumn} = :id
                    LIMIT 1
                ");
            }
            $stmt->execute([':id' => $employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $employee = null;
        }

        if (!$employee) {
            try {
                $stmt = $db->prepare("
                    SELECT e.*, 
                           CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name,
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

        if (!$employee && $sourceTable !== 'rao_hired') {
            try {
                $stmt = $db->prepare("
                    SELECT rh.*, 
                           CONCAT(COALESCE(rh.first_name, ''), ' ', COALESCE(rh.last_name, '')) AS full_name,
                           COALESCE(d.department_name, 'N/A') AS department_name, 
                           COALESCE(p.position_name, 'N/A') AS position_name
                    FROM rao_hired rh
                    LEFT JOIN em_departments d ON d.department_name = rh.department
                    LEFT JOIN em_positions   p ON p.position_name = rh.position
                    WHERE rh.id = :id OR rh.application_id = :id
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

$isOnboardingMode = !empty($_GET['onboarding']);

if (!$isOnboardingMode) {
    lc_apply_meta_overrides($employee);
}

$templateRecord = lc_get_document_template($db, $templateCode);

if ($templateRecord && !empty($templateRecord['template_content'])) {
    $documentBody = lc_replace_placeholders($templateRecord['template_content'], [], []);
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $documentBody = lc_replace_placeholders(lc_get_fallback_template('nda'), [], []);
    $governingLaw = 'Philippine Labor Code (PD 442)';
}

$today = date('F d, Y');
$hrSignatory = lc_get_signature_image();
$hrSignatoryName = htmlspecialchars((string) ($_GET['hr_signatory'] ?? ''), ENT_QUOTES);
if ($hrSignatoryName === '') {
    $hrSignatoryName = 'Blythe Enriquez';
}
$fullName = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$rawDateHired = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? $employee['hired_at'] ?? '');
$dateHired = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
?>

<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">NON-DISCLOSURE AGREEMENT</h2>
        <p class="document-subtitle">Governed by <?= $governingLaw ?: 'Philippine Labor Code (Presidential Decree No. 442)' ?></p>
    </div>

    <hr class="document-separator">

    <table class="document-information">
        <tr>
            <td class="info-label">Employee Name</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Employee Number</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Department</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Position</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Agreement Date</td>
            <td class="info-value">________________</td>
        </tr>
        <tr>
            <td class="info-label">Date Hired</td>
            <td class="info-value">________________</td>
        </tr>
    </table>

    <div class="document-body">
        <?= $documentBody ?>
    </div>

    <div class="document-signature">

        <div class="document-signature-block">
            <div class="sig-image">
                <?= $hrSignatory ?>
            </div>
            <div class="sig-text">
                <div class="sig-name"><?= htmlspecialchars($hrSignatoryName) ?></div>
                <div class="sig-role">HR Directress</div>
                <div class="sig-date">Date: <?= $today ?></div>
            </div>
        </div>

    </div>

</div>

<style>
.document-signature {
    margin-top: 24px;
}

.document-signature-block {
    display: inline-block;
    width: 100%;
    vertical-align: top;
    margin-right: 0;
}

.sig-image {
    margin-bottom: 6px;
}

.sig-image img {
    height: 90px;
    vertical-align: middle;
    display: inline-block;
}

.sig-text {
    font-size: 12px;
    color: #333;
}

.sig-name {
    font-weight: 700;
    color: #0f2b4d;
    margin-top: 4px;
}

.sig-role {
    font-size: 11px;
    color: #666;
}

.sig-date {
    font-size: 11px;
    color: #666;
    margin-top: 4px;
}
</style>
