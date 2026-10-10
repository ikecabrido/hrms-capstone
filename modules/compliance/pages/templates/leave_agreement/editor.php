<?php

$leaveOptions = [
    'Vacation Leave',
    'Sick Leave',
    'Emergency Leave',
    'Maternity Leave',
    'Paternity Leave',
    'Bereavement Leave',
    'Study Leave',
    'Solo Parent Leave',
    'Special Leave for Women',
    'Personal Leave',
];
$defaultLeaveType = !empty($_GET['leave_type']) ? $_GET['leave_type'] : $leaveOptions[0];
$defaultStart = !empty($_GET['leave_start_date']) ? $_GET['leave_start_date'] : date('Y-m-d');
$defaultEnd = !empty($_GET['leave_end_date']) ? $_GET['leave_end_date'] : date('Y-m-d', strtotime('+1 week'));
$defaultDuration = !empty($_GET['leave_duration']) ? $_GET['leave_duration'] : '';

$leaveOptionsList = '';
foreach ($leaveOptions as $lo) {
    $selected = ($lo === $defaultLeaveType) ? ' selected' : '';
    $leaveOptionsList .= '<option value="' . htmlspecialchars($lo) . '"' . $selected . '>' . htmlspecialchars($lo) . '</option>';
}
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgLeaveType">Leave Type</label>
        <select id="dgLeaveType" name="leave_type">
            <?= $leaveOptionsList ?>
        </select>
    </div>

    <div class="cd-field">
        <label for="dgStartDate">Leave Start Date</label>
        <input type="date" id="dgStartDate" name="leave_start_date" value="<?= htmlspecialchars($defaultStart) ?>">
    </div>

    <div class="cd-field">
        <label for="dgEndDate">Leave End Date</label>
        <input type="date" id="dgEndDate" name="leave_end_date" value="<?= htmlspecialchars($defaultEnd) ?>">
    </div>

    <div class="cd-field">
        <label for="dgDuration">Duration</label>
        <input type="text" id="dgDuration" name="leave_duration" value="<?= htmlspecialchars($defaultDuration) ?>" placeholder="e.g. 5 days">
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
