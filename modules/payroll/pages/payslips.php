<div class="module-header">
    <h1>Payslips</h1>
    <p class="ps-subtitle">View and manage generated employee payslips.</p>
</div>

<div class="module-content">
    <div class="ps-page" id="payslipsPage" data-page="payslips">

        <div class="ps-alert" id="psAlert" role="alert" style="display:none;"></div>

        <!-- Summary cards -->
        <div class="ps-summary-cards" id="psSummaryCards">
            <div class="ps-summary-card">
                <div class="ps-summary-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div class="ps-summary-text">
                    <span class="ps-summary-value" id="psTotalPayslips">&mdash;</span>
                    <span class="ps-summary-label">Total Payslips</span>
                </div>
            </div>
            <div class="ps-summary-card">
                <div class="ps-summary-icon ps-icon-gross"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="ps-summary-text">
                    <span class="ps-summary-value" id="psTotalGross">&mdash;</span>
                    <span class="ps-summary-label">Total Gross Pay</span>
                </div>
            </div>
            <div class="ps-summary-card">
                <div class="ps-summary-icon ps-icon-deductions"><i class="fa-solid fa-minus"></i></div>
                <div class="ps-summary-text">
                    <span class="ps-summary-value" id="psTotalDeductions">&mdash;</span>
                    <span class="ps-summary-label">Total Deductions</span>
                </div>
            </div>
            <div class="ps-summary-card">
                <div class="ps-summary-icon ps-icon-net"><i class="fa-solid fa-wallet"></i></div>
                <div class="ps-summary-text">
                    <span class="ps-summary-value" id="psTotalNet">&mdash;</span>
                    <span class="ps-summary-label">Total Net Pay</span>
                </div>
            </div>
        </div>

        <!-- Toolbar / Filters -->
        <div class="ps-toolbar">
            <div class="ps-toolbar-filters">
                <div class="ps-filter-group">
                    <label for="psPeriodFilter">Payroll Period</label>
                    <select id="psPeriodFilter">
                        <option value="">All Periods</option>
                    </select>
                </div>
                <div class="ps-filter-group">
                    <label for="psEmployeeFilter">Employee</label>
                    <select id="psEmployeeFilter">
                        <option value="">All Employees</option>
                    </select>
                </div>
            </div>
            <div class="ps-toolbar-actions">
                <button type="button" class="ps-btn ps-btn-secondary" id="psBtnReset">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </button>
                <button type="button" class="ps-btn ps-btn-primary" id="psBtnApply">
                    <i class="fa-solid fa-filter"></i> Apply Filters
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="ps-table-card">
            <div class="ps-table-wrapper">
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee Code</th>
                            <th>Payroll Period</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th class="ps-actions-col">Action</th>
                        </tr>
                    </thead>
                    <tbody id="psTableBody">
                        <tr>
                            <td colspan="10" class="ps-loading-row">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading payslips...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ps-empty-state" id="psEmptyState" style="display:none;">
                <i class="fa-regular fa-file-lines"></i>
                <p>No payslips found</p>
                <p class="ps-empty-sub">Try changing your payroll period or employee filter.</p>
            </div>
        </div>
    </div>
</div>

<!-- View Payslip Modal -->
<div class="ps-modal-overlay" id="psViewModalOverlay" style="display:none;">
    <div class="ps-modal">
        <div class="ps-modal-header">
            <h2>Payslip Details</h2>
            <button type="button" class="ps-modal-close" data-ps-close>&times;</button>
        </div>
        <div class="ps-modal-body" id="psViewModalBody">
            <div class="ps-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading payslip...</div>
        </div>
        <div class="ps-modal-footer">
            <button type="button" class="ps-btn ps-btn-secondary" data-ps-close>Close</button>
            <button type="button" class="ps-btn ps-btn-primary" id="psBtnPrintFromModal" style="display:none;">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<!-- Print-only payslip sheet (rendered only when printing, see @media print) -->
<div id="psPrintSheet"></div>