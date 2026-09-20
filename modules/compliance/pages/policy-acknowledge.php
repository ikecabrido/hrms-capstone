<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'My Policies';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
$policy = new Policy($db);

$employeeId = $policy->getCurrentUserEmployeeId();
if ($employeeId <= 0) {
    echo '<div class="module-content"><div class="alert alert-warning">You must be logged in to view your policies.</div></div>';
    exit;
}

$myPolicies = $policy->getMyPolicies($employeeId);

$acknowledged = array_filter($myPolicies, function ($p) {
    return $p['assignment_status'] === 'Acknowledged';
});
$pending = array_filter($myPolicies, function ($p) {
    return $p['assignment_status'] !== 'Acknowledged';
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge_policy_id'])) {
    $ackPolicyId = (int) $_POST['acknowledge_policy_id'];
    $ackPolicy = $policy->getPolicyById($ackPolicyId);
    if ($ackPolicy) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userId = $_SESSION['user']['id'] ?? null;
        $policy->acknowledgePolicy($ackPolicyId, $employeeId, $ackPolicy['version'], $ipAddress, $userId);
        echo '<script>window.location.href = "?page=policy-acknowledge";</script>';
        exit;
    }
}

?>

<div class="module-content">
    <div style="margin-bottom:20px;">
        <h2 style="margin:0;">My Policies</h2>
        <p style="margin:4px 0 0; color:var(--text-400,#8b93a1); font-size:.9rem;">Review and acknowledge your assigned policies</p>
    </div>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Assigned</h3>
            <p class="card-number"><?= count($myPolicies) ?></p>
        </div>
        <div class="card">
            <h3>Acknowledged</h3>
            <p class="card-number" style="color:#22c55e;"><?= count($acknowledged) ?></p>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <p class="card-number" style="color:#f59e0b;"><?= count($pending) ?></p>
        </div>
    </div>

    <?php if (!empty($pending)): ?>
        <div class="card" style="margin-top:20px;">
            <div class="card-header">
                <h3 class="card-title">Pending Acknowledgement</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Policy</th>
                            <th>Version</th>
                            <th>Effective Date</th>
                            <th>Deadline</th>
                            <th>Assigned</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                    <small style="color:var(--text-400,#8b93a1);"><?= htmlspecialchars($p['policy_code']) ?></small>
                                </td>
                                <td>v<?= htmlspecialchars($p['version']) ?></td>
                                <td><?= $p['effective_date'] ? date('M d, Y', strtotime($p['effective_date'])) : '—' ?></td>
                                <td><?= $p['acknowledgement_deadline'] ? date('M d, Y', strtotime($p['acknowledgement_deadline'])) : '—' ?></td>
                                <td><?= $p['assigned_at'] ? date('M d, Y', strtotime($p['assigned_at'])) : '—' ?></td>
                                <td>
                                    <a href="?page=policy-acknowledge&id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-primary">Read & Acknowledge</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <h3 class="card-title">Acknowledged Policies</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Policy</th>
                        <th>Version</th>
                        <th>Acknowledged At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($acknowledged)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">No acknowledged policies yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($acknowledged as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                    <small style="color:var(--text-400,#8b93a1);"><?= htmlspecialchars($p['policy_code']) ?></small>
                                </td>
                                <td>v<?= htmlspecialchars($p['version']) ?></td>
                                <td><?= $p['date_acknowledged'] ? date('M d, Y H:i', strtotime($p['date_acknowledged'])) : '—' ?></td>
                                <td>
                                    <a href="?page=policy-acknowledge&id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
