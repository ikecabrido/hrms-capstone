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
?>
<div class="module-header">
    <h1 class="module-header-title"><i class="fas fa-exchange-alt" style="margin-right:0.5rem;"></i> Knowledge Transfer</h1>
    <p class="module-header-subtitle">View and manage your assigned knowledge transfer plans.</p>
</div>

<div class="module-content">
    <?php if (empty($ktPlans)): ?>
        <div class="mode-card" style="text-align:center; padding:3rem;">
            <i class="fas fa-exchange-alt" style="font-size:2.5rem; color:var(--primary); opacity:0.3; display:block; margin-bottom:1rem;"></i>
            <h3 style="margin:0 0 0.5rem; color:var(--text);">No Knowledge Transfer Plans</h3>
            <p style="margin:0; color:var(--muted); font-size:0.9rem;">You don't have any knowledge transfer plans assigned to you yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($ktPlans as $plan):
            $statusColors = ['active' => '#059669', 'completed' => '#2563eb', 'cancelled' => '#dc3545'];
            $statusColor = $statusColors[$plan['status']] ?? '#666';
            $itemsDone = count(array_filter($plan['items'] ?? [], fn($it) => $it['status'] === 'completed'));
            $itemsTotal = count($plan['items'] ?? []);
            $progress = $itemsTotal > 0 ? round(($itemsDone / $itemsTotal) * 100) : 0;
        ?>
            <div class="mode-card" style="margin-bottom:1.5rem;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:0.75rem;">
                    <div>
                        <h3 style="margin:0 0 0.25rem; color:var(--text);">
                            <i class="fas fa-exchange-alt" style="color:var(--primary); margin-right:0.3rem;"></i>
                            Plan #<?= $plan['id'] ?> — Knowledge Transfer from <?= htmlspecialchars($plan['employee_name'] ?? 'N/A') ?>
                        </h3>
                        <p style="margin:0; font-size:0.85rem; color:var(--muted);">
                            Created <?= ktTimeAgo($plan['created_at']) ?>
                        </p>
                    </div>
                    <span style="padding:0.3rem 0.7rem; border-radius:999px; font-size:0.72rem; font-weight:700; background:<?= $statusColor ?>15; color:<?= $statusColor ?>;">
                        <?= ucfirst($plan['status']) ?>
                    </span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:1rem; margin-bottom:1.25rem;">
                    <div style="padding:0.75rem; background:rgba(32,0,130,0.04); border-radius:8px;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">From</div>
                        <div style="margin-top:0.2rem; font-size:0.9rem; font-weight:600; color:var(--text);"><?= htmlspecialchars($plan['employee_name'] ?? 'N/A') ?></div>
                    </div>
                    <div style="padding:0.75rem; background:rgba(32,0,130,0.04); border-radius:8px;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Duration</div>
                        <div style="margin-top:0.2rem; font-size:0.9rem; color:var(--text);"><?= date('M d', strtotime($plan['start_date'])) ?> — <?= date('M d, Y', strtotime($plan['end_date'])) ?></div>
                    </div>
                    <div style="padding:0.75rem; background:rgba(32,0,130,0.04); border-radius:8px;">
                        <div style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--primary); font-weight:700;">Progress</div>
                        <div style="margin-top:0.2rem; font-size:0.9rem; font-weight:600; color:var(--text);"><?= $itemsDone ?>/<?= $itemsTotal ?> items (<?= $progress ?>%)</div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div style="height:6px; border-radius:999px; background:rgba(32,0,130,0.08); overflow:hidden; margin-bottom:1.25rem;">
                    <div style="height:100%; width:<?= $progress ?>%; border-radius:999px; background:linear-gradient(90deg, #059669, #10b981); transition:width 0.3s;"></div>
                </div>

                <!-- Transfer items -->
                <h4 style="margin:0 0 0.75rem; font-size:0.78rem; font-weight:700; color:var(--primary); text-transform:uppercase; letter-spacing:0.06em;">
                    <i class="fas fa-list-check" style="margin-right:0.3rem;"></i>Transfer Items (<?= $itemsTotal ?>)
                </h4>

                <?php if (empty($plan['items'])): ?>
                    <p style="color:var(--muted); font-size:0.88rem;">No transfer items have been added to this plan yet.</p>
                <?php else: ?>
                    <div style="display:grid; gap:0.5rem;">
                        <?php foreach ($plan['items'] as $item):
                            $itemColors = ['pending' => '#666', 'in_progress' => '#d97706', 'completed' => '#059669'];
                            $itemColor = $itemColors[$item['status']] ?? '#666';
                            $itemIcons = ['pending' => 'fa-circle', 'in_progress' => 'fa-spinner', 'completed' => 'fa-check-circle'];
                            $itemIcon = $itemIcons[$item['status']] ?? 'fa-circle';
                        ?>
                            <div class="kt-item-row" data-item-id="<?= $item['id'] ?>" data-plan-id="<?= $plan['id'] ?>"
                                 style="display:flex; align-items:center; gap:0.75rem; padding:0.85rem 1rem; background:rgba(32,0,130,0.03); border:1px solid rgba(32,0,130,0.08); border-radius:10px; transition:border-color 0.2s;">
                                <i class="fas <?= $itemIcon ?>" style="color:<?= $itemColor ?>; font-size:0.9rem; flex-shrink:0;"></i>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:0.9rem; color:var(--text);"><?= htmlspecialchars($item['title']) ?></div>
                                    <div style="font-size:0.75rem; color:var(--muted); margin-top:0.15rem; text-transform:capitalize;">
                                        <?= $item['item_type'] ?> &bull; Priority: <?= $item['priority'] ?>
                                        <?php if (!empty($item['description'])): ?>
                                            &bull; <?= htmlspecialchars(mb_substr($item['description'], 0, 80)) ?><?= strlen($item['description']) > 80 ? '...' : '' ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($plan['status'] === 'active'): ?>
                                    <div style="display:flex; gap:0.3rem; flex-shrink:0;">
                                        <?php if ($item['status'] === 'pending'): ?>
                                            <button class="kt-status-btn" data-item-id="<?= $item['id'] ?>" data-new-status="in_progress"
                                                    style="padding:0.3rem 0.6rem; font-size:0.7rem; font-weight:700; background:rgba(217,119,6,0.1); color:#d97706; border:1px solid rgba(217,119,6,0.2); border-radius:999px; cursor:pointer; white-space:nowrap;">
                                                <i class="fas fa-play" style="margin-right:0.15rem;"></i>Start
                                            </button>
                                        <?php elseif ($item['status'] === 'in_progress'): ?>
                                            <button class="kt-status-btn" data-item-id="<?= $item['id'] ?>" data-new-status="completed"
                                                    style="padding:0.3rem 0.6rem; font-size:0.7rem; font-weight:700; background:rgba(5,150,105,0.1); color:#059669; border:1px solid rgba(5,150,105,0.2); border-radius:999px; cursor:pointer; white-space:nowrap;">
                                                <i class="fas fa-check" style="margin-right:0.15rem;"></i>Complete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:0.68rem; padding:0.15rem 0.5rem; border-radius:999px; background:<?= $itemColor ?>15; color:<?= $itemColor ?>; font-weight:600; text-transform:capitalize; white-space:nowrap;">
                                        <?= str_replace('_', ' ', $item['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
