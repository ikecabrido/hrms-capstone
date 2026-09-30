<?php
$viewData = get_defined_vars();
$records = is_array($viewData['records'] ?? null) ? $viewData['records'] : [];
?>

<div class="module-header">
    <h1 style="margin:0; color:#1e293b;">New Hire Onboarding</h1>
    <p class="date-text">Track onboarding progress for all new employees</p>

    <a href="index.php?page=onboarding-selection" class="btn-create">
        <i class="fas fa-user-plus"></i> Start Onboarding
    </a>
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
                                <a href="index.php?page=onboarding-manage&amp;id=<?= (int)($record['id'] ?? 0) ?>" class="btn-action">
                                    <i class="fas fa-tasks"></i> Manage
                                </a>
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
    </div>
</div>