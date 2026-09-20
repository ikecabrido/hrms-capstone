<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$employeeId = $data['employee_id'];
$templateCode = $data['template_code'];
$requestId = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';

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
                LEFT JOIN em_positions p ON p.position_id = e.position_id
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
$documentTitle = 'Quitclaim and Release';

$employer = lc_get_active_employer($db);
$templateRecord = lc_get_document_template($db, $templateCode);

$savedFormData = [];
if ($requestId !== '') {
    try {
        $stmt = $db->prepare("SELECT document_form_data FROM lc_document_requests WHERE request_id = :id LIMIT 1");
        $stmt->execute([':id' => $requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['document_form_data'])) {
            $savedFormData = json_decode($row['document_form_data'], true) ?: [];
        }
    } catch (Throwable $e) {
        $savedFormData = [];
    }
}

function numberToWords($number) {
    $number = (float) str_replace([',', ' '], '', $number);
    if ($number == 0) return 'Zero';
    
    $negative = $number < 0;
    $number = abs($number);
    
    $millions = floor($number / 1000000);
    $thousands = floor(($number % 1000000) / 1000);
    $remainder = $number % 1000;
    
    $words = [];
    if ($negative) $words[] = 'Negative';
    
    if ($millions > 0) {
        $words[] = numberToWordsChunk($millions) . ' Million';
    }
    if ($thousands > 0) {
        $words[] = numberToWordsChunk($thousands) . ' Thousand';
    }
    if ($remainder > 0) {
        $words[] = numberToWordsChunk($remainder);
    }
    
    return implode(' ', $words);
}

function numberToWordsChunk($number) {
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    
    $number = (int) $number;
    if ($number == 0) return '';
    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[floor($number / 10)] . ($number % 10 > 0 ? ' ' . $ones[$number % 10] : '');
    if ($number < 1000) return $ones[floor($number / 100)] . ' Hundred' . ($number % 100 > 0 ? ' ' . numberToWordsChunk($number % 100) : '');
    return $number;
}

$settlementAmount = $savedFormData['settlement_amount'] ?? '';
$settlementWords = $settlementAmount !== '' ? numberToWords($settlementAmount) . ' Pesos' : '';
?>

<form method="post" action="lib/api/quitclaim_save.php" id="quitclaimForm">
<input type="hidden" name="request_id" value="<?= htmlspecialchars($requestId) ?>">
<input type="hidden" name="employee_id" value="<?= htmlspecialchars($employeeId) ?>">

<div class="document-preview">

    <div class="document-header">
        <h2 class="document-title">QUITCLAIM AND RELEASE</h2>
        <p class="document-subtitle">SEPARATION AND FINAL SETTLEMENT RELEASE</p>
    </div>

    <hr class="document-separator">

    <table class="document-information">

        <tr>
            <td class="info-label">Employee Name</td>
            <td class="info-value">
                <input type="text" name="employee_name" id="qc_employee_name" value="<?= htmlspecialchars($savedFormData['employee_name'] ?? $fullName, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Employee Number</td>
            <td class="info-value">
                <input type="text" name="employee_number" id="qc_employee_number" value="<?= htmlspecialchars($savedFormData['employee_number'] ?? $employeeNo, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Employee ID</td>
            <td class="info-value">
                <input type="text" name="employee_id_number" id="qc_employee_id" value="<?= htmlspecialchars($savedFormData['employee_id_number'] ?? $employeeId, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Department</td>
            <td class="info-value">
                <input type="text" name="department" id="qc_department" value="<?= htmlspecialchars($savedFormData['department'] ?? ($department ?: 'BTVTED'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Position</td>
            <td class="info-value">
                <input type="text" name="position" id="qc_position" value="<?= htmlspecialchars($savedFormData['position'] ?? ($position ?: 'College Instructor'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Date Executed</td>
            <td class="info-value">
                <input type="date" name="date_issued" id="qc_date_issued" value="<?= htmlspecialchars($savedFormData['date_issued'] ?? '2026-08-16', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
            </td>
        </tr>

        <tr>
            <td class="info-label">Settlement Amount (PHP)</td>
            <td class="info-value">
                <input type="number" name="settlement_amount" id="qc_settlement_amount" value="<?= htmlspecialchars($savedFormData['settlement_amount'] ?? '', ENT_QUOTES) ?>" step="0.01" min="0" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:60%;" placeholder="0.00">
            </td>
        </tr>

    </table>

    <div class="document-body">
        <p>
            <strong>KNOW ALL MEN BY THESE PRESENTS:</strong>
        </p>

        <p>
            I,
            <span id="qc_body_name"><?= htmlspecialchars($savedFormData['employee_name'] ?? $fullName, ENT_QUOTES) ?></span>,
            hereby acknowledge that I have received to my full satisfaction all salaries,
            wages, benefits, allowances, incentives, separation pay, accrued leave credits,
            reimbursements, and any other monetary or non-monetary benefits due to me arising
            from my employment with the Company, including but not limited to the settlement
            amount of <strong><span id="qc_body_amount"><?= $settlementAmount !== '' ? '₱' . number_format((float)$settlementAmount, 2) : '___________' ?></span></strong>
            (<span id="qc_body_amount_words"><?= htmlspecialchars($settlementWords, ENT_QUOTES) ?></span>).
        </p>

        <p>
            In consideration of the foregoing, I voluntarily and freely execute this
            <strong>Quitclaim and Release</strong> in favor of the Company. I hereby release,
            waive, discharge, and forever quitclaim the Company, its officers,
            directors, em_employees, and representatives from any and all actions,
            claims, demands, liabilities, causes of action, or obligations arising
            from or connected with my employment or separation from employment,
            except those expressly provided by applicable law.
        </p>

        <p>
            I declare that I have read and fully understood the contents of this
            document, that I executed it voluntarily, without force, intimidation,
            fraud, or undue influence, and with full knowledge of its legal effect.
        </p>
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
            <div class="sig-name">
                <input type="text" name="employee_signature" id="qc_employee_signature" value="<?= htmlspecialchars($savedFormData['employee_signature'] ?? $fullName, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;max-width:300px;font-weight:bold;">
            </div>
            <div class="sig-date">
                <input type="text" name="signature_position" id="qc_signature_position" value="<?= htmlspecialchars($savedFormData['position'] ?? ($position ?: 'College Instructor'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;max-width:300px;">
            </div>
        </div>

    </div>

    <div style="margin-top:40px;">
        <strong>Witnesses</strong>
        <br><br>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:50%; padding-right:20px; vertical-align:top;">
                    <strong>Witness 1</strong><br>
                    <input type="text" name="witness_name_1" id="qc_witness_1" value="<?= htmlspecialchars($savedFormData['witness_name_1'] ?? '', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;" placeholder="Full Name"><br><br>
                    _______________________________<br>
                    Signature over Printed Name<br><br>
                    ID No.: <input type="text" name="witness_id_1" id="qc_witness_id_1" value="<?= htmlspecialchars($savedFormData['witness_id_1'] ?? '', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:60%;" placeholder="ID Number">
                </td>
                <td style="width:50%; padding-left:20px; vertical-align:top;">
                    <strong>Witness 2</strong><br>
                    <input type="text" name="witness_name_2" id="qc_witness_2" value="<?= htmlspecialchars($savedFormData['witness_name_2'] ?? '', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;" placeholder="Full Name"><br><br>
                    _______________________________<br>
                    Signature over Printed Name<br><br>
                    ID No.: <input type="text" name="witness_id_2" id="qc_witness_id_2" value="<?= htmlspecialchars($savedFormData['witness_id_2'] ?? '', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:60%;" placeholder="ID Number">
                </td>
            </tr>
        </table>
    </div>

    <div class="document-disclaimer">
        <strong>Academic Disclaimer</strong><br><br>
        This document is a <strong>system-generated sample document</strong> developed solely for academic, research, and demonstration purposes as part of the <strong>Human Resource Management System with Legal Compliance Module</strong> undergraduate thesis project.
    </div>

    <div style="margin-top:20px; text-align:right;">
        <button type="submit" class="sa-btn-primary">Save Quitclaim Form</button>
    </div>
</div>
</form>

<script>
(function(){
    var form = document.getElementById('quitclaimForm');
    var amountInput = document.getElementById('qc_settlement_amount');
    var bodyName = document.getElementById('qc_body_name');
    var bodyAmount = document.getElementById('qc_body_amount');
    var bodyAmountWords = document.getElementById('qc_body_amount_words');

    function updateBodyName() {
        var name = document.getElementById('qc_employee_name').value || '________________';
        if (bodyName) bodyName.textContent = name;
    }

    function updateSettlement() {
        var val = amountInput ? amountInput.value : '';
        if (bodyAmount) {
            bodyAmount.textContent = val ? '₱' + parseFloat(val).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '___________';
        }
        if (bodyAmountWords) {
            if (val === '' || parseFloat(val) <= 0) {
                bodyAmountWords.textContent = '';
            } else {
                bodyAmountWords.textContent = numberToWords(parseFloat(val)) + ' Pesos';
            }
        }
    }

    function updateSignaturePosition() {
        var pos = document.getElementById('qc_position').value || 'Employee';
        var sigPos = document.getElementById('qc_signature_position');
        if (sigPos) sigPos.value = pos;
    }

    function numberToWords(num) {
        var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        
        num = Math.abs(Math.round(num));
        if (num == 0) return 'Zero';
        
        var words = [];
        var millions = Math.floor(num / 1000000);
        var thousands = Math.floor((num % 1000000) / 1000);
        var remainder = num % 1000;
        
        if (millions > 0) words.push(chunkToWords(millions) + ' Million');
        if (thousands > 0) words.push(chunkToWords(thousands) + ' Thousand');
        if (remainder > 0) words.push(chunkToWords(remainder));
        
        return words.join(' ');
    }
    
    function chunkToWords(n) {
        var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 > 0 ? ' ' + ones[n % 10] : '');
        if (n < 1000) return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 > 0 ? ' ' + chunkToWords(n % 100) : '');
        return '';
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'lib/api/quitclaim_save.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    alert('Quitclaim form saved successfully.');
                    location.reload();
                } else {
                    alert('Failed to save form.');
                }
            };
            xhr.onerror = function() {
                alert('Network error.');
            };
            var params = new URLSearchParams(new FormData(form));
            xhr.send(params.toString());
        });
    }

    var inputs = ['qc_employee_name', 'qc_settlement_amount'];
    inputs.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                if (id === 'qc_employee_name') updateBodyName();
                if (id === 'qc_settlement_amount') updateSettlement();
            });
        }
    });

    updateBodyName();
    updateSettlement();
    updateSignaturePosition();
})();
</script>


