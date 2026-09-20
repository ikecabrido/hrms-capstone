<?php

$defaultProgram = !empty($_GET['study_program']) ? $_GET['study_program'] : '';
$defaultStart = !empty($_GET['leave_start_date']) ? $_GET['leave_start_date'] : date('Y-m-d');
$defaultEnd = !empty($_GET['leave_end_date']) ? $_GET['leave_end_date'] : date('Y-m-d', strtotime('+1 month'));
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgStudyProgram">Course / Program</label>
        <input type="text" id="dgStudyProgram" name="study_program" value="<?= htmlspecialchars($defaultProgram) ?>" placeholder="e.g. Master of Business Administration">
    </div>

    <div class="cd-field">
        <label for="dgStartDate">Study Leave Period From</label>
        <input type="date" id="dgStartDate" name="leave_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
    </div>

    <div class="cd-field">
        <label for="dgEndDate">Study Leave Period To</label>
        <input type="date" id="dgEndDate" name="leave_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
