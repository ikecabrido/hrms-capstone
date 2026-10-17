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

/** Badge class + label for a plan status. */
function ktStatusMeta(string $status): array {
    $map = [
        'active'    => ['is-active', 'Active'],
        'completed' => ['is-completed', 'Completed'],
        'cancelled' => ['is-cancelled', 'Cancelled'],
    ];
    return $map[$status] ?? ['is-muted', ucfirst($status)];
}

/** Lowercased text the toolbar search matches against. */
function ktSearchKey(array $plan): string {
    $raw = ($plan['employee_name'] ?? '') . ' ' . ($plan['successor_name'] ?? '') . ' ' . ($plan['status'] ?? '');
    return htmlspecialchars(strtolower($raw), ENT_QUOTES, 'UTF-8');
}

/** Readable date, or a dash when the plan has none. */
function ktDate(?string $date): string {
    return !empty($date) ? date('M d, Y', strtotime($date)) : '&mdash;';
}
?>
<?php include __DIR__ . '/../../includes/admin-user-navigation.php'; ?>
<div class="module-content kt-page">
    <div class="home-dashboard">
        <section class="home-hero">
            <div class="home-hero-main">
                <h1>Knowledge Transfer</h1>
                <p>Handover plans between leaving employees and the successors taking over their work.</p>
            </div>
            <div class="home-hero-aside">
                <div class="home-hero-chips">
                    <span class="home-hero-chip"><i class="fas fa-exchange-alt"></i> <?= (int) $stats['total'] ?> plan<?= $stats['total'] !== 1 ? 's' : '' ?></span>
                    <span class="home-hero-chip"><i class="fas fa-bolt"></i> <?= (int) $stats['active'] ?> active</span>
                </div>
            </div>
        </section>

        <div class="home-stats">
            <div class="home-stat">
                <span class="home-stat-icon tone-violet"><i class="fas fa-exchange-alt"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Total Plans</span>
                    <span class="home-stat-value"><?= (int) $stats['total'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['cancelled'] ?> cancelled</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-emerald"><i class="fas fa-play-circle"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Active</span>
                    <span class="home-stat-value"><?= (int) $stats['active'] ?></span>
                    <span class="home-stat-sub">in progress</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-indigo"><i class="fas fa-check-circle"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Completed</span>
                    <span class="home-stat-value"><?= (int) $stats['completed'] ?></span>
                    <span class="home-stat-sub">handovers closed</span>
                </span>
            </div>
            <div class="home-stat">
                <span class="home-stat-icon tone-amber"><i class="fas fa-list-check"></i></span>
                <span class="home-stat-body">
                    <span class="home-stat-label">Transfer Items</span>
                    <span class="home-stat-value"><?= (int) $stats['items_total'] ?></span>
                    <span class="home-stat-sub"><?= (int) $stats['items_completed'] ?> completed</span>
                </span>
            </div>
        </div>

        <div class="kt-toolbar">
            <label class="kt-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="kt-search" placeholder="Search by employee, successor, or status..." aria-label="Search knowledge transfer plans" />
            </label>
            <label class="kt-filter">
                <select id="kt-status-filter" aria-label="Filter plans by status">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </label>
            <span id="kt-count" class="kt-count" aria-live="polite"><?= (int) $stats['total'] ?> plan<?= $stats['total'] !== 1 ? 's' : '' ?></span>
        </div>

        <div id="kt-plans-grid" class="kt-grid">
            <?php if (empty($plans)): ?>
                <div id="kt-empty" class="kt-empty">
                    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                    <strong>No knowledge transfer plans found</strong>
                    <span>Plans are created from the Exit Management module.</span>
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan):
                    [$badgeClass, $badgeLabel] = ktStatusMeta((string) $plan['status']);
                    $itemCount = (int) $plan['item_count'];
                    $itemsDone = (int) $plan['items_done'];
                    $progress = $itemCount > 0 ? (int) round(($itemsDone / $itemCount) * 100) : 0;
                    $employee = $plan['employee_name'] !== '' && $plan['employee_name'] !== null ? $plan['employee_name'] : 'N/A';
                ?>
                    <article class="kt-plan-card" data-id="<?= (int) $plan['id'] ?>"
                             data-status="<?= htmlspecialchars((string) $plan['status'], ENT_QUOTES, 'UTF-8') ?>"
                             data-search="<?= ktSearchKey($plan) ?>">
                        <div class="kt-card-head">
                            <span class="kt-card-avatar"><i class="fas fa-exchange-alt" aria-hidden="true"></i></span>
                            <div class="kt-card-ident">
                                <h3 class="kt-card-name"><?= htmlspecialchars($employee) ?></h3>
                                <span class="kt-card-id">Plan #<?= (int) $plan['id'] ?></span>
                            </div>
                            <span class="kt-badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                        </div>

                        <div class="kt-card-meta">
                            <div>
                                <span class="kt-meta-label">Successor</span>
                                <span class="kt-meta-value"><?= htmlspecialchars($plan['successor_name'] ?: '—') ?></span>
                            </div>
                            <div>
                                <span class="kt-meta-label">Duration</span>
                                <span class="kt-meta-value"><?= ktDate($plan['start_date']) ?> &ndash; <?= ktDate($plan['end_date']) ?></span>
                            </div>
                        </div>

                        <?php if ($itemCount > 0): ?>
                            <div class="kt-progress">
                                <span class="kt-progress-label">Progress</span>
                                <span class="kt-progress-track">
                                    <span class="kt-progress-fill" style="width:<?= $progress ?>%"></span>
                                </span>
                                <span class="kt-progress-val"><?= $progress ?>%</span>
                            </div>
                        <?php endif; ?>

                        <div class="kt-card-foot">
                            <span class="kt-card-items">
                                <i class="fas fa-list-check" aria-hidden="true"></i>
                                <?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?>
                                <?php if ($itemCount > 0): ?>&middot; <?= $itemsDone ?>/<?= $itemCount ?> done<?php endif; ?>
                            </span>
                            <button type="button" class="kt-view-btn" data-id="<?= (int) $plan['id'] ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i> View
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="kt-no-results" class="kt-no-results" style="display:none;">
            <i class="fas fa-search" aria-hidden="true"></i>
            <strong>No matching plans found</strong>
            <span>Try a different search term or status filter.</span>
        </div>
    </div>
</div>

<!-- Plan detail modal -->
<div id="kt-modal-overlay" class="kt-modal-overlay" style="display:none;">
    <div class="kt-modal" role="dialog" aria-modal="true" aria-labelledby="kt-modal-title">
        <div class="kt-modal-head">
            <h2 id="kt-modal-title">Knowledge Transfer Plan</h2>
            <button type="button" id="kt-modal-close" class="kt-modal-close" aria-label="Close plan details">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div id="kt-modal-body" class="kt-modal-body"></div>
    </div>
</div>

<script>
(function() {
    var grid = document.getElementById('kt-plans-grid');
    var searchInput = document.getElementById('kt-search');
    var statusFilter = document.getElementById('kt-status-filter');
    var noResults = document.getElementById('kt-no-results');
    var countEl = document.getElementById('kt-count');

    var overlay = document.getElementById('kt-modal-overlay');
    var modalBody = document.getElementById('kt-modal-body');
    var modalTitle = document.getElementById('kt-modal-title');
    var closeBtn = document.getElementById('kt-modal-close');

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var STATUS = {
        'active':      { cls: 'is-active',      label: 'Active' },
        'completed':   { cls: 'is-completed',   label: 'Completed' },
        'cancelled':   { cls: 'is-cancelled',   label: 'Cancelled' },
        'in_progress': { cls: 'is-in_progress', label: 'In Progress' },
        'pending':     { cls: 'is-pending',     label: 'Pending' }
    };
    var PRIORITY = { 'low': 'Low', 'medium': 'Medium', 'high': 'High' };

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function badge(status) {
        var meta = STATUS[String(status)] || { cls: 'is-muted', label: String(status || 'Unknown') };
        return '<span class="kt-badge ' + meta.cls + '">' + esc(meta.label) + '</span>';
    }

    function formatDate(value) {
        if (!value) return '&mdash;';
        var match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value));
        if (!match) return esc(value);
        var month = MONTHS[parseInt(match[2], 10) - 1] || match[2];
        return month + ' ' + parseInt(match[3], 10) + ', ' + match[1];
    }

    function titleCase(value) {
        var text = String(value || '').replace(/_/g, ' ');
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function detail(label, value) {
        return '<div><span class="kt-detail-label">' + esc(label) + '</span>' +
               '<span class="kt-detail-value">' + value + '</span></div>';
    }

    function stateBlock(tone, icon, message) {
        return '<div class="kt-modal-state' + (tone ? ' ' + tone : '') + '">' +
               '<i class="fas ' + icon + '"></i><span>' + esc(message) + '</span></div>';
    }

    // ---- Filtering ----------------------------------------------------------
    function filterCards() {
        var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        var status = statusFilter ? statusFilter.value : '';
        var cards = document.querySelectorAll('.kt-plan-card');
        var visible = 0;

        cards.forEach(function(card) {
            var matchSearch = !query || (card.dataset.search || '').indexOf(query) > -1;
            var matchStatus = !status || card.dataset.status === status;
            var show = matchSearch && matchStatus;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (noResults) noResults.style.display = (cards.length > 0 && visible === 0) ? '' : 'none';

        if (countEl) {
            if (cards.length === 0) {
                countEl.textContent = 'No plans';
            } else if (visible === cards.length) {
                countEl.textContent = cards.length + ' plan' + (cards.length !== 1 ? 's' : '');
            } else {
                countEl.textContent = visible + ' of ' + cards.length + ' plans';
            }
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterCards);
    if (statusFilter) statusFilter.addEventListener('change', filterCards);

    // ---- Detail modal -------------------------------------------------------
    function renderPlan(plan, items) {
        var html = '<div class="kt-detail-grid">';
        html += detail('Employee', esc(plan.employee_name || 'N/A'));
        html += detail('Successor', esc(plan.successor_name || '—'));
        html += detail('Start date', formatDate(plan.start_date));
        html += detail('End date', formatDate(plan.end_date));
        html += detail('Created', formatDate(plan.created_at));
        html += '<div><span class="kt-detail-label">Status</span>' +
                '<span class="kt-detail-value">' + badge(plan.status) + '</span></div>';
        html += '</div>';

        html += '<div class="kt-detail-items">';
        html += '<h3 class="kt-detail-subhead"><i class="fas fa-list-check"></i> Transfer items (' + items.length + ')</h3>';

        if (items.length === 0) {
            html += '<div class="kt-item-empty"><i class="fas fa-inbox"></i><span>No transfer items have been added to this plan yet.</span></div>';
        } else {
            html += '<div class="kt-item-list">';
            items.forEach(function(item) {
                var priorityKey = PRIORITY[String(item.priority)] ? String(item.priority) : 'low';
                var priorityLabel = PRIORITY[String(item.priority)] || titleCase(item.priority);

                html += '<div class="kt-item">';
                html += '<div class="kt-item-head">' +
                        '<span class="kt-item-title">' + esc(item.title) + '</span>' +
                        badge(item.status) +
                        '</div>';
                html += '<div class="kt-item-meta">Type: <strong>' + esc(titleCase(item.item_type)) + '</strong>' +
                        ' &bull; Priority: <span class="kt-prio is-' + priorityKey + '">' + esc(priorityLabel) + '</span></div>';
                if (item.description) {
                    html += '<p class="kt-item-desc">' + esc(item.description) + '</p>';
                }
                if (item.notes) {
                    html += '<p class="kt-item-notes"><em>Notes: ' + esc(item.notes) + '</em></p>';
                }
                html += '</div>';
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function closeModal() {
        if (!overlay) return;
        overlay.style.display = 'none';
        if (modalBody) modalBody.innerHTML = '';
    }

    function openPlan(planId) {
        if (!planId || !overlay || !modalBody) return;

        if (modalTitle) modalTitle.textContent = 'Knowledge Transfer Plan';
        modalBody.innerHTML = stateBlock('', 'fa-spinner fa-spin', 'Loading plan...');
        overlay.style.display = 'flex';
        if (closeBtn) closeBtn.focus();

        fetch('pages/admin/ajax/get-kt-plan.php?id=' + encodeURIComponent(planId), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.success || !data.plan) {
                modalBody.innerHTML = stateBlock('is-error', 'fa-triangle-exclamation', data.error || 'Unable to load this plan.');
                return;
            }
            modalBody.innerHTML = renderPlan(data.plan, data.items || []);
        })
        .catch(function() {
            modalBody.innerHTML = stateBlock('is-error', 'fa-triangle-exclamation', 'Failed to load plan details.');
        });
    }

    if (grid) {
        grid.addEventListener('click', function(e) {
            var card = e.target.closest('.kt-plan-card');
            if (card && card.dataset.id) openPlan(card.dataset.id);
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closeModal();
        });
        overlay.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    }

    filterCards();
})();
</script>
