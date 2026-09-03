<div class="module-header">
    <h1>Document Requests</h1>
    <p>Review and process employee document requests.</p>
</div>

<div class="module-content">
    <div class="employee-filters">
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
