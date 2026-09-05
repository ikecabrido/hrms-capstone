<?php

$defaultProgram = !empty($_GET['training_program']) ? $_GET['training_program'] : '';
$defaultBondPeriod = !empty($_GET['bond_period']) ? $_GET['bond_period'] : '';
$defaultAgreementDate = !empty($_GET['agreement_date']) ? $_GET['agreement_date'] : date('Y-m-d');
?>

<form class="cd-date-form" method="get" action="" data-skip>
    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($data['employee_id']) ?>">
    <input type="hidden" name="document_type" value="<?= htmlspecialchars($data['document_type']) ?>">
    <input type="hidden" name="template_code" value="<?= htmlspecialchars($data['template_code']) ?>">
    <input type="hidden" name="hr_signatory" value="<?= htmlspecialchars($_GET['hr_signatory'] ?? '') ?>">

    <div class="cd-field">
        <label for="dgTrainingProgram">Training Program</label>
        <input type="text" id="dgTrainingProgram" name="training_program" value="<?= htmlspecialchars($defaultProgram) ?>" placeholder="e.g. Leadership Development Program">
    </div>

    <div class="cd-field">
        <label for="dgBondPeriod">Bond Period</label>
        <input type="text" id="dgBondPeriod" name="bond_period" value="<?= htmlspecialchars($defaultBondPeriod) ?>" placeholder="e.g. 1 year">
    </div>

    <div class="cd-field">
        <label for="dgAgreementDate">Agreement Date</label>
        <input type="date" id="dgAgreementDate" name="agreement_date" value="<?= htmlspecialchars($defaultAgreementDate) ?>">
    </div>

    <div class="cd-field cd-field--split-actions-2">
        <button type="submit" class="cd-submit">Apply Changes</button>
        <a href="?page=notification-compose&mode=reply&notification_key=warning&to_recipient_email=<?= urlencode($data['employee_email']) ?>&to_recipient_name=<?= urlencode($data['employee_full_name']) ?>&template_code=<?= urlencode($data['template_code']) ?>&scenario=general" class="dg-btn-generate"><i class="bi bi-envelope"></i> Send to Email</a>
    </div>
</form>
