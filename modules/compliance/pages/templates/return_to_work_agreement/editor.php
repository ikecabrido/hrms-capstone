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
$defaultLeaveReason = !empty($_GET['leave_reason']) ? $_GET['leave_reason'] : ($leaveOptions[0] ?? '');
$leaveOptionsList = '';
foreach ($leaveOptions as $lo) {
    $selected = ($lo === $defaultLeaveReason) ? ' selected' : '';
    $leaveOptionsList .= '<option value="' . htmlspecialchars($lo) . '"' . $selected . '>' . htmlspecialchars($lo) . '</option>';
}
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgLeaveReason">Reason for Leave</label>
        <select id="dgLeaveReason" name="leave_reason">
            <?= $leaveOptionsList ?>
        </select>
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
