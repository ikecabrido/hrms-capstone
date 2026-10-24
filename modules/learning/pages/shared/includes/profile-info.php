<?php
/**
 * Read-only personal information card. These values come from HR and are not
 * editable from the Learning module. Expects $profileData.
 */
$employee = $profileData['employee'];
$employeeRef = trim((string) ($employee['employee_code'] ?? ''));
if ($employeeRef === '') {
    $employeeRef = (string) $profileData['id'];
}
?>
<div class="mode-card">
    <h3 class="pfl-section-title"><i class="fas fa-user" aria-hidden="true"></i> Personal Information</h3>
    <div class="pfl-rows">
        <div class="pfl-row">
            <span class="pfl-row-label">Full Name</span>
            <span class="pfl-row-value"><?= htmlspecialchars($profileData['full_name']) ?></span>
        </div>
        <div class="pfl-row">
            <span class="pfl-row-label">Employee ID</span>
            <span class="pfl-row-value"><?= htmlspecialchars($employeeRef) ?></span>
        </div>
        <div class="pfl-row">
            <span class="pfl-row-label">Email</span>
            <span class="pfl-row-value"><?= htmlspecialchars($employee['email'] !== '' ? $employee['email'] : '—') ?></span>
        </div>
        <div class="pfl-row">
            <span class="pfl-row-label">Position</span>
            <span class="pfl-row-value"><?= htmlspecialchars($employee['position_name'] !== '' ? $employee['position_name'] : '—') ?></span>
        </div>
        <div class="pfl-row">
            <span class="pfl-row-label">Department</span>
            <span class="pfl-row-value"><?= htmlspecialchars($employee['department_name'] !== '' ? $employee['department_name'] : '—') ?></span>
        </div>
        <?php if (trim((string) ($employee['employment_status'] ?? '')) !== ''): ?>
            <div class="pfl-row">
                <span class="pfl-row-label">Employment status</span>
                <span class="pfl-row-value"><?= htmlspecialchars((string) $employee['employment_status']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (trim((string) ($employee['ranking'] ?? '')) !== ''): ?>
            <div class="pfl-row">
                <span class="pfl-row-label">Ranking</span>
                <span class="pfl-row-value"><?= htmlspecialchars((string) $employee['ranking']) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($employee['hire_date'])): ?>
            <div class="pfl-row">
                <span class="pfl-row-label">Hire date</span>
                <span class="pfl-row-value"><?= htmlspecialchars(date('M j, Y', strtotime((string) $employee['hire_date']))) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
