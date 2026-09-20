<?php

$defaultIncidentDate = !empty($_GET['incident_date']) ? $_GET['incident_date'] : '';
$defaultDescription = !empty($_GET['incident_description']) ? $_GET['incident_description'] : '';
$defaultPolicyViolated = !empty($_GET['policy_violated']) ? $_GET['policy_violated'] : '';
$policyOptions = ['Labor Code', 'DOLE Standard', 'Working Hours', 'Benefits', 'Contract', 'Safety', 'Other'];
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

    <div class="cd-field cd-field--full-width">
        <label for="dgIncidentDescription">Incident Description</label>
        <textarea id="dgIncidentDescription" name="incident_description" rows="3"><?= htmlspecialchars($defaultDescription) ?></textarea>
    </div>

    <div class="cd-field cd-field--full-width">
        <label for="dgPolicyViolated">Policy / Rule Violated</label>
        <select id="dgPolicyViolated" name="policy_violated_list">
            <option value="">Select a policy</option>
            <?php foreach ($policyOptions as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($defaultPolicyViolated === $opt ? 'selected' : '') ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" id="dgPolicyViolatedInput" name="policy_violated" value="<?= htmlspecialchars($defaultPolicyViolated) ?>">
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="../../../index.php?page=notification-compose&mode=forward&notification_key=employment_contract_confirmation&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>

<script>
(function(){
    var sel = document.getElementById('dgPolicyViolated');
    var hidden = document.getElementById('dgPolicyViolatedInput');
    if (!sel || !hidden) return;
    sel.addEventListener('change', function(){
        hidden.value = sel.value;
    });
})();
</script>
