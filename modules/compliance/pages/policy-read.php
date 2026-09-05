<?php

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../classes/Policy.php';

$pageTitle = 'Read Policy';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db = (new Database())->getConnection();
if (!$db) {
    echo '<div class="module-content"><div class="alert alert-danger">Database connection failed.</div></div>';
    exit;
}

$policy = new Policy($db);

$employeeId = $policy->getCurrentUserEmployeeId();
if ($employeeId <= 0) {
    echo '<div class="module-content"><div class="alert alert-warning">You must be logged in to acknowledge policies.</div></div>';
    exit;
}

$policyId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($policyId <= 0) {
    header('Location: ?page=policy-acknowledge');
    exit;
}

$policyData = $policy->getPolicyById($policyId);
if (!$policyData || $policyData['status'] !== 'Published') {
    echo '<div class="module-content"><div class="alert alert-warning">This policy is not available for acknowledgement.</div></div>';
    exit;
}

$assignment = $db->prepare("
    SELECT a.* FROM lc_policy_assignments a
    WHERE a.policy_id = :policy_id AND a.employee_id = :employee_id
    LIMIT 1
");
$assignment->execute([':policy_id' => $policyId, ':employee_id' => $employeeId]);
$assignment = $assignment->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    echo '<div class="module-content"><div class="alert alert-warning">This policy is not assigned to you.</div></div>';
    exit;
}

$ack = $db->prepare("
    SELECT * FROM lc_policy_acknowledgments
    WHERE policy_id = :policy_id AND employee_id = :employee_id
    LIMIT 1
");
$ack->execute([':policy_id' => $policyId, ':employee_id' => $employeeId]);
$ack = $ack->fetch(PDO::FETCH_ASSOC);

$isAcknowledged = $assignment && $assignment['status'] === 'Acknowledged';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAcknowledged && isset($_POST['acknowledge'])) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userId = $_SESSION['user']['id'] ?? null;
    $policy->acknowledgePolicy($policyId, $employeeId, $policyData['version'], $ipAddress, $userId);
    echo '<script>window.location.href = "?page=policy-acknowledge";</script>';
    exit;
}

?>

<div class="module-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2 style="margin:0;"><?= htmlspecialchars($policyData['title']) ?></h2>
            <p style="margin:4px 0 0; color:var(--text-400,#8b93a1); font-size:.9rem;">
                <?= htmlspecialchars($policyData['policy_code']) ?> · Version <?= htmlspecialchars($policyData['version']) ?>
            </p>
        </div>
        <a href="?page=policy-acknowledge" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to My Policies</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #e5e7eb;">
                <div>
                    <small style="color:var(--text-400,#8b93a1);">Effective Date</small>
                    <div><strong><?= $policyData['effective_date'] ? date('F d, Y', strtotime($policyData['effective_date'])) : '—' ?></strong></div>
                </div>
                <div>
                    <small style="color:var(--text-400,#8b93a1);">Acknowledgement Deadline</small>
                    <div><strong><?= $policyData['acknowledgement_deadline'] ? date('F d, Y', strtotime($policyData['acknowledgement_deadline'])) : '—' ?></strong></div>
                </div>
                <div>
                    <small style="color:var(--text-400,#8b93a1);">Status</small>
                    <div><strong><?= htmlspecialchars($policyData['status']) ?></strong></div>
                </div>
            </div>

            <?php if (!empty($policyData['description'])): ?>
                <div style="margin-bottom:16px; padding:12px; background:#f8fafc; border-radius:8px; border-left:4px solid #3b82c4;">
                    <?= nl2br(htmlspecialchars($policyData['description'])) ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:20px; line-height:1.8; white-space:pre-wrap; font-family:inherit;"><?= htmlspecialchars($policyData['content']) ?></div>

            <?php if (!empty($policyData['attachment_path'])): ?>
                <div style="margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb;">
                    <a href="<?= htmlspecialchars($policyData['attachment_path']) ?>" target="_blank" class="btn btn-secondary">
                        <i class="bi bi-paperclip"></i> Download Attachment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <div class="card-body" style="text-align:center; padding:32px;">
            <?php if ($isAcknowledged): ?>
                <div style="color:#22c55e; font-size:3rem; margin-bottom:8px;"><i class="bi bi-check-circle-fill"></i></div>
                <h3 style="margin:0 0 8px; color:#22c55e;">Acknowledged</h3>
                <p style="margin:0; color:var(--text-400,#8b93a1);">
                    You acknowledged this policy on <?= date('F d, Y H:i', strtotime($ack['date_acknowledged'])) ?>
                </p>
            <?php else: ?>
                <div style="margin-bottom:16px;">
                    <label style="display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-size:1rem;">
                        <input type="checkbox" id="ackCheck" onchange="document.getElementById('ackBtn').disabled = !this.checked">
                        <span>I have read and understood this policy.</span>
                    </label>
                </div>
                <form method="post" action="" id="ackForm">
                    <button type="submit" name="acknowledge" id="ackBtn" class="btn btn-primary" disabled style="min-width:200px;">
                        Acknowledge Policy
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
