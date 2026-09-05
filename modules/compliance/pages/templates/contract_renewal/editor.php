<?php

$defaultContractType = !empty($_GET['contract_type']) ? $_GET['contract_type'] : (!empty($data['raw_employment_status']) ? $data['raw_employment_status'] : 'Regular');
$defaultStart = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : (!empty($data['raw_date_hired']) ? date('Y-m-d', strtotime($data['raw_date_hired'])) : date('Y-m-d'));
$baseForEnd = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : $data['raw_date_hired'];
$defaultEnd = !empty($_GET['contract_end_date']) ? $_GET['contract_end_date'] : (!empty($baseForEnd) ? date('Y-m-d', strtotime('+1 year', strtotime($baseForEnd))) : date('Y-m-d', strtotime('+1 year')));

$baseSalary = 23000.00;
if (!empty($_GET['contract_salary_input']) && is_numeric($_GET['contract_salary_input'])) {
    $baseSalary = (float) $_GET['contract_salary_input'];
} elseif (!empty($data['employee_id']) && isset($db)) {
    try {
        $sourceTable = $data['source_table'] ?? 'em_employees';
        $idColumn = $data['id_column'] ?? 'employee_id';
        $stmt = $db->prepare("SELECT negotiated_salary FROM {$sourceTable} WHERE {$idColumn} = :id LIMIT 1");
        $stmt->execute([':id' => $data['employee_id']]);
        $empSalary = $stmt->fetchColumn();
        if ($empSalary && is_numeric($empSalary)) {
            $baseSalary = (float) $empSalary;
        }
    } catch (Throwable $e) {
        $baseSalary = 23000.00;
    }
}

$option1 = $baseSalary;
$option2 = round($baseSalary * 1.05, 2);
$option3 = round($baseSalary * 1.10, 2);

$salaryOptions = [
    $option1 => '₱' . number_format($option1, 2),
    $option2 => '₱' . number_format($option2, 2),
    $option3 => '₱' . number_format($option3, 2),
];

$currentSalary = !empty($_GET['contract_salary_input']) ? (float) $_GET['contract_salary_input'] : $baseSalary;
$hasExactMatch = false;
foreach ($salaryOptions as $val => $label) {
    if (abs($currentSalary - $val) < 0.01) {
        $hasExactMatch = true;
        break;
    }
}

if (!$hasExactMatch && $currentSalary > 0) {
    $salaryOptions = [$currentSalary => '₱' . number_format($currentSalary, 2)] + $salaryOptions;
    $defaultSalary = $currentSalary;
} else {
    $defaultSalary = $currentSalary;
}

$contractTypeOptions = '';
$validContractTypes = ['Regular', 'Probationary', 'Fixed-Term', 'Project', 'Seasonal', 'Casual', 'Part-Time'];
foreach ($validContractTypes as $ct) {
    $selected = ($ct === $defaultContractType) ? ' selected' : '';
    $contractTypeOptions .= '<option value="' . htmlspecialchars($ct) . '"' . $selected . '>' . htmlspecialchars($ct) . '</option>';
}
?>

<form class="cd-date-form" method="post" action="" data-skip>
    <input type="hidden" name="action" value="apply_contract_renewal">
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgContractType">Employment Status</label>
        <select id="dgContractType" name="contract_type">
            <?= $contractTypeOptions ?>
        </select>
    </div>

    <div class="cd-field">
        <label for="dgStartDate">Start Date</label>
        <input type="date" id="dgStartDate" name="contract_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
    </div>

    <div class="cd-field">
        <label for="dgEndDate">End Date</label>
        <input type="date" id="dgEndDate" name="contract_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
    </div>

    <div class="cd-field">
        <label for="dgSalary">Monthly Salary</label>
        <select id="dgSalary" name="contract_salary_input" onchange="toggleSalaryInput(this)">
            <?php foreach ($salaryOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars((string) $value) ?>" <?= abs($defaultSalary - $value) < 0.01 ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
            <option value="__other__">Other</option>
        </select>
        <input type="text" id="dgSalaryOther" name="contract_salary_input" placeholder="Enter custom amount" style="display:none;" inputmode="decimal" disabled>
    </div>

    <script>
        function toggleSalaryInput(select) {
            var otherInput = document.getElementById("dgSalaryOther");
            if (select.value === "__other__") {
                select.style.display = "none";
                select.disabled = true;
                otherInput.style.display = "block";
                otherInput.disabled = false;
                otherInput.focus();
            } else {
                otherInput.style.display = "none";
                otherInput.disabled = true;
                otherInput.value = "";
                select.style.display = "block";
                select.disabled = false;
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            var select = document.getElementById("dgSalary");
            var otherInput = document.getElementById("dgSalaryOther");
            if (select && otherInput) {
                if (select.value === "__other__") {
                    select.disabled = true;
                    otherInput.disabled = false;
                } else {
                    otherInput.disabled = true;
                    select.disabled = false;
                }
            }
        });
    </script>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
