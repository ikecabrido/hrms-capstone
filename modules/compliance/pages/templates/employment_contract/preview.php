<?php

require_once __DIR__ . '/../../../../../database/db.php';
require_once __DIR__ . '/../../../lib/ajax/document_template_helper.php';

$employeeId = $data['employee_id'] ?? '';
$templateCode = $data['template_code'] ?? 'employment_contract';

$doctype = lc_get_document_type_by_code($db, $templateCode);
$sourceTable = $doctype['source_table'] ?? ($data['source_table'] ?? 'new_hire_table');
$idColumn = $sourceTable === 'new_hire_table' ? 'candidate_id' : ($data['id_column'] ?? 'employee_id');

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

$requiredContractDetails = ['contract_type', 'contract_start_date', 'contract_end_date', 'contract_salary_input'];
$missingContractDetails = [];
foreach ($requiredContractDetails as $key) {
    if (empty($_GET[$key])) {
        $missingContractDetails[] = $key;
    }
}
$contractDetailsComplete = empty($missingContractDetails) || $isOnboardingMode;

if ($isOnboardingMode) {
    $employee['contract_type'] = $employee['employment_status'] ?? '';
    $rawDateHired = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? '');
    if ($rawDateHired !== '') {
        $employee['contract_start_date'] = date('Y-m-d', strtotime($rawDateHired));
        $employee['contract_end_date'] = date('Y-m-d', strtotime('+1 year', strtotime($rawDateHired)));
    } else {
        $employee['contract_start_date'] = '';
        $employee['contract_end_date'] = '';
    }
    $employee['contract_salary_input'] = $employee['monthly_salary'] ?? $employee['negotiated_salary'] ?? '';
    $_GET['contract_type'] = $employee['contract_type'];
    $_GET['contract_start_date'] = $employee['contract_start_date'];
    $_GET['contract_end_date'] = $employee['contract_end_date'];
    $_GET['contract_salary_input'] = $employee['contract_salary_input'];
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

$fullName      = htmlspecialchars((string) ($employee['full_name'] ?? ''), ENT_QUOTES);
$email         = htmlspecialchars((string) ($employee['email'] ?? ''), ENT_QUOTES);
$address       = htmlspecialchars((string) ($employee['address'] ?? ''), ENT_QUOTES);
$phone         = htmlspecialchars((string) ($employee['phone_number'] ?? ''), ENT_QUOTES);
$rawDateHired  = (string) ($employee['date_hired'] ?? $employee['hire_date'] ?? $employee['hired_at'] ?? '');
$dateHired     = $rawDateHired !== '' ? date('F d, Y', strtotime($rawDateHired)) : '';
$birthdate     = !empty($employee['birthdate']) ? date('F d, Y', strtotime($employee['birthdate'])) : '';
$sex           = htmlspecialchars((string) ($employee['sex'] ?? ''), ENT_QUOTES);
$maritalStatus = htmlspecialchars((string) ($employee['marital_status'] ?? ''), ENT_QUOTES);
$department    = htmlspecialchars((string) ($employee['department_name'] ?? ''), ENT_QUOTES);
$position      = htmlspecialchars((string) ($employee['position_name'] ?? ''), ENT_QUOTES);
$status        = htmlspecialchars((string) ($employee['employment_status'] ?? ''), ENT_QUOTES);
$onboarding    = htmlspecialchars((string) ($employee['onboarding_stage'] ?? ''), ENT_QUOTES);
$nationality   = htmlspecialchars((string) ($employee['nationality'] ?? 'Filipino'), ENT_QUOTES);
$qualification = htmlspecialchars((string) ($employee['teacher_qualification'] ?? ''), ENT_QUOTES);
$employeeNo    = htmlspecialchars((string) ($employee['employee_code'] ?? $employee['employee_no'] ?? ''), ENT_QUOTES);
$hrSignatory = lc_get_signature_image();

$today = date('F d, Y');
$documentTitle = 'Employment Contract';

$templateRecord = lc_get_document_template($db, $templateCode);
$templateVersion = $templateRecord['version'] ?? '1.0';
$governingLaw = '';
if ($templateRecord && !empty($templateRecord['governing_law'])) {
    $governingLaw = htmlspecialchars((string) ($templateRecord['governing_law'] ?? ''), ENT_QUOTES);
} else {
    $governingLaw = 'Philippine Labor Code (PD 442)';
}

$contractNumber = '';
try {
    $stmt = $db->prepare("
        SELECT contract_number FROM lc_contracts 
        WHERE employee_id = :eid 
        ORDER BY contract_id DESC 
        LIMIT 1
    ");
    $stmt->execute([':eid' => $employeeId]);
    $contractNumber = htmlspecialchars((string) ($stmt->fetchColumn() ?: ''), ENT_QUOTES);
} catch (Throwable $e) {
    $contractNumber = '';
}

$contractSalaryInput = $_GET['contract_salary_input'] ?? ($employee['monthly_salary'] ?? $employee['negotiated_salary'] ?? '');
$salaryFormatted = $contractSalaryInput !== '' && is_numeric($contractSalaryInput) 
    ? 'PHP ' . number_format((float) $contractSalaryInput, 2) 
    : '_________________';
$salaryWords = $contractSalaryInput !== '' && is_numeric($contractSalaryInput)
    ? numberToWords((float) $contractSalaryInput) . ' Pesos'
    : '_________________';

$rawContractStart = $_GET['contract_start_date'] ?? '';
$rawContractEnd = $_GET['contract_end_date'] ?? '';
$contractStart = $rawContractStart !== '' && strtotime($rawContractStart) !== false ? date('F d, Y', strtotime($rawContractStart)) : '';
$contractEnd = $rawContractEnd !== '' && strtotime($rawContractEnd) !== false ? date('F d, Y', strtotime($rawContractEnd)) : '';

$hrSignatoryName = htmlspecialchars((string) ($_GET['hr_signatory'] ?? ''), ENT_QUOTES);
if ($hrSignatoryName === '') {
    $hrSignatoryName = 'Blythe Enriquez';
}

$authorizedPosition = 'HR Directress';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hrSignatory = '<img src="' . $protocol . $host . '/hrms-capstone/modules/compliance/assets/images.png" alt="Signature" style="height:90px; vertical-align:middle; display:inline-block;">';
?>

<?php if (!empty($validationErrors ?? [])): ?>

<div class="document-validation-errors">

    <h4>
        <i class="bi bi-exclamation-triangle"></i>
        Validation Errors
    </h4>

    <ul>
        <?php foreach ($validationErrors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>

</div>

<?php else: ?>

<div class="document-preview">

    <?php if (!$contractDetailsComplete): ?>
        <div class="document-warning">
            <strong>Contract details incomplete.</strong><br>
            Please provide all required contract details — Contract Type, Start Date, End Date, and Salary — before generating this document.
        </div>
    <?php else: ?>

    <div class="agreement-block">
        <p class="agreement-intro">
            This Employment Contract ("Agreement") is entered into between <strong>________________</strong>, 
            hereinafter referred to as the <strong>"Employee,"</strong> and 
            <strong>________________</strong>, hereinafter referred to as the <strong>"Employer."</strong>
        </p>
        <p class="agreement-intro">
            The Employer and Employee agree to the following terms and conditions governing the Employee's employment.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-title">Employment Terms Notice</h2>
        <p class="section-text">
            This Agreement sets out the principal terms and conditions of employment. It shall be read together with applicable company policies, 
            the Employee Handbook, duly executed employment-related agreements, and applicable laws and regulations.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">1.</h2>
        <h3 class="section-title">POSITION AND DUTIES</h3>
        <p class="section-text">
            The Employee is employed as <strong>________________</strong> under the 
            <strong>________________</strong> department.
        </p>
        <p class="section-text">
            The Employee agrees to perform the duties and responsibilities normally associated with the position, 
            as well as other reasonable duties that may be assigned by the Employer in accordance with the Employee's position, 
            qualifications, and organizational requirements.
        </p>
        <p class="section-text">
            The Employee shall perform assigned duties diligently, professionally, and in accordance with the policies, 
            procedures, rules, and standards established by the Employer.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">2.</h2>
        <h3 class="section-title">COMMENCEMENT OF EMPLOYMENT</h3>
        <p class="section-text">
            The Employee's employment shall commence on <strong>________________</strong>.
        </p>
        <p class="section-text">
            The Employee's date of hire is recorded as <strong>________________</strong>.
        </p>
        <p class="section-text">
            The Employee's employment status shall be <strong>________________</strong>, 
            subject to the terms and conditions stated in this Agreement, applicable company policies, and applicable law.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">3.</h2>
        <h3 class="section-title">PLACE OF WORK</h3>
        <p class="section-text">
            The Employee's primary place of work shall be at <strong>________________</strong>, 
            or at such other location as may reasonably be required by the Employer in connection with the Employee's duties.
        </p>
        <p class="section-text">
            Any temporary or permanent reassignment shall be made in accordance with applicable company policies and Philippine labor laws.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">4.</h2>
        <h3 class="section-title">COMPENSATION</h3>
        <p class="section-text">The Employee shall receive the following compensation:</p>
        <p class="section-text">
            <strong>Monthly Salary:</strong> _________________<br>
            <strong>Salary in Words:</strong> _________________
        </p>
        <p class="section-text">
            In consideration of the services rendered by the Employee, the Employee shall receive a monthly salary of 
            <strong>_________________</strong> (<strong>_________________</strong>), 
            subject to applicable deductions, taxes, government contributions, and other lawful deductions.
        </p>
        <p class="section-text">
            Salary shall be paid in accordance with the Employer's established payroll schedule and applicable compensation policies.
        </p>
        <p class="section-text">
            Any salary adjustment, incentive, allowance, or additional benefit shall be subject to applicable company policies, 
            employment conditions, approval requirements, and applicable law.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">5.</h2>
        <h3 class="section-title">WORKING HOURS</h3>
        <p class="section-text">
            The Employee shall observe the working schedule assigned by the Employer. The standard work schedule shall be determined 
            in accordance with the requirements of the Employee's position and the Employer's operating schedule.
        </p>
        <p class="section-text">
            The Employee shall observe required attendance, timekeeping, break, overtime, and scheduling procedures established by the Employer.
        </p>
        <p class="section-text">
            Work performed beyond the applicable normal working hours shall be subject to applicable company policies and Philippine labor laws.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">6.</h2>
        <h3 class="section-title">EMPLOYEE BENEFITS</h3>
        <p class="section-text">
            The Employee shall be entitled to benefits required by applicable Philippine laws and regulations, 
            as well as benefits provided by the Employer under its approved policies and applicable employment arrangements.
        </p>
        <p class="section-text">
            Applicable statutory benefits may include government-mandated contributions and benefits, 
            subject to eligibility requirements and prevailing laws and regulations.
        </p>
        <p class="section-text">
            Company-provided benefits shall be governed by the applicable policies, rules, eligibility requirements, and benefit plans of the Employer.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">7.</h2>
        <h3 class="section-title">LEAVE AND ABSENCES</h3>
        <p class="section-text">
            The Employee shall be entitled to applicable statutory and company-provided leave benefits 
            subject to eligibility requirements and the Employer's established leave policies.
        </p>
        <p class="section-text">
            Requests for leave shall be submitted through the prescribed procedure and within the required period, 
            except in circumstances where advance notice is not reasonably possible.
        </p>
        <p class="section-text">
            Unauthorized or excessive absences, tardiness, or failure to comply with attendance procedures may be subject to 
            appropriate administrative action in accordance with company policy and applicable law.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">8.</h2>
        <h3 class="section-title">EMPLOYEE RESPONSIBILITIES</h3>
        <p class="section-text">The Employee agrees to:</p>
        <ol class="section-list">
            <li class="section-list-item">Perform assigned duties competently and responsibly.</li>
            <li class="section-list-item">Observe company policies, procedures, and workplace rules.</li>
            <li class="section-list-item">Maintain professional conduct in the workplace.</li>
            <li class="section-list-item">Protect company property and resources.</li>
            <li class="section-list-item">Maintain the confidentiality of sensitive information.</li>
            <li class="section-list-item">Comply with applicable workplace safety requirements.</li>
            <li class="section-list-item">Maintain accurate employment and personal information.</li>
            <li class="section-list-item">Perform duties in accordance with applicable laws and regulations.</li>
        </ol>
    </div>

    <div class="section-block">
        <h2 class="section-number">9.</h2>
        <h3 class="section-title">CONFIDENTIALITY</h3>
        <p class="section-text">
            The Employee acknowledges that, during employment, the Employee may have access to confidential, proprietary, 
            personal, operational, financial, academic, administrative, or other sensitive information belonging to the Employer.
        </p>
        <p class="section-text">
            The Employee agrees not to disclose, reproduce, misuse, or provide confidential information to unauthorized persons 
            except when disclosure is authorized by the Employer or required by law.
        </p>
        <p class="section-text">
            The Employee's confidentiality obligations shall be further governed by the applicable Non-Disclosure and Confidentiality Agreement 
            executed in connection with employment.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">10.</h2>
        <h3 class="section-title">DATA PRIVACY AND PERSONAL INFORMATION</h3>
        <p class="section-text">
            The Employee acknowledges that the Employer may collect, process, store, and use personal information for legitimate 
            employment, administrative, payroll, benefits, compliance, security, and other lawful purposes.
        </p>
        <p class="section-text">
            The Employee agrees to comply with the Employer's applicable data privacy, information security, and records management policies.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">11.</h2>
        <h3 class="section-title">COMPANY PROPERTY</h3>
        <p class="section-text">
            All equipment, documents, records, identification cards, access credentials, files, materials, and other property 
            provided to the Employee for work purposes shall remain the property of the Employer unless otherwise stated in writing.
        </p>
        <p class="section-text">
            Upon separation from employment, the Employee shall return all company property in the Employee's possession or control, 
            subject to applicable company procedures.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">12.</h2>
        <h3 class="section-title">WORKPLACE CONDUCT</h3>
        <p class="section-text">
            The Employee shall maintain professional and respectful conduct and shall comply with the Employer's code of conduct, 
            Employee Handbook, workplace policies, and applicable laws.
        </p>
        <p class="section-text">
            Conduct that violates applicable policies or laws may result in appropriate administrative action following the Employer's 
            established procedures and applicable legal requirements.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">13.</h2>
        <h3 class="section-title">HEALTH, SAFETY, AND SECURITY</h3>
        <p class="section-text">
            The Employee shall comply with applicable workplace health, safety, emergency, security, and access-control procedures.
        </p>
        <p class="section-text">
            The Employee shall immediately report workplace hazards, incidents, accidents, security concerns, or other matters 
            requiring attention through the appropriate reporting channels.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">14.</h2>
        <h3 class="section-title">INTELLECTUAL PROPERTY AND WORK PRODUCT</h3>
        <p class="section-text">
            Work products, documents, materials, records, and other outputs created by the Employee in the course of performing 
            assigned employment duties shall be handled in accordance with applicable law, company policies, and any applicable written agreements.
        </p>
        <p class="section-text">
            The Employee shall not reproduce, distribute, commercialize, or otherwise use protected company materials outside the scope 
            of authorized employment duties without proper authorization.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">15.</h2>
        <h3 class="section-title">COMPANY POLICIES</h3>
        <p class="section-text">
            The Employee acknowledges that the Employer maintains policies, procedures, rules, and guidelines governing employment and workplace conduct.
        </p>
        <p class="section-text">
            The Employee agrees to familiarize themselves with and comply with applicable policies, including the Employee Handbook and 
            other officially issued workplace policies.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">16.</h2>
        <h3 class="section-title">DISCIPLINE AND ADMINISTRATIVE ACTION</h3>
        <p class="section-text">
            Violations of company policies, employment obligations, or applicable laws may result in appropriate administrative action 
            in accordance with the Employer's established procedures and applicable Philippine labor laws.
        </p>
        <p class="section-text">
            Any disciplinary action shall be undertaken in accordance with applicable procedural and substantive requirements.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">17.</h2>
        <h3 class="section-title">TERMINATION AND SEPARATION</h3>
        <p class="section-text">
            Employment may end through resignation, expiration of an applicable employment period, termination for lawful cause, 
            or other circumstances recognized by applicable law.
        </p>
        <p class="section-text">
            The Employer and Employee shall comply with applicable notice, clearance, turnover, return-of-property, final-pay, 
            and other separation requirements.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">18.</h2>
        <h3 class="section-title">GOVERNING LAW</h3>
        <p class="section-text">
            This Agreement shall be interpreted and implemented in accordance with applicable laws and regulations of the Republic of the Philippines, 
            including applicable provisions of the Philippine Labor Code and relevant labor and employment regulations.
        </p>
        <p class="section-text">
            <strong>Governing Legal Reference:</strong> <?= $governingLaw ?>
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">19.</h2>
        <h3 class="section-title">ENTIRE AGREEMENT</h3>
        <p class="section-text">
            This Agreement, together with applicable employment policies, the Employee Handbook, and other duly executed employment-related agreements, 
            constitutes the understanding between the Employer and Employee concerning the matters covered herein, subject to applicable law.
        </p>
        <p class="section-text">
            Any amendment or modification to this Agreement shall be made in accordance with applicable company procedures and legal requirements.
        </p>
    </div>

    <div class="section-block">
        <h2 class="section-number">20.</h2>
        <h3 class="section-title">EMPLOYEE ACKNOWLEDGMENT</h3>
        <p class="section-text">
            By signing below, the Employee confirms that the Employee has read and understood this Employment Contract and agrees to comply with 
            its applicable terms and conditions, the Employee Handbook, company policies, and applicable laws.
        </p>
        <p class="section-text">
            The Employee further acknowledges receipt of the applicable onboarding documents and understands that questions concerning the terms of 
            employment may be raised with the appropriate Human Resources representative or authorized company representative.
        </p>
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


    <div class="document-footer">
        <div class="footer-left">
            <strong>________________</strong><br>
            Human Resources Department
        </div>
        <div class="footer-right">
            <strong>Contract No.:</strong> <?= $contractNumber ?: '________________' ?><br>
            <strong>Version:</strong> <?= htmlspecialchars($templateVersion) ?><br>
            <strong>Date:</strong> <?= $today ?>
        </div>
    </div>

    <?php endif; ?>

</div>

<?php endif; ?>

<style>
/* =========================================================
   EMPLOYMENT CONTRACT - NEW FORMAT
   ========================================================= */

.document-preview {
    width: 100%;
    max-width: 900px;
    margin: 15px auto;
    padding: 18px 22px;
    background: #ffffff;
    border: 1px solid #d7dbe3;
    border-radius: 8px;
    box-sizing: border-box;
    font-family: "Times New Roman", Times, serif;
    font-size: 10.5px;
    line-height: 1.45;
    color: #222;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.document-header {
    text-align: center;
    margin-bottom: 10px;
}

.document-institution {
    font-family: "Times New Roman", Times, serif;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #111;
    margin-bottom: 3px;
}

.document-dept {
    font-family: "Times New Roman", Times, serif;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #444;
    margin-bottom: 10px;
}

.document-title {
    margin: 0;
    text-align: center;
    font-family: "Times New Roman", Times, serif;
    font-size: 18px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #111;
}

.document-subtitle {
    margin: 4px 0 0;
    text-align: center;
    font-family: "Times New Roman", Times, serif;
    font-size: 10px;
    line-height: 1.5;
    color: #666;
}

.document-separator {
    margin: 8px 0;
    border: 0;
    border-top: 1px solid #cfcfcf;
}

.document-information {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
    font-family: "Times New Roman", Times, serif;
}

.document-information td {
    padding: 2px 0;
    vertical-align: top;
    font-size: 10px;
}

.document-information td:first-child {
    width: 160px;
    font-weight: 700;
    color: #333;
}

.document-information td:last-child {
    color: #222;
}

.document-warning {
    margin-top: 10px;
    padding: 10px;
    border: 1px solid #d8dce4;
    border-radius: 8px;
    background: #fafbfc;
    text-align: center;
    font-family: "Times New Roman", Times, serif;
    font-size: 10px;
    line-height: 1.45;
    color: #666;
}

.document-warning strong {
    color: #333;
}

.document-body {
    font-family: "Times New Roman", Times, serif;
    font-size: 10.5px;
    line-height: 1.45;
    color: #222;
}

.agreement-block {
    margin-bottom: 8px;
}

.agreement-intro {
    margin: 0 0 10px;
    text-align: justify;
}

.section-block {
    margin-bottom: 7px;
}

.section-number {
    display: inline;
    margin: 0;
    font-family: "Times New Roman", Times, serif;
    font-size: 12px;
    font-weight: 700;
    color: #111;
}

.section-title {
    display: inline;
    margin: 0;
    font-family: "Times New Roman", Times, serif;
    font-size: 12px;
    font-weight: 700;
    color: #111;
    text-transform: uppercase;
}

.section-text {
    margin: 3px 0 0;
    text-align: justify;
}

.section-list {
    margin: 3px 0 0;
    padding-left: 22px;
}

.section-list-item {
    margin-bottom: 1px;
}

.signatures-block {
    margin-top: 14px;
    padding-top: 8px;
    border-top: 1px solid #cfcfcf;
}

.signatures-title {
    margin: 0 0 4px;
    text-align: center;
    font-family: "Times New Roman", Times, serif;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #111;
}

.signatures-intro {
    margin: 0 0 8px;
    text-align: center;
    font-size: 9px;
    color: #555;
}

.signature-group {
    margin-bottom: 10px;
}

.signature-group-title {
    margin: 0 0 3px;
    font-family: "Times New Roman", Times, serif;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    color: #333;
}

.signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 5px;
}

.signature-table td {
    padding: 2px 0;
    vertical-align: top;
    font-size: 10px;
}

.signature-table td:first-child {
    width: 160px;
    font-weight: 700;
    color: #333;
}

.signature-table td:last-child {
    color: #222;
}

.sig-line-group {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    margin-top: 2px;
}

.sig-line-label {
    font-size: 9px;
    color: #555;
    margin-bottom: 1px;
}

.sig-line {
    border-bottom: 1px solid #222;
    height: 18px;
    min-width: 140px;
}

.sig-line.short {
    min-width: 100px;
}


.document-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-top: 12px;
    padding-top: 6px;
    border-top: 1px solid #cfcfcf;
    font-size: 9px;
    color: #555;
}

.footer-left {
    text-align: left;
}

.footer-right {
    text-align: right;
}

.document-notary {
    margin-top: 8px;
    text-align: center;
}

.document-notary img {
    height: 180px;
    opacity: 0.22;
}

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


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 768px) {
    .document-preview {
        padding: 18px;
    }

    .document-title {
        font-size: 18px;
    }

    .document-information td:first-child {
        width: 140px;
    }

    .signature-group {
        page-break-inside: avoid;
    }
}

@media (max-width: 576px) {
    .document-preview {
        margin: 8px;
        padding: 12px;
        border-radius: 6px;
    }

    .document-title {
        font-size: 16px;
        letter-spacing: 0.04em;
    }

    .document-information,
    .document-information tbody,
    .document-information tr,
    .document-information td {
        display: block;
        width: 100%;
    }

    .document-information td:first-child {
        width: 100%;
        padding-bottom: 1px;
    }

    .document-information td:last-child {
        padding-top: 0;
        margin-bottom: 8px;
    }

    .sig-line-group {
        flex-direction: column;
        align-items: flex-start;
    }

    .document-footer {
        flex-direction: column;
        gap: 8px;
    }

    .footer-right {
        text-align: left;
    }
}


/* =========================================================
   PRINT / PDF
   ========================================================= */

@media print {
    .document-preview {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
        background: #ffffff;
        font-size: 10pt;
        line-height: 1.45;
    }

    .document-title {
        font-size: 16pt;
    }

    .document-subtitle {
        font-size: 10pt;
    }

    .document-separator {
        border-top: 1px solid #000;
    }


    .document-validation-errors,
    .document-empty-state,
    .document-warning {
        break-inside: avoid;
    }

    .document-body {
        break-inside: auto;
    }

    .section-block {
        break-inside: avoid;
    }

    .signatures-block {
        break-inside: avoid;
    }
}

</style>



