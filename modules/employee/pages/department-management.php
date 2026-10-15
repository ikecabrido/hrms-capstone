<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Department.php';

// Load ALL departments (Active and Inactive) directly from em_departments —
// no hardcoded list. Employee-assignment dropdowns elsewhere continue to use
// Department::getAllDepartments(true) (Active only); this page is the one
// place that intentionally needs to see both statuses.
$departmentClass = new Department();
$departments = $departmentClass->getAllDepartments(false);
?>

<div class="module-header">
    <h1>Department Management</h1>
    <p>View, add, and manage departments used across Employee Management.</p>
</div>

<div class="module-content">
    <div class="form-section">
        <div class="form-section-header">
            <h3>Departments</h3>
            <button type="button" data-modal-open="add-department-modal" class="btn-primary">+ Add Department</button>
        </div>

        <div class="table-wrapper">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Department Code</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="department-table-body">
                    <?php if (empty($departments)): ?>
                        <tr><td colspan="5">No departments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($departments as $d): ?>
                            <?php
                                $isActive = ($d['status'] ?? 'Active') === 'Active';
                                $statusClass = $isActive ? 'status-active' : 'status-inactive';
                                $statusLabel = $isActive ? 'ACTIVE' : 'INACTIVE';
                                $statusAction = $isActive ? 'Deactivate' : 'Activate';
                                $nextStatus = $isActive ? 'Inactive' : 'Active';
                            ?>
                            <tr class="department-row">
                                <td><?= htmlspecialchars($d['department_code'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['department_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['description'] ?? '') ?: '&mdash;' ?></td>
                                <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                <td>
                                    <button type="button" class="btn-edit-department" data-id="<?= (int) $d['department_id'] ?>">Edit</button>
                                    <button type="button" class="btn-toggle-department-status" data-id="<?= (int) $d['department_id'] ?>" data-next-status="<?= $nextStatus ?>"><?= $statusAction ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─────────────── Add Department Modal ─────────────── -->
<div id="add-department-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Add Department</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="add-department-message" class="alert" style="display:none;"></div>
        <form id="add-department-form" data-skip="true">
            <div class="form-grid">
                <input type="text" name="department_code" placeholder="Department Code *" required>
                <input type="text" name="department_name" placeholder="Department Name *" required>
                <textarea name="description" placeholder="Description"></textarea>
            </div>
            <button type="submit" class="btn-primary">Save Department</button>
        </form>
    </div>
</div>

<!-- ─────────────── Edit Department Modal ─────────────── -->
<div id="edit-department-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Edit Department</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="edit-department-message" class="alert" style="display:none;"></div>
        <form id="edit-department-form" data-skip="true">
            <input type="hidden" name="department_id">
            <div class="form-grid">
                <input type="text" name="department_code" placeholder="Department Code *" required>
                <input type="text" name="department_name" placeholder="Department Name *" required>
                <textarea name="description" placeholder="Description"></textarea>
            </div>
            <button type="submit" class="btn-primary">Update Department</button>
        </form>
    </div>
</div>
