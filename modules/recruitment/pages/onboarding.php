<?php

require_once __DIR__ . '/../classes/Onboarding.php';

$onboardingModel = new Onboarding();

try {
    $records = $onboardingModel->getAll();
    $error = null;
} catch (Throwable $e) {
    $records = [];
    $error = $e->getMessage();
}

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
                    <th>Position & Dept.</th>
                    <th width="250">Onboarding Progress</th>
                    <th width="120" style="text-align:center;">Status</th>
                    <th style="text-align: center;">Actions</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><span class="badge">#<?= $r['id'] ?></span></td>
                        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                        <td>
                            <div style="font-size: 13px;">
                                <span style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($r['position']) ?></span><br>
                                <small style="color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars($r['department']) ?></small>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: <?= $r['progress'] ?>%;"></div>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #475569; min-width: 35px;">
                                    <?= $r['progress'] ?>%
                                </span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($r['progress'] == 100): ?>
                                <span style="background:#dcfce7; color:#166534; padding:5px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                                    Completed
                                </span>

                            <?php elseif ($r['progress'] > 0): ?>
                                <span style="background:#dbeafe; color:#1e40af; padding:5px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                                    In Progress
                                </span>

                            <?php else: ?>
                                <span style="background:#fef9c3; color:#854d0e; padding:5px 10px; border-radius:20px; font-size:12px; font-weight:600;">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="index.php?page=onboarding-manage&id=<?= $r['id'] ?>" class="btn-action" style="background:#f1f5f9; color:#475569; width:auto; padding: 0 15px; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-tasks"></i> Manage
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>