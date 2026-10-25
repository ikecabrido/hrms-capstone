<?php
include_once __DIR__ . '/../../../auth/session.php';
require_once __DIR__ . '/../classes/Employee.php';

$employeeClass = new Employee();
$employees = $employeeClass->getEmployees();
?>

<div class="module-header">
    <h1>Document Requests</h1>
    <p>Review and process employee document requests.</p>
</div>

<div class="module-content">
    <div class="form-section-header">
        <h3>Requests</h3>
        <button type="button" data-modal-open="create-request-modal" class="btn-primary">+ Request Document</button>
    </div>

    <div class="employee-filters">
        <input type="search" id="dr-filter-search" placeholder="Search employee or request…">
        <select id="dr-filter-status">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Approved">Approved</option>
            <option value="Released">Released</option>
            <option value="Rejected">Rejected</option>
        </select>
        <select id="dr-filter-archived">
            <option value="0">Active</option>
            <option value="1">Archived</option>
            <option value="">All</option>
        </select>
    </div>

    <div id="document-requests-message" class="alert" style="display:none;"></div>

    <div class="table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Document Type</th>
                    <th>Date Requested</th>
                    <th>Required By</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="document-requests-table-body">
                <tr><td colspan="9">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ─────────────── Create Request Modal ─────────────── -->
<div id="create-request-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Request Document</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="create-request-message" class="alert" style="display:none;"></div>
        <form id="create-document-request-form" data-skip="true">
            <div class="form-grid">
                <select name="employee_id" required>
                    <option value="">Select Employee *</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= (int) $employee['employee_id'] ?>">
                            <?= htmlspecialchars($employee['employee_code'] . ' — ' . $employee['first_name'] . ' ' . $employee['last_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="dr-request-document-type" name="document_type" required>
                    <option value="">Select Document Type *</option>
                    <option value="Certificate of Employment">Certificate of Employment</option>
                    <option value="Quitclaim and Release">Quitclaim and Release</option>
                    <option value="Leave Agreement">Leave Agreement</option>
                    <option value="Training Bond">Training Bond</option>
                    <option value="Study Leave Agreement">Study Leave Agreement</option>
                    <option value="Non-Compete Agreement">Non-Compete Agreement</option>
                    <option value="Return-to-Work Agreement">Return-to-Work Agreement</option>
                    <option value="Clearance Survey">Clearance Survey</option>
                    <option value="Exit Clearance">Exit Clearance</option>
                </select>
                <input type="text" id="dr-request-other-type" placeholder="Document name *" style="display:none;">
                <select name="priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
                <label>
                    Required By
                    <input type="date" name="required_by">
                </label>
                <textarea name="notes" placeholder="Notes"></textarea>
            </div>
            <button type="submit" class="btn-primary">Create Request</button>
        </form>
    </div>
</div>

<!-- ─────────────── Process Request Modal ─────────────── -->
<div id="process-request-modal" class="modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Process Document Request</h3>
            <button type="button" data-modal-close class="modal-close">&times;</button>
        </div>
        <div id="process-request-message" class="alert" style="display:none;"></div>
        <div id="process-request-summary" class="view-detail-list"></div>
        <form id="process-request-form" data-skip="true">
            <input type="hidden" name="request_id">
            <div class="form-grid">
                <select name="request_status">
                    <option value="">Status (no change)</option>
                    <option value="Pending">Pending</option>
                    <option value="Processing">Processing</option>
                    <option value="Approved">Approved</option>
                    <option value="Released">Released</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <input type="text" name="assigned_to" placeholder="Assign to (staff name/ID)">
                <select name="signature_status">
                    <option value="">Signature Status (no change)</option>
                    <option value="Pending">Pending</option>
                    <option value="Signed">Signed</option>
                    <option value="Waived">Waived</option>
                </select>
                <label style="display:flex; align-items:center; gap:0.4rem;">
                    <input type="checkbox" name="verified" value="1"> Mark as verified
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem;">
                    <input type="checkbox" name="archived" value="1"> Archive this request
                </label>
                <textarea name="notes" placeholder="Notes"></textarea>
            </div>
            <button type="submit" class="btn-primary">Save Changes</button>
        </form>
    </div>
</div>
