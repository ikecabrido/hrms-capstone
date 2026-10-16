<?php
$viewData = get_defined_vars();
$applications = is_array($viewData['applications'] ?? null) ? $viewData['applications'] : [];
$message = $_GET['message'] ?? '';
?>

<div class="module-header">
    <h1>Hired Applicants</h1>
    <p>Applicants successfully moved to the hired list.</p>
</div>

<div class="module-content">
    <?php if ($message !== ''): ?>
        <div style="background:#dcfce7;color:#166534;padding:10px 14px;border-radius:6px;margin-bottom:16px;">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:6px;margin-bottom:16px;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Applicant Name</th>
                    <th>Email</th>
                    <th>Cellphone Number</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Salary</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($applications)): ?>
                    <?php foreach ($applications as $application): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''), ' ') ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($application['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($application['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($application['position'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($application['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(number_format((float)($application['salary'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#64748b;">No hired applicants found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>