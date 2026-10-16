<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentEmployeeId = $_SESSION['employee_id'] ?? 0;

$pdo = null;
$ktPlans = [];
try {
    require_once dirname(__DIR__, 4) . '/database/db.php';
    $pdo = (new Database())->getConnection();

    // Fetch KT plans where this learner is the successor
    $stmt = $pdo->prepare("
        SELECT p.*,
               CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
               CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        WHERE p.successor_id = :eid
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':eid' => $currentEmployeeId]);
    $ktPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each plan
    foreach ($ktPlans as &$plan) {
        $itemStmt = $pdo->prepare("SELECT * FROM exit_knowledge_transfer_items WHERE plan_id = :pid ORDER BY FIELD(priority, 'high', 'medium', 'low'), created_at ASC");
        $itemStmt->execute([':pid' => $plan['id']]);
        $plan['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($plan);
} catch (Throwable $e) {
    error_log('learner/knowledge-transfer.php error: ' . $e->getMessage());
}

function ktTimeAgo($dt) {
    if (!$dt) return 'N/A';
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . 'm ago';
    if ($d < 86400) return floor($d / 3600) . 'h ago';
    if ($d < 604800) return floor($d / 86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}

$planStatusBadge = static function (string $status): array {
    switch ($status) {
        case 'active':    return ['is-success', 'Active'];
        case 'completed': return ['is-muted', 'Completed'];
        case 'cancelled': return ['is-danger', 'Cancelled'];
        default:          return ['is-muted', ucfirst($status)];
    }
};

$itemTone = static function (string $status): array {
    switch ($status) {
        case 'in_progress': return ['tone-amber', 'fa-spinner'];
        case 'completed':   return ['tone-emerald', 'fa-check-circle'];
        default:            return ['tone-slate', 'fa-circle'];
    }
};

$activePlans = count(array_filter($ktPlans, fn($p) => ($p['status'] ?? '') === 'active'));
?>
<div class="module-content lt-page">
    <div class="home-dashboard">

        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Knowledge Transfer</h1>
                <p>Knowledge transfer plans where you are the successor.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-exchange-alt"></i> <?= count($ktPlans) ?> plan<?= count($ktPlans) !== 1 ? 's' : '' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-bolt"></i> <?= $activePlans ?> active</span>
                </div>
            </div>
        </section>

        <?php if (empty($ktPlans)): ?>
            <div class="home-card">
                <div class="home-empty">
                    <i class="fas fa-exchange-alt"></i>
                    <p>You don't have any knowledge transfer plans assigned to you yet.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($ktPlans as $plan):
                [$statusBadgeClass, $statusBadgeLabel] = $planStatusBadge((string) ($plan['status'] ?? ''));
                $items = $plan['items'] ?? [];
                $itemsDone = count(array_filter($items, fn($it) => ($it['status'] ?? '') === 'completed'));
                $itemsTotal = count($items);
                $progress = $itemsTotal > 0 ? (int) round(($itemsDone / $itemsTotal) * 100) : 0;
                $startDisplay = !empty($plan['start_date']) ? date('M d, Y', strtotime($plan['start_date'])) : 'No start date';
                $endDisplay = !empty($plan['end_date']) ? date('M d, Y', strtotime($plan['end_date'])) : 'No end date';
            ?>
                <div class="home-card">
                    <div class="home-card-head">
                        <h2 class="home-card-title">
                            <span class="home-card-icon"><i class="fas fa-exchange-alt"></i></span>
                            Plan #<?= (int) $plan['id'] ?> — from <?= htmlspecialchars($plan['employee_name'] ?? 'N/A') ?>
                        </h2>
                        <span class="home-badge <?= $statusBadgeClass ?>"><?= htmlspecialchars($statusBadgeLabel) ?></span>
                    </div>

                    <div class="home-row-meta" style="margin-bottom:0.4rem;">Created <?= ktTimeAgo($plan['created_at']) ?></div>

                    <div class="home-kv">
                        <span class="home-kv-label"><i class="fas fa-user-tie"></i> From</span>
                        <span class="home-kv-value"><?= htmlspecialchars($plan['employee_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="home-kv">
                        <span class="home-kv-label"><i class="fas fa-calendar"></i> Duration</span>
                        <span class="home-kv-value"><?= htmlspecialchars($startDisplay . ' — ' . $endDisplay) ?></span>
                    </div>
                    <div class="home-kv">
                        <span class="home-kv-label"><i class="fas fa-list-check"></i> Progress</span>
                        <span class="home-kv-value"><?= $itemsDone ?>/<?= $itemsTotal ?> items</span>
                    </div>

                    <div class="home-progress" style="max-width:none;margin-top:0.75rem;">
                        <span class="home-progress-track">
                            <span class="home-progress-fill" style="width:<?= $progress ?>%"></span>
                        </span>
                        <span class="home-progress-val"><?= $progress ?>%</span>
                    </div>

                    <div class="lt-subhead"><i class="fas fa-list-check"></i> Transfer items (<?= $itemsTotal ?>)</div>

                    <?php if (empty($items)): ?>
                        <div class="home-empty">
                            <i class="fas fa-list-check"></i>
                            <p>No transfer items have been added to this plan yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="home-list">
                            <?php foreach ($items as $item):
                                [$tone, $icon] = $itemTone((string) ($item['status'] ?? 'pending'));
                            ?>
                                <div class="home-row kt-item-row" data-item-id="<?= (int) $item['id'] ?>" data-plan-id="<?= (int) $plan['id'] ?>">
                                    <span class="home-row-icon <?= $tone ?>"><i class="fas <?= $icon ?>"></i></span>
                                    <span class="home-row-body">
                                        <span class="home-row-title"><?= htmlspecialchars($item['title']) ?></span>
                                        <span class="home-row-meta">
                                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $item['item_type']))) ?>
                                            &bull; Priority: <?= htmlspecialchars((string) $item['priority']) ?>
                                            <?php if (!empty($item['description'])): ?>
                                                &bull; <?= htmlspecialchars(mb_substr($item['description'], 0, 80)) ?><?= mb_strlen($item['description']) > 80 ? '…' : '' ?>
                                            <?php endif; ?>
                                        </span>
                                    </span>
                                    <span class="home-row-trail">
                                        <?php if (($plan['status'] ?? '') === 'active' && ($item['status'] ?? '') === 'pending'): ?>
                                            <button type="button" class="kt-status-btn lt-item-btn start" data-item-id="<?= (int) $item['id'] ?>" data-new-status="in_progress">
                                                <i class="fas fa-play"></i> Start
                                            </button>
                                        <?php elseif (($plan['status'] ?? '') === 'active' && ($item['status'] ?? '') === 'in_progress'): ?>
                                            <button type="button" class="kt-status-btn lt-item-btn done" data-item-id="<?= (int) $item['id'] ?>" data-new-status="completed">
                                                <i class="fas fa-check"></i> Complete
                                            </button>
                                        <?php else: ?>
                                            <span class="home-badge <?= $tone === 'tone-emerald' ? 'is-success' : ($tone === 'tone-amber' ? 'is-warn' : 'is-muted') ?>">
                                                <?= htmlspecialchars(str_replace('_', ' ', (string) $item['status'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<script>
(function() {
    document.querySelectorAll('.kt-status-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var itemId = btn.dataset.itemId;
            var newStatus = btn.dataset.newStatus;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('pages/admin/ajax/update-kt-item-status.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ item_id: parseInt(itemId), status: newStatus })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to update item.');
                    btn.disabled = false;
                }
            })
            .catch(function() {
                alert('Network error.');
                btn.disabled = false;
            });
        });
    });
})();
</script>
