<?php

$defaultStart = !empty($_GET['contract_start_date']) ? $_GET['contract_start_date'] : date('Y-m-d');
$defaultEnd = !empty($_GET['contract_end_date']) ? $_GET['contract_end_date'] : date('Y-m-d', strtotime('+3 days'));
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgStartDate">Suspension Start Date</label>
        <input type="date" id="dgStartDate" name="contract_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
    </div>

    <div class="cd-field">
        <label for="dgEndDate">Suspension End Date</label>
        <input type="date" id="dgEndDate" name="contract_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
