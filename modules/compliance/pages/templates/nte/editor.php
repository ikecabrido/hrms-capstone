<?php

$defaultIncidentDate       = !empty($_GET['incident_date']) ? $_GET['incident_date'] : '';
$defaultIncidentTime       = !empty($_GET['incident_time']) ? $_GET['incident_time'] : '';
$defaultIncidentLocation   = !empty($_GET['incident_location']) ? $_GET['incident_location'] : '';
$defaultIncidentDescription = !empty($_GET['incident_description']) ? $_GET['incident_description'] : '';
$defaultPolicyViolated     = !empty($_GET['policy_violated']) ? $_GET['policy_violated'] : '';

$policyViolationOptions = [
    'Labor Code',
    'DOLE Standard',
    'Working Hours',
    'Benefits',
    'Contract',
    'Safety',
    'Other',
];
$policyViolationOptionsList = '<option value="">Select Policy / Rule Violated</option>';
foreach ($policyViolationOptions as $pv) {
    $selected = ($pv === $defaultPolicyViolated) ? ' selected' : '';
    $policyViolationOptionsList .= '<option value="' . htmlspecialchars($pv) . '"' . $selected . '>' . htmlspecialchars($pv) . '</option>';
}
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgIncidentDate">Date of Incident</label>
        <input type="date" id="dgIncidentDate" name="incident_date" value="<?= htmlspecialchars($defaultIncidentDate) ?>">
    </div>

    <div class="cd-field">
        <label for="dgIncidentTime">Time</label>
        <input type="text" id="dgIncidentTime" name="incident_time" value="<?= htmlspecialchars($defaultIncidentTime) ?>" placeholder="e.g. 2:30 PM">
    </div>

    <div class="cd-field">
        <label for="dgIncidentLocation">Location</label>
        <input type="text" id="dgIncidentLocation" name="incident_location" value="<?= htmlspecialchars($defaultIncidentLocation) ?>" placeholder="e.g. Building A, 3rd Floor">
    </div>

    <div class="cd-field">
        <label for="dgPolicyViolated">Policy / Rule Violated</label>
        <select id="dgPolicyViolated" name="policy_violated">
            <?= $policyViolationOptionsList ?>
        </select>
    </div>

    <div class="cd-field cd-field--full-width">
        <label for="dgIncidentDescription">Incident Description</label>
        <textarea id="dgIncidentDescription" name="incident_description" rows="3"><?= htmlspecialchars($defaultIncidentDescription) ?></textarea>
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <?php
        $employeeId = $data['employee_id'] ?? '';
        $employeeEmail = urlencode($data['employee_email'] ?? '');
        $employeeName = urlencode($data['employee_full_name'] ?? '');
        $templateCode = urlencode($data['template_code'] ?? 'nte');
        $hrSignatory = urlencode($_GET['hr_signatory'] ?? '');

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $absGenerateUrl = $protocol . $host . '/hrms-capstone/modules/compliance/pages/generate-document.php'
            . '?employee_id=' . urlencode($employeeId)
            . '&document_type=nte'
            . '&template=nte.php'
            . '&template_code=nte'
            . '&hr_signatory=' . $hrSignatory
            . '&incident_date=' . urlencode($_GET['incident_date'] ?? '')
            . '&incident_time=' . urlencode($_GET['incident_time'] ?? '')
            . '&incident_location=' . urlencode($_GET['incident_location'] ?? '')
            . '&policy_violated=' . urlencode($_GET['policy_violated'] ?? '')
            . '&incident_description=' . urlencode($_GET['incident_description'] ?? '')
            . '&generate=1';

        $attachmentName = 'NTE_' . preg_replace('/[^A-Za-z0-9]/', '', $data['employee_full_name'] ?? 'Employee') . '.pdf';

        $composeHref = '?page=notification-compose&mode=forward'
            . '&notification_key=warning'
            . '&to_recipient_email=' . $employeeEmail
            . '&to_recipient_name=' . $employeeName
            . '&template_code=' . $templateCode
            . '&scenario=general'
            . '&employee_id=' . urlencode($employeeId)
            . '&incident_date=' . urlencode($_GET['incident_date'] ?? '')
            . '&incident_time=' . urlencode($_GET['incident_time'] ?? '')
            . '&incident_location=' . urlencode($_GET['incident_location'] ?? '')
            . '&policy_violated=' . urlencode($_GET['policy_violated'] ?? '')
            . '&incident_description=' . urlencode($_GET['incident_description'] ?? '')
            . '&attachment_url=' . urlencode($absGenerateUrl)
            . '&attachment_name=' . urlencode($attachmentName)
            . '&document_name=Notice_to_Explain';
        ?>
        <a href="<?= htmlspecialchars($composeHref, ENT_QUOTES, 'UTF-8') ?>" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>

<script>
(function(){
    var params = new URLSearchParams(window.location.search);
    var policy = params.get('policy_violated');
    if (policy) {
        var select = document.getElementById('dgPolicyViolated');
        if (select) {
            select.value = policy;
        }
    }
})();
</script>
