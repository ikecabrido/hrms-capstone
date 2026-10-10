<?php

$defaultStart = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : (!empty($data['raw_date_hired']) ? date('Y-m-d', strtotime($data['raw_date_hired'])) : date('Y-m-d'));
$baseForEnd = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : $data['raw_date_hired'];
$defaultEnd = !empty($_GET['contract_end_date']) ? $_GET['contract_end_date'] : (!empty($baseForEnd) ? date('Y-m-d', strtotime('+1 year', strtotime($baseForEnd))) : date('Y-m-d', strtotime('+1 year')));

$baseSalary = 23000.00;
if (!empty($_GET['original_salary']) && is_numeric($_GET['original_salary'])) {
    $baseSalary = max(18000.00, (float) $_GET['original_salary']);
} elseif (!empty($_GET['contract_salary_input']) && is_numeric($_GET['contract_salary_input'])) {
    $baseSalary = max(18000.00, (float) $_GET['contract_salary_input']);
} elseif (!empty($data['employee_id']) && isset($db)) {
    try {
        $stmt = $db->prepare("SELECT negotiated_salary FROM em_employees WHERE employee_id = :id LIMIT 1");
        $stmt->execute([':id' => $data['employee_id']]);
        $empSalary = $stmt->fetchColumn();
        if ($empSalary && is_numeric($empSalary)) {
            $baseSalary = max(18000.00, (float) $empSalary);
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
?>

<form class="cd-date-form" method="post" action="" data-skip>
    <input type="hidden" name="action" value="apply_salary_rectification">
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgStartDate">Original Contract Date / Effectivity</label>
        <input type="date" id="dgStartDate" name="contract_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
    </div>

    <div class="cd-field">
        <label for="dgEndDate">Rectification / Effectivity Date</label>
        <input type="date" id="dgEndDate" name="contract_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
    </div>

    <div class="cd-field">
        <label for="dgSalary">Corrected Salary</label>
        <select id="dgSalary" name="contract_salary_input">
            <?php foreach ($salaryOptions as $value => $label): ?>
                <option value="<?= htmlspecialchars((string) $value) ?>" <?= abs($defaultSalary - $value) < 0.01 ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
