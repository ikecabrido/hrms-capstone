<?php
include_once __DIR__ . '/../../classes/Employee.php';
require_once dirname(__DIR__, 4) . '/database/db.php';

$employeeClass = new Employee();
$pdo = (new Database())->getConnection();

$plans = [];
$employees = [];
$stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0, 'items_total' => 0, 'items_completed' => 0];

try {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.employee_id,
            p.successor_id,
            p.start_date,
            p.end_date,
            p.status,
            p.created_at,
            CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
            CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name,
            (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id) AS item_count,
            (SELECT COUNT(*) FROM exit_knowledge_transfer_items WHERE plan_id = p.id AND status = 'completed') AS items_done
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        ORDER BY p.created_at DESC
    ");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats['total'] = count($plans);
    foreach ($plans as $p) {
        if ($p['status'] === 'active') $stats['active']++;
        elseif ($p['status'] === 'completed') $stats['completed']++;
        elseif ($p['status'] === 'cancelled') $stats['cancelled']++;
        $stats['items_total'] += (int) $p['item_count'];
        $stats['items_completed'] += (int) $p['items_done'];
    }

    $stmt = $pdo->query("SELECT employee_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) AS full_name FROM em_employees ORDER BY first_name ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('admin/knowledge-transfer.php load failed: ' . $e->getMessage());
}

function kt_status_badge($status) {
    $map = [
        'active'    => ['bg' => 'rgba(5,150,105,0.1)', 'color' => '#059669', 'label' => 'Active'],
        'completed' => ['bg' => 'rgba(37,99,235,0.1)', 'color' => '#2563eb', 'label' => 'Completed'],
        'cancelled' => ['bg' => 'rgba(220,53,69,0.1)', 'color' => '#dc3545', 'label' => 'Cancelled'],
    ];
    $s = $map[$status] ?? ['bg' => 'rgba(100,100,100,0.1)', 'color' => '#666', 'label' => ucfirst($status)];
    return '<span style="display:inline-block;padding:0.2rem 0.7rem;border-radius:999px;font-size:0.72rem;font-weight:700;background:' . $s['bg'] . ';color:' . $s['color'] . ';">' . $s['label'] . '</span>';
}
?>
<div class="module-header">
    <h1 class="module-header-title">Knowledge Transfer</h1>
</div>

<div class="module-content">
    <!-- Stats Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="analytics-card">
            <h2><i class="fas fa-exchange-alt" style="margin-right:0.4rem;opacity:0.6;"></i> Total Plans</h2>
            <p class="analytics-value"><?= $stats['total'] ?></p>
        </div>
        <div class="analytics-card">
            <h2><i class="fas fa-play-circle" style="margin-right:0.4rem;opacity:0.6;color:#059669;"></i> Active</h2>
            <p class="analytics-value" style="color:#059669;"><?= $stats['active'] ?></p>
        </div>
        <div class="analytics-card">
            <h2><i class="fas fa-check-circle" style="margin-right:0.4rem;opacity:0.6;color:#2563eb;"></i> Completed</h2>
            <p class="analytics-value" style="color:#2563eb;"><?= $stats['completed'] ?></p>
        </div>
        <div class="analytics-card">
            <h2><i class="fas fa-tasks" style="margin-right:0.4rem;opacity:0.6;"></i> Transfer Items</h2>
            <p class="analytics-value"><?= $stats['items_total'] ?></p>
            <div style="font-size:0.8rem;color:#999;"><?= $stats['items_completed'] ?> completed</div>
        </div>
    </div>

    <!-- Toolbar -->
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;position:relative;">
            <i class="fas fa-search" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:0.85rem;"></i>
            <input type="search" id="kt-search" placeholder="Search by employee, successor, or status..."
                   style="width:100%;padding:0.6rem 1rem 0.6rem 2.5rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:10px;background:rgba(32,0,130,0.03);font-size:0.88rem;outline:none;box-sizing:border-box;"
                   aria-label="Search knowledge transfer plans" />
        </div>
        <select id="kt-status-filter"
                style="padding:0.55rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;background:#fff;font-size:0.82rem;font-weight:600;cursor:pointer;outline:none;">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <!-- Plans Grid -->
    <div id="kt-plans-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
        <?php if (empty($plans)): ?>
            <div id="kt-empty" style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);">
                <i class="fas fa-exchange-alt" style="font-size:2rem;display:block;margin-bottom:0.75rem;opacity:0.4;"></i>
                <p style="margin:0;font-size:0.95rem;font-weight:600;">No knowledge transfer plans found</p>
                <p style="margin:0.4rem 0 0;font-size:0.82rem;opacity:0.7;">Plans are created from the Exit Management module.</p>
            </div>
        <?php else: ?>
            <?php foreach ($plans as $plan): ?>
                <div class="mode-card kt-plan-card" data-status="<?= htmlspecialchars($plan['status']) ?>"
                     data-search="<?= strtolower(htmlspecialchars($plan['employee_name'] . ' ' . $plan['successor_name'] . ' ' . $plan['status'])) ?>"
                     style="cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;"
                     onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 35px rgba(32,0,130,0.12)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0.75rem;">
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div>
                                <h3 style="margin:0;font-size:0.95rem;font-weight:700;color:var(--text);"><?= htmlspecialchars($plan['employee_name'] ?: 'N/A') ?></h3>
                                <span style="font-size:0.72rem;color:var(--muted);">Plan #<?= $plan['id'] ?></span>
                            </div>
                        </div>
                        <?= kt_status_badge($plan['status']) ?>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:0.75rem;">
                        <div>
                            <span style="display:block;font-size:0.65rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Successor</span>
                            <span style="font-size:0.82rem;color:var(--text);"><?= htmlspecialchars($plan['successor_name'] ?: '—') ?></span>
                        </div>
                        <div>
                            <span style="display:block;font-size:0.65rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Duration</span>
                            <span style="font-size:0.82rem;color:var(--text);"><?= date('M d', strtotime($plan['start_date'])) ?> – <?= date('M d, Y', strtotime($plan['end_date'])) ?></span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.6rem;border-top:1px solid rgba(32,0,130,0.06);">
                        <div style="display:flex;align-items:center;gap:0.4rem;">
                            <i class="fas fa-list-check" style="font-size:0.72rem;color:var(--muted);"></i>
                            <span style="font-size:0.78rem;color:var(--muted);"><?= $plan['item_count'] ?> items</span>
                            <?php if ((int) $plan['item_count'] > 0): ?>
                                <span style="font-size:0.72rem;color:var(--muted);">· <?= $plan['items_done'] ?>/<?= $plan['item_count'] ?> done</span>
                            <?php endif; ?>
                        </div>
                        <button class="kt-view-btn" data-id="<?= $plan['id'] ?>"
                                style="padding:0.35rem 0.75rem;font-size:0.75rem;background:rgba(32,0,130,0.08);color:var(--primary);border:1px solid rgba(32,0,130,0.15);border-radius:999px;cursor:pointer;font-weight:600;">
                            <i class="fas fa-eye" style="margin-right:0.25rem;"></i>View
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="kt-no-results" style="display:none;text-align:center;padding:2.5rem;color:var(--muted);">
        <i class="fas fa-search" style="font-size:1.5rem;display:block;margin-bottom:0.5rem;opacity:0.4;"></i>
        <p style="margin:0;font-weight:600;">No matching plans found</p>
    </div>
</div>

<!-- View Modal -->
<div id="kt-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:var(--surface);border-radius:18px;width:90%;max-width:720px;max-height:85vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);display:flex;flex-direction:column;">
        <div style="padding:1.5rem 1.5rem 1rem;border-bottom:1px solid rgba(32,0,130,0.08);display:flex;align-items:center;justify-content:space-between;">
            <h2 id="kt-modal-title" style="margin:0;font-size:1.15rem;font-weight:800;color:var(--text);">Knowledge Transfer Plan</h2>
            <button id="kt-modal-close" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--muted);padding:0.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <div id="kt-modal-body" style="padding:1.25rem 1.5rem;overflow-y:auto;flex:1;"></div>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('kt-search');
    var statusFilter = document.getElementById('kt-status-filter');
    var noResults = document.getElementById('kt-no-results');

    function filterCards() {
        var q = (searchInput.value || '').toLowerCase().trim();
        var status = statusFilter.value;
        var cards = document.querySelectorAll('.kt-plan-card');
        var visible = 0;
        cards.forEach(function(card) {
            var matchSearch = !q || card.dataset.search.indexOf(q) > -1;
            var matchStatus = !status || card.dataset.status === status;
            var show = matchSearch && matchStatus;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        noResults.style.display = (cards.length > 0 && visible === 0) ? '' : 'none';
    }
    searchInput.addEventListener('input', filterCards);
    statusFilter.addEventListener('change', filterCards);

    // View modal
    var overlay = document.getElementById('kt-modal-overlay');
    var body = document.getElementById('kt-modal-body');
    var title = document.getElementById('kt-modal-title');

    document.querySelectorAll('.kt-view-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var planId = btn.dataset.id;
            title.textContent = 'Knowledge Transfer Plan';
            body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            overlay.style.display = 'flex';

            fetch('../../modules/exit/pages/ajax/get-knowledge-transfer.php?id=' + planId, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.plan) {
                    body.innerHTML = '<p style="color:#dc3545;">Unable to load plan details.</p>';
                    return;
                }
                var p = data.plan;
                var items = data.items || [];
                var badges = <?= json_encode([
                    'active' => kt_status_badge('active'),
                    'completed' => kt_status_badge('completed'),
                    'cancelled' => kt_status_badge('cancelled'),
                ]) ?>;
                var itemStatusBadge = {
                    'pending': '<span style="display:inline-block;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.68rem;font-weight:700;background:rgba(100,100,100,0.1);color:#666;">Pending</span>',
                    'in_progress': '<span style="display:inline-block;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.68rem;font-weight:700;background:rgba(217,119,6,0.1);color:#d97706;">In Progress</span>',
                    'completed': '<span style="display:inline-block;padding:0.15rem 0.5rem;border-radius:999px;font-size:0.68rem;font-weight:700;background:rgba(5,150,105,0.1);color:#059669;">Completed</span>'
                };
                var priorityLabel = {
                    'low': '<span style="font-size:0.68rem;color:#666;">Low</span>',
                    'medium': '<span style="font-size:0.68rem;color:#d97706;">Medium</span>',
                    'high': '<span style="font-size:0.68rem;color:#dc3545;font-weight:700;">High</span>'
                };

                var html = '';
                html += '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.25rem;">';
                html += '<div><label style="display:block;font-size:0.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Employee</label><p style="margin:0.35rem 0 0;font-size:0.92rem;font-weight:600;">' + (p.employee_name || 'N/A') + '</p></div>';
                html += '<div><label style="display:block;font-size:0.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Successor</label><p style="margin:0.35rem 0 0;font-size:0.92rem;">' + (p.successor_name || '—') + '</p></div>';
                html += '<div><label style="display:block;font-size:0.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Start Date</label><p style="margin:0.35rem 0 0;font-size:0.92rem;">' + p.start_date + '</p></div>';
                html += '<div><label style="display:block;font-size:0.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">End Date</label><p style="margin:0.35rem 0 0;font-size:0.92rem;">' + p.end_date + '</p></div>';
                html += '<div><label style="display:block;font-size:0.68rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;">Status</label><p style="margin:0.35rem 0 0;">' + (badges[p.status] || p.status) + '</p></div>';
                html += '</div>';

                // Items
                html += '<div style="border-top:1px solid rgba(32,0,130,0.08);padding-top:1rem;">';
                html += '<h3 style="margin:0 0 0.75rem;font-size:0.85rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;"><i class="fas fa-list-check" style="margin-right:0.4rem;"></i>Transfer Items (' + items.length + ')</h3>';

                if (items.length === 0) {
                    html += '<div style="text-align:center;padding:1.5rem;color:var(--muted);opacity:0.6;"><i class="fas fa-inbox" style="font-size:1.2rem;display:block;margin-bottom:0.4rem;"></i>No transfer items yet.</div>';
                } else {
                    html += '<div style="display:grid;gap:0.5rem;">';
                    items.forEach(function(item) {
                        html += '<div style="padding:0.85rem 1rem;background:rgba(32,0,130,0.03);border:1px solid rgba(32,0,130,0.08);border-radius:10px;">';
                        html += '<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">';
                        html += '<span style="font-weight:700;font-size:0.88rem;color:var(--text);">' + item.title + '</span>';
                        html += itemStatusBadge[item.status] || '';
                        html += '</div>';
                        html += '<div style="font-size:0.78rem;color:var(--muted);margin-bottom:0.25rem;">Type: <strong>' + item.item_type + '</strong> &bull; Priority: ' + (priorityLabel[item.priority] || item.priority) + '</div>';
                        if (item.description) {
                            html += '<div style="font-size:0.8rem;color:var(--muted);line-height:1.5;">' + item.description + '</div>';
                        }
                        if (item.notes) {
                            html += '<div style="font-size:0.75rem;color:var(--border);margin-top:0.25rem;"><em>Notes: ' + item.notes + '</em></div>';
                        }
                        html += '</div>';
                    });
                    html += '</div>';
                }
                html += '</div>';

                body.innerHTML = html;
            })
            .catch(function() {
                body.innerHTML = '<p style="color:#dc3545;">Failed to load plan details.</p>';
            });
        });
    });

    document.getElementById('kt-modal-close').addEventListener('click', function() {
        overlay.style.display = 'none';
    });
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
})();
</script>
