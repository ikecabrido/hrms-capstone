<?php

$defaultContractType = !empty($_GET['contract_type']) ? $_GET['contract_type'] : (!empty($data['raw_employment_status']) ? $data['raw_employment_status'] : 'Regular');
$defaultStart = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : (!empty($data['raw_date_hired']) ? date('Y-m-d', strtotime($data['raw_date_hired'])) : date('Y-m-d'));
$baseForEnd = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : $data['raw_date_hired'];
$defaultEnd = !empty($_GET['contract_end_date']) ? $_GET['contract_end_date'] : (!empty($baseForEnd) ? date('Y-m-d', strtotime('+1 year', strtotime($baseForEnd))) : date('Y-m-d', strtotime('+1 year')));
$defaultSalary = !empty($_GET['contract_salary_input']) ? $_GET['contract_salary_input'] : '';

$salaryMin = 0;
$salaryMax = 0;
$hasSalaryRange = false;

if (!empty($data['employee_id']) && isset($db)) {
    try {
        $sourceTable = $data['source_table'] ?? 'em_employees';
        $idColumn = $data['id_column'] ?? 'employee_id';
        $stmt = $db->prepare("SELECT position_id FROM {$sourceTable} WHERE {$idColumn} = :id LIMIT 1");
        $stmt->execute([':id' => $data['employee_id']]);
        $empRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $positionId = (int)($empRow['position_id'] ?? 0);

        if ($positionId > 0) {
            $stmt = $db->prepare("SELECT minimum_wage, maximum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'No' AND position_id = :pid LIMIT 1");
            $stmt->execute([':pid' => $positionId]);
            $wageRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($wageRow) {
                $salaryMin = (float)($wageRow['minimum_wage'] ?? 0);
                $salaryMax = (float)($wageRow['maximum_wage'] ?? 0);
                $hasSalaryRange = $salaryMax > $salaryMin;
            }
        }

        if (!$hasSalaryRange) {
            $stmt = $db->prepare("SELECT minimum_wage, maximum_wage FROM lc_minimum_wage WHERE status = 'Active' AND is_global = 'Yes' LIMIT 1");
            $stmt->execute();
            $globalRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($globalRow) {
                $salaryMin = (float)($globalRow['minimum_wage'] ?? 0);
                $salaryMax = (float)($globalRow['maximum_wage'] ?? 0);
                $hasSalaryRange = $salaryMax > $salaryMin;
            }
        }
    } catch (Throwable $e) {
        $hasSalaryRange = false;
    }
}

if ($hasSalaryRange) {
    $step = ($salaryMax - $salaryMin) / 2;
    $options = [
        $salaryMin,
        round($salaryMin + $step, 2),
        $salaryMax
    ];
    $options = array_unique($options);
    sort($options);

    $salaryFieldHtml = '<select id="dgSalary" name="contract_salary_input">';
    foreach ($options as $opt) {
        $selected = ((string)$defaultSalary === (string)$opt) ? ' selected' : '';
        $salaryFieldHtml .= '<option value="' . htmlspecialchars($opt) . '"' . $selected . '>' . htmlspecialchars(number_format($opt, 2)) . '</option>';
    }
    $salaryFieldHtml .= '</select>';
} else {
    $baseSalary = !empty($defaultSalary) && is_numeric($defaultSalary) ? (float) $defaultSalary : 23000.00;
    $opt1 = $baseSalary;
    $opt2 = round($baseSalary * 1.05, 2);
    $opt3 = round($baseSalary * 1.10, 2);
    $fallbackOptions = [
        $opt1 => '₱' . number_format($opt1, 2),
        $opt2 => '₱' . number_format($opt2, 2),
        $opt3 => '₱' . number_format($opt3, 2),
    ];
    $salaryFieldHtml = '<select id="dgSalary" name="contract_salary_input">';
    foreach ($fallbackOptions as $val => $label) {
        $selected = (abs((float)$defaultSalary - $val) < 0.01) ? ' selected' : '';
        $salaryFieldHtml .= '<option value="' . htmlspecialchars((string)$val) . '"' . $selected . '>' . $label . '</option>';
    }
    $salaryFieldHtml .= '</select>';
}

$contractTypeOptions = '';
$validContractTypes = ['Regular', 'Probationary', 'Fixed-Term', 'Project', 'Seasonal', 'Casual', 'Part-Time'];
foreach ($validContractTypes as $ct) {
    $selected = ($ct === $defaultContractType) ? ' selected' : '';
    $contractTypeOptions .= '<option value="' . htmlspecialchars($ct) . '"' . $selected . '>' . htmlspecialchars($ct) . '</option>';
}
?>

<form class="cd-date-form" method="post" action="" data-skip>
    <input type="hidden" name="action" value="apply_employment_contract">
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field-row" style="margin-top: 16px;">
        <div class="cd-field">
            <label for="dgContractType">Employment Status</label>
            <select id="dgContractType" name="contract_type">
                <?= $contractTypeOptions ?>
            </select>
        </div>

        <div class="cd-field">
            <label for="dgEndDate">End Date</label>
            <input type="date" id="dgEndDate" name="contract_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
        </div>
    </div>

    <div class="cd-field-row">
        <div class="cd-field">
            <label for="dgStartDate">Start Date</label>
            <input type="date" id="dgStartDate" name="contract_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
        </div>

        <div class="cd-field">
            <label for="dgSalary">Monthly Salary</label>
            <?= $salaryFieldHtml ?>
        </div>
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <?php
        $employeeId = $data['employee_id'] ?? '';
        $employeeEmail = urlencode($data['employee_email'] ?? '');
        $employeeName = urlencode($data['employee_full_name'] ?? '');
        $templateCode = urlencode($data['template_code'] ?? 'employment_contract');
        $hrSignatory = urlencode($_GET['hr_signatory'] ?? '');

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $absGenerateUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/generate-document.php'
            . '?employee_id=' . urlencode($employeeId)
            . '&document_type=employment_contract'
            . '&template=employment_contract.php'
            . '&template_code=employment_contract'
            . '&contract_type=' . urlencode($_GET['contract_type'] ?? 'Regular')
            . '&contract_start_date=' . urlencode($_GET['contract_start_date'] ?? '')
            . '&contract_end_date=' . urlencode($_GET['contract_end_date'] ?? '')
            . '&contract_salary_input=' . urlencode($_GET['contract_salary_input'] ?? '')
            . '&hr_signatory=' . $hrSignatory
            . '&generate=1';

        $attachmentName = 'Employment_Contract_' . preg_replace('/[^A-Za-z0-9]/', '', $data['employee_full_name'] ?? 'Employee') . '.pdf';

        $composeHref = '?page=notification-compose&mode=forward'
            . '&notification_key=employment_contract_confirmation'
            . '&to_recipient_email=' . $employeeEmail
            . '&to_recipient_name=' . $employeeName
            . '&template_code=' . $templateCode
            . '&scenario=general'
            . '&employee_id=' . urlencode($employeeId)
            . '&contract_type=' . urlencode($_GET['contract_type'] ?? 'Regular')
            . '&contract_start_date=' . urlencode($_GET['contract_start_date'] ?? '')
            . '&contract_end_date=' . urlencode($_GET['contract_end_date'] ?? '')
            . '&contract_salary_input=' . urlencode($_GET['contract_salary_input'] ?? '')
            . '&attachment_url=' . urlencode($absGenerateUrl)
            . '&attachment_name=' . urlencode($attachmentName)
            . '&document_name=Employment_Contract';
        ?>
        <a href="<?= htmlspecialchars($composeHref, ENT_QUOTES, 'UTF-8') ?>" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>

