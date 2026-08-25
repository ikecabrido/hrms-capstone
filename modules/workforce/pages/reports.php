<div class="module-header">
    <div class="rp-header-row">
        <div>
            <h1>Payroll Reports</h1>
            <p class="rp-subtitle">Generate, review, and print payroll reports for payroll periods.</p>
        </div>
    </div>
</div>

<div class="module-content">
    <div class="pm-page" id="reportsPage" data-page="reports">

        <div class="pm-alert" id="rpAlert" role="alert" style="display:none;"></div>

        <!-- Report generator -->
        <div class="rp-generator-card">
            <div class="rp-generator-fields">

                <div class="rp-filter-group">
                    <label for="rpReportType">Report Type</label>
                    <select id="rpReportType">
                        <option value="payroll_register">Payroll Register</option>
                        <option value="payroll_summary">Payroll Summary</option>
                        <option value="item_summary">Earnings &amp; Deductions Breakdown</option>
                        <option value="department_summary">Department Summary</option>
                        <option value="employee_history">Employee Payroll History</option>
                    </select>
                </div>

                <div class="rp-filter-group" id="rpPeriodFieldGroup">
                    <label for="rpPeriodSearchInput">Payroll Period</label>
                    <div class="ps-combobox" id="rpPeriodCombobox">
                        <div class="ps-combobox-input-wrap">
                            <i class="fa-solid fa-magnifying-glass ps-combobox-icon"></i>
                            <input
                                type="text"
                                id="rpPeriodSearchInput"
                                class="ps-combobox-input"
                                placeholder="Search payroll period..."
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list">
                            <button type="button" class="ps-combobox-clear" id="rpPeriodClearBtn" title="Clear" aria-label="Clear payroll period">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <input type="hidden" id="rpPeriodFilter" value="">
                        <div class="ps-combobox-options" id="rpPeriodOptions" role="listbox"></div>
                    </div>
                </div>

                <div class="rp-filter-group" id="rpEmployeeFieldGroup" style="display:none;">
                    <label for="rpEmployeeSearchInput">Employee</label>
                    <div class="ps-combobox" id="rpEmployeeCombobox">
                        <div class="ps-combobox-input-wrap">
                            <i class="fa-solid fa-magnifying-glass ps-combobox-icon"></i>
                            <input
                                type="text"
                                id="rpEmployeeSearchInput"
                                class="ps-combobox-input"
                                placeholder="Search employee code or name..."
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list">
                            <button type="button" class="ps-combobox-clear" id="rpEmployeeClearBtn" title="Clear" aria-label="Clear employee">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </div>
                        <input type="hidden" id="rpEmployeeFilter" value="">
                        <div class="ps-combobox-options" id="rpEmployeeOptions" role="listbox"></div>
                    </div>
                </div>
            </div>

            <div class="rp-generator-actions">
                <button type="button" class="pm-btn pm-btn-secondary" id="rpBtnReset">
                    <i class="fa-solid fa-rotate-left"></i> Clear
                </button>
                <button type="button" class="pm-btn pm-btn-primary" id="rpBtnGenerate">
                    <i class="fa-solid fa-file-invoice"></i> Generate Report
                </button>
                <button type="button" class="pm-btn pm-btn-secondary" id="rpBtnPrint" disabled>
                    <i class="fa-solid fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Empty state (before a report is generated) -->
        <div class="pm-empty-state" id="rpEmptyState">
            <i class="fa-regular fa-file-lines"></i>
            <p>No report generated</p>
            <span class="rp-empty-subtext">Select a payroll period and report type, then click Generate Report.</span>
        </div>

        <!-- Report preview -->
        <div class="rp-preview-card" id="rpPreviewCard" style="display:none;">

            <div class="rp-preview-header">
                <div class="rp-preview-titles">
                    <h2 id="rpReportTitle">Payroll Report</h2>
                    <div class="rp-preview-meta">
                        <span id="rpReportPeriod">&mdash;</span>
                        <span id="rpReportGenerated">&mdash;</span>
                    </div>
                </div>
            </div>

            <div id="rpReportBody"></div>
        </div>

    </div>
</div>

<!-- Dedicated print sheet — hidden on screen, shown only via @media print -->
<div id="rpPrintSheet"></div>