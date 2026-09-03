<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Employee.php';

$employeeClass = new Employee();
$employees = $employeeClass->getEmployees();

$selectedEmployeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : null;

// Known change_type values written by EmployeeController.php's history logging —
// not an enum column (change_type is varchar), so this list reflects what the
// application actually produces, not invented categories.
$changeTypes = [
    'Employee Created', 'Field Update', 'Employee Archived', 'Employee Restored',
    'Personal Info Update', 'Document Uploaded',
];
?>

<div class="module-header">
    <h1>Employee History</h1>
    <p>Audit log of changes made to employee records (sourced from <code>employee_change_history</code>).</p>
</div>

<div class="module-content">
    <div class="stat-grid stat-grid-compact" id="history-stat-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <div class="stat-label">Recent Changes</div>
                <h2 class="stat-value" id="stat-recent-changes">0</h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-green"><i class="fa-solid fa-file-arrow-up"></i></div>
            <div>
                <div class="stat-label">Documents Uploaded</div>
                <h2 class="stat-value" id="stat-documents-uploaded">0</h2>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange"><i class="fa-solid fa-user-group"></i></div>
            <div>
                <div class="stat-label">Employees Affected</div>
                <h2 class="stat-value" id="stat-employees-affected">0</h2>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-header">
            <h3>Change Log</h3>
        </div>

        <div class="employee-filters">
            <select id="history-employee-picker">
                <option value="">All Employees (last 50 changes)</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= (int) $emp['employee_id'] ?>" <?= $selectedEmployeeId === (int) $emp['employee_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['employee_code'] . ' — ' . $emp['first_name'] . ' ' . $emp['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="history-changetype-filter">
                <option value="">All Change Types</option>
                <?php foreach ($changeTypes as $ct): ?>
                    <option value="<?= htmlspecialchars($ct) ?>"><?= htmlspecialchars($ct) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="filter-date-label">From <input type="date" id="history-date-from"></label>
            <label class="filter-date-label">To <input type="date" id="history-date-to"></label>
            <button type="button" id="history-reset-btn" class="btn-secondary">Reset</button>
        </div>

        <div class="table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Change</th>
                        <th>Old → New</th>
                        <th>Updated By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="history-table-body">
                    <tr><td colspan="6">Loading…</td></tr>
                </tbody>
            </table>
        </div>

        <div id="history-pagination" class="pagination-bar" style="display:none;">
            <span id="history-pagination-summary"></span>
            <div class="pagination-controls">
                <button type="button" id="history-pagination-prev" class="btn-secondary">&larr; Prev</button>
                <span id="history-pagination-pages"></span>
                <button type="button" id="history-pagination-next" class="btn-secondary">Next &rarr;</button>
            </div>
        </div>
    </div>
</div>

<!-- ─────────────── History Detail Modal ─────────────── -->
<div id="history-detail-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Change Details</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="history-detail-body" class="view-detail-list-wrap">
            <p class="empty-item">Loading…</p>
        </div>
    </div>
</div>
