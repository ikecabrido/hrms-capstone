<div class="module-header">
    <h1>Payroll Processing</h1>
</div>

<div class="module-content">
    <div class="pp-page" id="payrollProcessingPage" data-page="payroll-processing">

        <p class="pp-page-subtitle">Process and review employee payroll for the selected payroll period.</p>

        <div class="pm-alert" id="ppAlert" role="alert" style="display:none;"></div>

        <!-- STEP 1: Period selector -->
        <div class="pp-card">
            <div class="pp-card-header">
                <h2><i class="fa-solid fa-calendar-days"></i> Payroll Period</h2>
            </div>
            <div class="pp-card-body">
                <div class="pp-period-select-row">
                    <select id="ppPeriodSelect">
                        <option value="">Select a payroll period...</option>
                    </select>
                    <button type="button" class="pm-btn pm-btn-primary" id="ppBtnCalculate" disabled>
                        <i class="fa-solid fa-calculator"></i> Calculate Payroll
                    </button>
                </div>

                <div class="pp-period-info" id="ppPeriodInfo" style="display:none;">
                    <div class="pp-period-info-item">
                        <span>Period</span>
                        <strong id="ppInfoName">&mdash;</strong>
                    </div>
                    <div class="pp-period-info-item">
                        <span>Start Date</span>
                        <strong id="ppInfoStart">&mdash;</strong>
                    </div>
                    <div class="pp-period-info-item">
                        <span>End Date</span>
                        <strong id="ppInfoEnd">&mdash;</strong>
                    </div>
                    <div class="pp-period-info-item">
                        <span>Pay Date</span>
                        <strong id="ppInfoPay">&mdash;</strong>
                    </div>
                    <div class="pp-period-info-item">
                        <span>Status</span>
                        <strong><span class="pm-badge" id="ppInfoStatus">&mdash;</span></strong>
                    </div>
                </div>

                <div class="pp-closed-notice" id="ppClosedNotice" style="display:none;">
                    <i class="fa-solid fa-lock"></i>
                    This payroll period is closed. Processing and finalization are disabled, and it can no longer be recalculated.
                </div>
            </div>
        </div>

        <!-- STEP 3: Processing state -->
        <div class="pp-processing-state" id="ppProcessingState" style="display:none;">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <h3>Calculating Payroll&hellip;</h3>
            <p>Please wait while attendance, schedules, salary information, government contributions, tax, and deductions are calculated.</p>
        </div>

        <!-- STEP 4: Processing summary -->
        <div class="pp-summary-cards" id="ppSummaryCards" style="display:none;">
            <div class="pp-summary-card">
                <span class="pp-summary-label">Employees Processed</span>
                <span class="pp-summary-value" id="ppSumProcessed">0</span>
            </div>
            <div class="pp-summary-card">
                <span class="pp-summary-label">With Earnings</span>
                <span class="pp-summary-value" id="ppSumEarnings">0</span>
            </div>
            <div class="pp-summary-card">
                <span class="pp-summary-label">With Deductions</span>
                <span class="pp-summary-value" id="ppSumDeductions">0</span>
            </div>
            <div class="pp-summary-card pp-summary-card-money">
                <span class="pp-summary-label">Total Gross Pay</span>
                <span class="pp-summary-value" id="ppSumGross">&#8369;0.00</span>
            </div>
            <div class="pp-summary-card pp-summary-card-money">
                <span class="pp-summary-label">Total Deductions</span>
                <span class="pp-summary-value" id="ppSumTotalDeductions">&#8369;0.00</span>
            </div>
            <div class="pp-summary-card pp-summary-card-money pp-summary-card-net">
                <span class="pp-summary-label">Total Net Pay</span>
                <span class="pp-summary-value" id="ppSumNet">&#8369;0.00</span>
            </div>
        </div>

        <!-- STEP 5: Employee selection -->
        <div class="pp-card" id="ppEmployeeSection" style="display:none;">
            <div class="pp-card-header">
                <h2><i class="fa-solid fa-users"></i> Employee Payroll Review</h2>
                <div class="pp-employee-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="ppEmployeeSearch" placeholder="Search by name or employee no...">
                </div>
            </div>
            <div class="pp-card-body pp-employee-list-body">
                <div class="pp-employee-list" id="ppEmployeeList"></div>
            </div>
        </div>

        <!-- Payroll breakdown -->
        <div class="pp-card" id="ppBreakdownSection" style="display:none;">
            <div class="pp-card-header">
                <h2><i class="fa-solid fa-file-invoice-dollar"></i> Payroll Breakdown</h2>
            </div>
            <div class="pp-card-body" id="ppBreakdownBody">
                <!-- Rendered dynamically by payroll-processing.js -->
            </div>
        </div>

        <!-- STEP: Finalization -->
        <div class="pp-finalize-bar" id="ppFinalizeBar" style="display:none;">
            <div class="pp-finalize-text">
                <strong>Ready to finalize?</strong>
                <span>This will generate payslips for every calculated employee and close the payroll period.</span>
            </div>
            <button type="button" class="pm-btn pm-btn-primary" id="ppBtnFinalize">
                <i class="fa-solid fa-flag-checkered"></i> Finalize Payroll
            </button>
        </div>

        <!-- Post-finalization success panel -->
        <div class="pp-success-panel" id="ppSuccessPanel" style="display:none;">
            <i class="fa-solid fa-circle-check"></i>
            <h3>Payroll Successfully Finalized</h3>
            <div class="pp-success-grid">
                <div>
                    <span>Payroll Run</span>
                    <strong id="ppSuccessRunId">&mdash;</strong>
                </div>
                <div>
                    <span>Payslips Generated</span>
                    <strong id="ppSuccessCount">&mdash;</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong><span class="pm-badge pm-badge-closed">FINALIZED</span></strong>
                </div>
            </div>
            <a href="?page=payslips" data-page="payslips" class="pm-btn pm-btn-primary">
                <i class="fa-solid fa-arrow-right"></i> View Payslips
            </a>
        </div>
    </div>
</div>

<!-- Finalize confirmation modal -->
<div class="pm-modal-overlay" id="ppFinalizeModalOverlay" style="display:none;">
    <div class="pm-modal">
        <div class="pm-modal-header">
            <h2>Finalize Payroll</h2>
            <button type="button" class="pm-modal-close" data-pp-close>&times;</button>
        </div>
        <div class="pm-modal-body">
            <p>Are you sure you want to finalize this payroll period?</p>
            <p class="pp-finalize-warning">
                Once finalized, payslips will be generated and the payroll period will be closed.
                The payroll can no longer be recalculated.
            </p>
            <div class="pm-form-error" id="ppFinalizeError" style="display:none;"></div>
        </div>
        <div class="pm-modal-footer">
            <button type="button" class="pm-btn pm-btn-secondary" data-pp-close>Cancel</button>
            <button type="button" class="pm-btn pm-btn-primary" id="ppBtnConfirmFinalize">Confirm Finalization</button>
        </div>
    </div>
</div>