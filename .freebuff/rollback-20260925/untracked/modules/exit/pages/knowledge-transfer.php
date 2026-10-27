<?php
/**
 * Exit — Knowledge Transfer.
 *
 * The page this module's own navigation has been advertising since it was scaffolded: until
 * now every item in the Exit sidebar pointed at a file that did not exist, and the offboarding
 * handover was only manageable from the Learning & Development screen, which imported the
 * module's data and wrote its plans for it.
 *
 * The handover record belongs here — exit_knowledge_transfer_plans and its items are this
 * module's tables — so this page is the screen that creates and edits plans, through
 * classes/knowledgetransfer.php, the one implementation shared with the save endpoint. L&D
 * keeps its own view of the same rows, because turning a handover into training is its job.
 *
 * Items are not created here: they are derived from the departing employee's courses, which is
 * L&D's side of a handover (see the learning module's Knowledge Transfer screen). What this page
 * does with them is keep them moving, one status at a time.
 *
 * Access follows this module's own convention (see pages/ajax/get-employees.php): a signed-in
 * employee. There is no role rule in the Exit module to follow — see the integration notes.
 */
require_once dirname(__DIR__, 3) . '/auth/session.php';
require_once dirname(__DIR__, 3) . '/database/db.php';
require_once __DIR__ . '/../classes/knowledgetransfer.php';

$plans = [];
$employees = [];
$itemsByPlan = [];
$stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'items' => 0, 'items_done' => 0];

try {
    $pdo = (new Database())->getConnection();

    $plans = $pdo->query("
        SELECT p.id, p.employee_id, p.successor_id, p.start_date, p.end_date, p.status, p.created_by,
               CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
               CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name,
               d.department_name,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items i WHERE i.plan_id = p.id) AS item_count,
               (SELECT COUNT(*) FROM exit_knowledge_transfer_items i WHERE i.plan_id = p.id AND i.status = 'completed') AS items_done
          FROM exit_knowledge_transfer_plans p
          LEFT JOIN em_employees e ON e.employee_id = p.employee_id
          LEFT JOIN em_employees s ON s.employee_id = p.successor_id
          LEFT JOIN em_departments d ON d.department_id = e.department_id
         ORDER BY FIELD(p.status, 'active', 'completed', 'cancelled'), p.end_date ASC, p.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $employees = $pdo->query("
        SELECT employee_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) AS full_name
          FROM em_employees ORDER BY first_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if ($plans !== []) {
        // Every item of every listed plan in one query, grouped in PHP rather than one query per row.
        $ids = implode(',', array_map(static fn(array $plan): int => (int) $plan['id'], $plans));
        foreach ($pdo->query("
            SELECT plan_id, id, item_type, title, description, priority, status
              FROM exit_knowledge_transfer_items
             WHERE plan_id IN ({$ids})
             ORDER BY FIELD(priority, 'high', 'medium', 'low'), id ASC
        ")->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $itemsByPlan[(int) $item['plan_id']][] = $item;
        }
    }

    foreach ($plans as $plan) {
        $stats['total']++;
        if ($plan['status'] === 'active') {
            $stats['active']++;
        }
        if ($plan['status'] === 'completed') {
            $stats['completed']++;
        }
        $stats['items'] += (int) $plan['item_count'];
        $stats['items_done'] += (int) $plan['items_done'];
    }
} catch (Throwable $e) {
    error_log('exit/knowledge-transfer.php load failed: ' . $e->getMessage());
}

/** Status pill for a plan. */
function ktStatusBadge(string $status): string
{
    $map = [
        'active'    => ['rgba(5,150,105,0.1)', '#059669', 'Active'],
        'completed' => ['rgba(37,99,235,0.1)', '#2563eb', 'Completed'],
        'cancelled' => ['rgba(220,53,69,0.1)', '#dc3545', 'Cancelled'],
    ];
    [$bg, $colour, $label] = $map[$status] ?? ['rgba(100,100,100,0.1)', '#666', ucfirst($status)];

    return '<span style="display:inline-block;padding:0.2rem 0.7rem;border-radius:999px;font-size:0.72rem;font-weight:700;background:' . $bg . ';color:' . $colour . ';">' . $label . '</span>';
}

/** Status pill for a transfer item. */
function ktItemBadge(string $status): string
{
    $map = [
        'pending'     => ['rgba(100,116,139,0.12)', '#475569', 'Pending'],
        'in_progress' => ['rgba(217,119,6,0.12)', '#b45309', 'In progress'],
        'completed'   => ['rgba(5,150,105,0.12)', '#047857', 'Completed'],
    ];
    [$bg, $colour, $label] = $map[$status] ?? ['rgba(100,100,100,0.1)', '#666', ucfirst($status)];

    return '<span style="display:inline-block;padding:0.15rem 0.6rem;border-radius:999px;font-size:0.68rem;font-weight:700;background:' . $bg . ';color:' . $colour . ';">' . $label . '</span>';
}

$labelStyle = 'display:block;font-size:0.72rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.35rem;';
$fieldStyle = 'width:100%;padding:0.6rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;font-size:0.88rem;outline:none;background:#fff;';
?>
<div class="module-header">
    <h1 class="module-header-title">Knowledge Transfer</h1>
    <p class="module-header-subtitle">Handovers being passed from a departing employee to their successor.</p>
</div>

<div class="module-content">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <?php foreach ([
            ['Total plans', $stats['total'], 'var(--primary)', 'fa-exchange-alt'],
            ['Active', $stats['active'], '#059669', 'fa-play-circle'],
            ['Completed', $stats['completed'], '#2563eb', 'fa-check-circle'],
            ['Transfer items', $stats['items'] . ' (' . $stats['items_done'] . ' done)', 'var(--primary)', 'fa-list-check'],
        ] as [$label, $value, $colour, $icon]): ?>
            <div class="analytics-card">
                <h2><i class="fas <?= $icon ?>" style="margin-right:0.4rem;opacity:0.6;"></i><?= htmlspecialchars($label) ?></h2>
                <p class="analytics-value" style="color:<?= $colour ?>;"><?= htmlspecialchars((string) $value) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;position:relative;">
            <i class="fas fa-search" style="position:absolute;left:0.85rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:0.85rem;"></i>
            <input type="search" id="kt-search" placeholder="Search by employee, successor or status..."
                   style="width:100%;padding:0.6rem 1rem 0.6rem 2.5rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:10px;background:rgba(32,0,130,0.03);font-size:0.88rem;outline:none;box-sizing:border-box;"
                   aria-label="Search transfer plans" />
        </div>
        <select id="kt-status-filter" style="padding:0.55rem 0.75rem;border:1.5px solid rgba(32,0,130,0.1);border-radius:8px;background:#fff;font-size:0.82rem;font-weight:600;cursor:pointer;">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <button type="button" id="kt-new-plan" style="padding:0.55rem 1.1rem;border:0;border-radius:8px;background:var(--primary);color:#fff;font-size:0.82rem;font-weight:700;cursor:pointer;">
            <i class="fas fa-plus" style="margin-right:0.3rem;"></i>New transfer plan
        </button>
    </div>

    <div class="mode-card">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead style="background:rgba(32,0,130,0.03);">
                    <tr>
                        <?php foreach (['Employee', 'Successor', 'Period', 'Items', 'Status', 'Actions'] as $heading): ?>
                            <th style="padding:0.7rem 0.85rem;text-align:left;font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--primary);white-space:nowrap;"><?= $heading ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($plans === []): ?>
                    <tr><td colspan="6" style="padding:3rem;text-align:center;color:var(--muted);">
                        <i class="fas fa-exchange-alt" style="font-size:1.6rem;display:block;margin-bottom:0.6rem;opacity:0.4;"></i>
                        No transfer plans yet. Raise one for an employee who is leaving, and L&amp;D will build the training side from it.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($plans as $plan):
                        $items = $itemsByPlan[(int) $plan['id']] ?? [];
                        ?>
                        <tr class="kt-plan-row" data-search="<?= htmlspecialchars(strtolower(($plan['employee_name'] ?? '') . ' ' . ($plan['successor_name'] ?? '') . ' ' . $plan['status']), ENT_QUOTES) ?>"
                            data-status="<?= htmlspecialchars((string) $plan['status'], ENT_QUOTES) ?>"
                            style="border-top:1px solid rgba(32,0,130,0.06);">
                            <td style="padding:0.7rem 0.85rem;font-size:0.85rem;">
                                <strong><?= htmlspecialchars($plan['employee_name'] ?: 'Unknown employee') ?></strong>
                                <div style="font-size:0.72rem;color:var(--muted);">
                                    #<?= (int) $plan['employee_id'] ?><?= $plan['department_name'] ? ' · ' . htmlspecialchars($plan['department_name']) : '' ?>
                                </div>
                            </td>
                            <td style="padding:0.7rem 0.85rem;font-size:0.85rem;"><?= htmlspecialchars($plan['successor_name'] ?: '—') ?></td>
                            <td style="padding:0.7rem 0.85rem;font-size:0.82rem;color:var(--muted);white-space:nowrap;">
                                <?= htmlspecialchars(date('M j, Y', strtotime((string) $plan['start_date']))) ?>
                                – <?= htmlspecialchars(date('M j, Y', strtotime((string) $plan['end_date']))) ?>
                            </td>
                            <td style="padding:0.7rem 0.85rem;font-size:0.82rem;white-space:nowrap;">
                                <?= (int) $plan['items_done'] ?>/<?= (int) $plan['item_count'] ?>
                            </td>
                            <td style="padding:0.7rem 0.85rem;"><?= ktStatusBadge((string) $plan['status']) ?></td>
                            <td style="padding:0.7rem 0.85rem;white-space:nowrap;">
                                <button type="button" class="kt-toggle-items" data-target="kt-items-<?= (int) $plan['id'] ?>"
                                        style="padding:0.35rem 0.7rem;font-size:0.72rem;background:rgba(32,0,130,0.08);color:var(--primary);border:1px solid rgba(32,0,130,0.15);border-radius:999px;cursor:pointer;font-weight:700;">
                                    <i class="fas fa-list-check"></i> Items (<?= count($items) ?>)
                                </button>
                                <button type="button" class="kt-edit-plan" data-id="<?= (int) $plan['id'] ?>"
                                        data-employee="<?= (int) $plan['employee_id'] ?>"
                                        data-successor="<?= (int) ($plan['successor_id'] ?? 0) ?>"
                                        data-start="<?= htmlspecialchars((string) $plan['start_date'], ENT_QUOTES) ?>"
                                        data-end="<?= htmlspecialchars((string) $plan['end_date'], ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars((string) $plan['status'], ENT_QUOTES) ?>"
                                        style="padding:0.35rem 0.7rem;font-size:0.72rem;background:rgba(245,158,11,0.1);color:#d97706;border:1px solid rgba(245,158,11,0.2);border-radius:999px;cursor:pointer;font-weight:700;">
                                    <i class="fas fa-pen"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <tr class="kt-items-row" id="kt-items-<?= (int) $plan['id'] ?>" style="display:none;background:rgba(32,0,130,0.02);">
                            <td colspan="6" style="padding:0.85rem 1rem;">
                                <?php if ($items === []): ?>
                                    <p style="margin:0;font-size:0.82rem;color:var(--muted);">
                                        No transfer items yet. L&amp;D generates them from the departing employee's courses —
                                        see <a href="../learning/index.php?page=admin/knowledge-transfer" style="color:var(--primary);font-weight:700;">Knowledge Transfer in Learning &amp; Development</a>.
                                    </p>
                                <?php else: ?>
                                    <div style="display:grid;gap:0.5rem;">
                                        <?php foreach ($items as $item): ?>
                                            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.85rem;background:#fff;border:1px solid rgba(32,0,130,0.08);border-radius:10px;">
                                                <div style="flex:1;min-width:0;">
                                                    <div style="font-size:0.85rem;font-weight:700;color:var(--text);"><?= htmlspecialchars((string) $item['title']) ?></div>
                                                    <div style="font-size:0.74rem;color:var(--muted);">
                                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $item['item_type']))) ?>
                                                        · priority <?= htmlspecialchars((string) $item['priority']) ?>
                                                        <?= $item['description'] ? ' · ' . htmlspecialchars((string) $item['description']) : '' ?>
                                                    </div>
                                                </div>
                                                <span class="kt-item-badge"><?= ktItemBadge((string) $item['status']) ?></span>
                                                <select class="kt-item-status" data-item-id="<?= (int) $item['id'] ?>" style="<?= $fieldStyle ?>width:auto;">
                                                    <?php foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label): ?>
                                                        <option value="<?= $value ?>"<?= $item['status'] === $value ? ' selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <p id="kt-no-results" style="display:none;margin:1rem 0 0;font-size:0.85rem;color:var(--muted);">No plans match that search.</p>
    </div>
</div>

<div id="kt-plan-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:10060;align-items:center;justify-content:center;padding:1rem;">
    <div style="background:var(--surface,#fff);border-radius:18px;width:92%;max-width:560px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h3 id="kt-plan-title" style="margin:0;font-size:1.1rem;font-weight:800;color:var(--text);">New transfer plan</h3>
            <button type="button" id="kt-plan-close" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:var(--muted);"><i class="fas fa-times"></i></button>
        </div>
        <div style="display:grid;gap:1rem;">
            <label><span style="<?= $labelStyle ?>">Employee leaving</span>
                <select id="kt-employee" style="<?= $fieldStyle ?>">
                    <option value="">Select employee...</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['employee_id'] ?>"><?= htmlspecialchars((string) $employee['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span style="<?= $labelStyle ?>">Successor (person taking the handover)</span>
                <select id="kt-successor" style="<?= $fieldStyle ?>">
                    <option value="">Select successor...</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['employee_id'] ?>"><?= htmlspecialchars((string) $employee['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <label><span style="<?= $labelStyle ?>">Start date</span><input type="date" id="kt-start" style="<?= $fieldStyle ?>"></label>
                <label><span style="<?= $labelStyle ?>">End date</span><input type="date" id="kt-end" style="<?= $fieldStyle ?>"></label>
            </div>
            <label><span style="<?= $labelStyle ?>">Status</span>
                <select id="kt-plan-status" style="<?= $fieldStyle ?>">
                    <?php foreach (KnowledgeTransfer::STATUSES as $status): ?>
                        <option value="<?= $status ?>"><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <p id="kt-plan-error" style="display:none;margin:0.85rem 0 0;font-size:0.82rem;color:#dc3545;font-weight:600;"></p>
        <div style="display:flex;gap:0.6rem;justify-content:flex-end;margin-top:1.5rem;">
            <button type="button" id="kt-plan-cancel" style="padding:0.5rem 1rem;background:transparent;color:var(--text);border:1.5px solid rgba(32,0,130,0.15);border-radius:999px;font-weight:700;font-size:0.82rem;cursor:pointer;">Cancel</button>
            <button type="button" id="kt-plan-save" style="padding:0.5rem 1rem;background:var(--primary);color:#fff;border:none;border-radius:999px;font-weight:700;font-size:0.82rem;cursor:pointer;">Save plan</button>
        </div>
    </div>
</div>

<script>
(function () {
    var search = document.getElementById('kt-search');
    var statusFilter = document.getElementById('kt-status-filter');
    var noResults = document.getElementById('kt-no-results');

    function filterPlans() {
        var q = (search.value || '').toLowerCase().trim();
        var status = statusFilter.value;
        var visible = 0;
        document.querySelectorAll('.kt-plan-row').forEach(function (row) {
            var show = (!q || row.dataset.search.indexOf(q) > -1) && (!status || row.dataset.status === status);
            row.style.display = show ? '' : 'none';
            var itemsRow = document.getElementById(row.querySelector('.kt-toggle-items').dataset.target);
            if (!show && itemsRow) { itemsRow.style.display = 'none'; }
            if (show) { visible++; }
        });
        noResults.style.display = visible === 0 && document.querySelectorAll('.kt-plan-row').length > 0 ? '' : 'none';
    }

    search.addEventListener('input', filterPlans);
    statusFilter.addEventListener('change', filterPlans);

    document.querySelectorAll('.kt-toggle-items').forEach(function (button) {
        button.addEventListener('click', function () {
            var row = document.getElementById(button.dataset.target);
            if (!row) { return; }
            var open = row.style.display !== 'none';
            row.style.display = open ? 'none' : 'table-row';
            button.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
    });

    // Item statuses: one endpoint, one item at a time.
    document.querySelectorAll('.kt-item-status').forEach(function (select) {
        select.addEventListener('change', function () {
            select.disabled = true;
            fetch('pages/ajax/update-knowledge-transfer-item.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'item_id=' + encodeURIComponent(select.dataset.itemId) + '&status=' + encodeURIComponent(select.value)
            }).then(function (response) { return response.json(); }).then(function (data) {
                select.disabled = false;
                if (!data || !data.success) {
                    select.value = select.dataset.previous || select.value;
                    window.alert((data && data.error) || 'The item status could not be saved.');
                    return;
                }
                select.dataset.previous = select.value;
                var badge = select.parentElement.querySelector('.kt-item-badge');
                if (badge && data.badge) { badge.outerHTML = data.badge; }
            }).catch(function () {
                select.disabled = false;
                window.alert('The item status could not be saved.');
            });
        });
        select.dataset.previous = select.value;
    });

    // Create / edit modal
    var overlay = document.getElementById('kt-plan-overlay');
    var title = document.getElementById('kt-plan-title');
    var errorLine = document.getElementById('kt-plan-error');
    var employee = document.getElementById('kt-employee');
    var successor = document.getElementById('kt-successor');
    var start = document.getElementById('kt-start');
    var end = document.getElementById('kt-end');
    var status = document.getElementById('kt-plan-status');
    var saveButton = document.getElementById('kt-plan-save');
    var editingId = 0;

    function openModal(plan) {
        editingId = plan ? plan.id : 0;
        title.textContent = plan ? 'Edit transfer plan' : 'New transfer plan';
        employee.value = plan ? String(plan.employee_id) : '';
        successor.value = plan ? String(plan.successor_id || '') : '';
        start.value = plan ? plan.start_date : '';
        end.value = plan ? plan.end_date : '';
        status.value = plan ? plan.status : 'active';
        errorLine.style.display = 'none';
        overlay.style.display = 'flex';
    }

    function closeModal() { overlay.style.display = 'none'; }

    document.getElementById('kt-new-plan').addEventListener('click', function () { openModal(null); });
    document.getElementById('kt-plan-close').addEventListener('click', closeModal);
    document.getElementById('kt-plan-cancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (event) { if (event.target === overlay) { closeModal(); } });

    document.querySelectorAll('.kt-edit-plan').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal({
                id: parseInt(button.dataset.id, 10),
                employee_id: parseInt(button.dataset.employee, 10),
                successor_id: parseInt(button.dataset.successor, 10),
                start_date: button.dataset.start,
                end_date: button.dataset.end,
                status: button.dataset.status
            });
        });
    });

    saveButton.addEventListener('click', function () {
        if (!employee.value) { errorLine.textContent = 'Choose the employee whose handover this is.'; errorLine.style.display = ''; return; }
        if (!start.value || !end.value) { errorLine.textContent = 'Both dates are required.'; errorLine.style.display = ''; return; }

        var payload = {
            employee_id: parseInt(employee.value, 10),
            successor_id: successor.value ? parseInt(successor.value, 10) : null,
            start_date: start.value,
            end_date: end.value,
            status: status.value
        };
        if (editingId) { payload.id = editingId; }

        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';

        fetch('pages/ajax/save-knowledge-transfer.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        }).then(function (response) { return response.json(); }).then(function (data) {
            saveButton.disabled = false;
            saveButton.textContent = 'Save plan';
            if (!data || !data.success) {
                errorLine.textContent = (data && data.error) || 'The plan could not be saved.';
                errorLine.style.display = '';
                return;
            }
            window.location.reload();
        }).catch(function () {
            saveButton.disabled = false;
            saveButton.textContent = 'Save plan';
            errorLine.textContent = 'The plan could not be saved.';
            errorLine.style.display = '';
        });
    });
})();
</script>
