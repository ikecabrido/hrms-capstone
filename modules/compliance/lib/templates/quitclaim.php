<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../../../../database/db.php';
require_once dirname(__DIR__) . '/ajax/document_template_helper.php';

$employeeId   = isset($_GET['employee_id']) ? trim((string) $_GET['employee_id']) : '';
$documentType = isset($_GET['document_type']) ? trim((string) $_GET['document_type']) : '';
$requestId    = isset($_GET['request_id']) ? trim((string) $_GET['request_id']) : '';

$employee = null;
$sourceLabel = 'em_employees';
$savedFormData = [];

if ($requestId !== '') {
    try {
        if (!isset($db)) {
            $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
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
            LEFT JOIN em_positions p ON p.position_id = e.position_id
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
            if (!isset($db)) {
                $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
            $stmt = $db->prepare("
                SELECT e.*, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, e.employee_code AS employee_no, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
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

    if (!$employee) {
        try {
            if (!isset($db)) {
                $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
            $stmt = $db->prepare("
                SELECT rh.*, COALESCE(d.department_name, 'N/A') AS department_name, COALESCE(p.position_name, 'N/A') AS position_name
                FROM rao_hired rh
                LEFT JOIN em_departments d ON d.department_name = rh.department
                LEFT JOIN em_positions p ON p.position_name = rh.position
                WHERE rh.id = :id
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

$fullName    = htmlspecialchars((string)($employee['full_name'] ?? ''), ENT_QUOTES);
$employeeNo  = htmlspecialchars((string)($employee['employee_no'] ?? ''), ENT_QUOTES);
$department  = htmlspecialchars((string)($employee['department_name'] ?? ''), ENT_QUOTES);
$position    = htmlspecialchars((string)($employee['position_name'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();
$today = date('F d, Y');

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

<form method="post" action="?page=preview-document&request_id=<?= htmlspecialchars($requestId) ?>&ajax=quitclaim_save" id="quitclaimForm">
<input type="hidden" name="request_id" value="<?= htmlspecialchars($requestId) ?>">
<input type="hidden" name="employee_id" value="<?= htmlspecialchars($employeeId) ?>">

<div class="dg-template-frame">

<div style="
        padding:25px 30px;
        border:1px solid #d7dbe3;
        border-radius:8px;
        background:#fff;
        font-family:'Times New Roman', Times, serif;
        font-size:13px;
        line-height:1.8;
        color:#222;
        margin-top:20px;
    ">

        <h2 style="
            margin:0;
            text-align:center;
            text-transform:uppercase;
            letter-spacing:.08em;
            font-size:26px;
        ">
            QUITCLAIM AND RELEASE
        </h2>

        <p style="
            margin:10px 0 30px;
            text-align:center;
            color:#666;
            font-size:14px;
        ">
            Full and Final Settlement of Employment Claims
        </p>

        <hr style="margin:0 0 30px;">

        <table style="
            width:100%;
            border-collapse:collapse;
            margin-bottom:35px;
        ">

            <tr>
                <td style="width:190px;padding:9px 0;">
                    <strong>Employee Name</strong>
                </td>
                <td>
                    <input type="text" name="employee_name" id="qc_employee_name" value="<?= htmlspecialchars($savedFormData['employee_name'] ?? $fullName, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Employee Number</strong>
                </td>
                <td>
                    <input type="text" name="employee_number" id="qc_employee_number" value="<?= htmlspecialchars($savedFormData['employee_number'] ?? $employeeNo, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Employee ID</strong>
                </td>
                <td>
                    <input type="text" name="employee_id_number" id="qc_employee_id" value="<?= htmlspecialchars($savedFormData['employee_id_number'] ?? $employeeId, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Department</strong>
                </td>
                <td>
                    <input type="text" name="department" id="qc_department" value="<?= htmlspecialchars($savedFormData['department'] ?? ($department ?: 'BTVTED'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Position</strong>
                </td>
                <td>
                    <input type="text" name="position" id="qc_position" value="<?= htmlspecialchars($savedFormData['position'] ?? ($position ?: 'College Instructor'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Date Executed</strong>
                </td>
                <td>
                    <input type="date" name="date_issued" id="qc_date_issued" value="<?= htmlspecialchars($savedFormData['date_issued'] ?? '2026-08-16', ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;">
                </td>
            </tr>

            <tr>
                <td style="padding:9px 0;">
                    <strong>Settlement Amount (PHP)</strong>
                </td>
                <td>
                    <input type="number" name="settlement_amount" id="qc_settlement_amount" value="<?= htmlspecialchars($savedFormData['settlement_amount'] ?? '', ENT_QUOTES) ?>" step="0.01" min="0" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:60%;" placeholder="0.00">
                </td>
            </tr>

        </table>

        <div class="dg-document-body" style="
            text-align:justify;
            line-height:1.85;
            margin-bottom:45px;
        ">

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

                <input type="text" name="employee_signature" id="qc_employee_signature" value="<?= htmlspecialchars($savedFormData['employee_signature'] ?? $fullName, ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;max-width:300px;font-weight:bold;">

                <br>

                <input type="text" name="position" id="qc_signature_position" value="<?= htmlspecialchars($savedFormData['position'] ?? ($position ?: 'College Instructor'), ENT_QUOTES) ?>" style="border:none;border-bottom:1px solid #333;background:transparent;font-family:inherit;font-size:inherit;padding:2px 0;width:100%;max-width:300px;">

                <br><br>

                _______________________________

                <br>

                Employee Signature

                <br>

                Date: <?= $today ?>

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
    var dateInput = document.getElementById('qc_date_issued');

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

    var inputs = ['qc_employee_name', 'qc_employee_number', 'qc_employee_id', 'qc_settlement_amount'];
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

