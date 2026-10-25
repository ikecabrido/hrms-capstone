<div class="module-header">
    <h1>Document Management</h1>
    <p>Browse uploaded employee documents and view or download files.</p>
</div>

<div class="module-content">
    <div class="employee-filters">
        <input type="search" id="dm-filter-search" placeholder="Search employee or document…">
        <select id="dm-filter-type">
            <option value="">All Document Types</option>
        </select>
        <select id="dm-filter-status">
            <option value="">All Statuses</option>
        </select>
    </div>

    <div id="dm-message" class="alert" style="display:none;"></div>

    <div class="table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Document Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Uploaded Date</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>View/Download</th>
                </tr>
            </thead>
            <tbody id="dm-documents-table-body">
                <tr><td colspan="9">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>
