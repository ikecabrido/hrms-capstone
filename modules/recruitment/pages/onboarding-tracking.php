<?php
$viewData = get_defined_vars();
$records = is_array($viewData['records'] ?? null) ? $viewData['records'] : [];
?>

<div class="module-header">
    <h1 style="margin:0; color:#1e293b;">New Hire Onboarding</h1>
    <p class="date-text">Track onboarding progress for all new employees</p>

    <div style="display: inline-block;">
        <a href="index.php?page=onboarding-selection" class="btn-create">
            <i class="fas fa-user-plus"></i> Start Onboarding
        </a>
    </div>
</div>

<div class="module-content">
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="80">ID</th>
                    <th>Employee Name</th>
                    <th>Position &amp; Dept.</th>
                    <th width="250">Onboarding Progress</th>
                    <th width="120" style="text-align:center;">Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $record): ?>
                        <?php $progress = (float)($record['progress'] ?? 0); ?>
                        <tr>
                            <td><span class="badge">#<?= (int)($record['id'] ?? 0) ?></span></td>
                            <td><strong><?= htmlspecialchars($record['full_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($record['position'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <small><?= htmlspecialchars($record['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width:<?= min(100, max(0, $progress)) ?>%;"></div>
                                </div>
                                <small><?= htmlspecialchars((string)$progress, ENT_QUOTES, 'UTF-8') ?>%</small>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($progress >= 100): ?>
                                    <span class="badge" style="background:#dcfce7;color:#166534;">Completed</span>
                                <?php elseif ($progress > 0): ?>
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;">In Progress</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#fef9c3;color:#854d0e;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <button
                                    type="button"
                                    class="btn-action manage-onboarding"
                                    data-id="<?= (int)($record['id'] ?? 0) ?>"
                                    data-name="<?= htmlspecialchars($record['full_name'] ?? 'Employee', ENT_QUOTES, 'UTF-8') ?>"
                                    data-position="<?= htmlspecialchars($record['position'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-tasks"></i> Manage
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#64748b;">No onboarding records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="onboardingModal" class="onboarding-modal" aria-hidden="true">
            <div class="onboarding-modal-content">
                <div class="onboarding-modal-header">
                    <div>
                        <h2 id="onboardingModalTitle">Onboarding Checklist</h2>
                        <p id="onboardingEmployeePosition"></p>
                    </div>
                    <button type="button" id="closeOnboardingModal"
                        class="onboarding-close" aria-label="Close">&times;</button>
                </div>

                <div id="onboardingMessage" class="onboarding-message"></div>

                <div class="onboarding-checklist">
                    <label class="checklist-row">
                        <span>
                            <input type="checkbox" data-item="requirements">
                            Submission of requirements
                        </span>
                    </label>

                    <div class="checklist-row">
                        <label>
                            <input type="checkbox" data-item="medical">
                            Medical (Health Card / Certificate)
                        </label>
                        <div class="checklist-actions">
                            <span class="uploaded-file" data-file="medical"></span>
                            <input type="file" class="checklist-file"
                                data-upload="medical" accept=".pdf,.jpg,.jpeg,.png"
                                hidden>
                            <button type="button" class="upload-btn"
                                data-pick="medical">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>

                    <div class="checklist-row">
                        <label>
                            <input type="checkbox" data-item="contract">
                            Contract signing
                        </label>
                        <div class="checklist-actions">
                            <span class="uploaded-file" data-file="contract"></span>
                            <input type="file" class="checklist-file"
                                data-upload="contract" accept=".pdf,.jpg,.jpeg,.png"
                                hidden>
                            <button type="button" class="upload-btn"
                                data-pick="contract">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                    </div>

                    <label class="checklist-row">
                        <span>
                            <input type="checkbox" data-item="biometric">
                            Enrollment in biometric
                        </span>
                    </label>

                    <label class="checklist-row">
                        <span>
                            <input type="checkbox" data-item="company_id">
                            Secure company ID
                        </span>
                    </label>

                    <div class="checklist-row">
                        <label>
                            <input type="checkbox" data-item="bdo_cash_card">
                            Enrollment to BDO cash card
                        </label>
                        <input type="text" id="bdoAccountNumber"
                            placeholder="BDO account number"
                            maxlength="100">
                    </div>
                </div>

                <div class="onboarding-modal-footer">
                    <span id="onboardingProgress">0 of 6 completed</span>
                    <button type="button" id="closeOnboardingFooter"
                        class="onboarding-save-btn">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>