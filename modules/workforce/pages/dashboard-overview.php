<div class="module-header">
    <div class="dash-header-row">
        <div>
            <h1>Payroll Dashboard</h1>
            <p class="dash-subtitle">Overview of payroll processing, expenses, deductions, and employee payroll activity.</p>
        </div>
        <div class="dash-header-actions">
            <div class="dash-period-pill" id="dashHeaderPeriod">
                <i class="fa-solid fa-calendar-days"></i>
                <span id="dashHeaderPeriodName">Loading period&hellip;</span>
                <span class="pm-badge" id="dashHeaderStatusBadge" style="display:none;"></span>
            </div>
            <a href="?page=payroll-processing" data-page="payroll-processing" class="pm-btn pm-btn-primary">
                <i class="fa-solid fa-money-check-dollar"></i> Process Payroll
            </a>
        </div>
    </div>
</div>

<div class="module-content">
    <div class="dash-page" id="dashboardOverviewPage" data-page="dashboard-overview">

        <div class="pm-alert" id="dashAlert" role="alert" style="display:none;"></div>

        <!-- ============================================================
             SUMMARY CARDS
             ============================================================ -->
        <div class="dash-summary-cards" id="dashSummaryCards">

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-employees"><i class="fa-solid fa-users"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashActiveEmployees" data-count-up="0">&mdash;</span>
                    <span class="dash-card-label">Active Employees</span>
                </div>
            </div>

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-gross"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashGrossPayroll">&mdash;</span>
                    <span class="dash-card-label">Gross Payroll</span>
                    <span class="dash-card-sub" id="dashGrossPayrollSub">Latest finalized payroll</span>
                </div>
            </div>

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-deductions"><i class="fa-solid fa-scale-balanced"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashTotalDeductions">&mdash;</span>
                    <span class="dash-card-label">Total Deductions</span>
                    <span class="dash-card-sub" id="dashTotalDeductionsSub">Latest finalized payroll</span>
                </div>
            </div>

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-net"><i class="fa-solid fa-wallet"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashNetPayroll">&mdash;</span>
                    <span class="dash-card-label">Net Payroll</span>
                    <span class="dash-card-sub" id="dashNetPayrollSub">Latest finalized payroll</span>
                </div>
            </div>

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-average"><i class="fa-solid fa-chart-simple"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashAverageNetPay">&mdash;</span>
                    <span class="dash-card-label">Average Net Pay</span>
                    <span class="dash-card-sub">Per employee, latest payroll</span>
                </div>
            </div>

            <div class="dash-card dash-card-anim">
                <div class="dash-card-icon dash-icon-lifetime"><i class="fa-solid fa-coins"></i></div>
                <div class="dash-card-text">
                    <span class="dash-card-value" id="dashLifetimePayroll">&mdash;</span>
                    <span class="dash-card-label">Lifetime Net Payroll</span>
                    <span class="dash-card-sub">All finalized payroll runs</span>
                </div>
            </div>

        </div>

        <!-- ============================================================
             PAYROLL STATUS / PROCESSING OVERVIEW
             ============================================================ -->
        <div class="dash-status-grid">

            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2><i class="fa-solid fa-calendar-check"></i> Current Payroll Period</h2>
                </div>
                <div class="dash-panel-body" id="dashPeriodPanelBody">
                    <div class="dash-loading-inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading period information&hellip;</div>
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2><i class="fa-solid fa-list-check"></i> Payroll Run Progress</h2>
                </div>
                <div class="dash-panel-body" id="dashProgressPanelBody">
                    <div class="dash-loading-inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading processing progress&hellip;</div>
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2><i class="fa-solid fa-layer-group"></i> Payroll Runs</h2>
                </div>
                <div class="dash-panel-body" id="dashRunCountersPanelBody">
                    <div class="dash-loading-inline"><i class="fa-solid fa-spinner fa-spin"></i> Loading run totals&hellip;</div>
                </div>
            </div>

        </div>

        <!-- ============================================================
             GRAPH: PAYROLL EXPENSE TREND
             ============================================================ -->
        <div class="dash-chart-card dash-chart-card-wide">
            <div class="dash-chart-header">
                <h2><i class="fa-solid fa-chart-line"></i> Payroll Expense Trend</h2>
                <span class="dash-chart-sub">Gross pay, deductions, and net pay across the last finalized payroll runs</span>
            </div>
            <div class="dash-chart-body">
                <canvas id="dashTrendChart" height="110"></canvas>
                <div class="dash-empty-state" id="dashTrendEmpty" style="display:none;">
                    <i class="fa-regular fa-chart-bar"></i>
                    <p>No payroll data available for this period.</p>
                </div>
            </div>
        </div>

        <!-- ============================================================
             GRAPH: GROSS VS NET  +  DEDUCTION BREAKDOWN
             ============================================================ -->
        <div class="dash-chart-grid">

            <div class="dash-chart-card">
                <div class="dash-chart-header">
                    <h2><i class="fa-solid fa-scale-unbalanced"></i> Gross vs Net Payroll</h2>
                    <span class="dash-chart-sub">Latest finalized payroll run</span>
                </div>
                <div class="dash-chart-body">
                    <canvas id="dashCompositionChart" height="220"></canvas>
                    <div class="dash-empty-state" id="dashCompositionEmpty" style="display:none;">
                        <i class="fa-regular fa-chart-bar"></i>
                        <p>No payroll data available for this period.</p>
                    </div>
                </div>
            </div>

            <div class="dash-chart-card">
                <div class="dash-chart-header">
                    <h2><i class="fa-solid fa-chart-pie"></i> Deduction Breakdown</h2>
                    <span class="dash-chart-sub">Latest finalized payroll run</span>
                </div>
                <div class="dash-chart-body">
                    <canvas id="dashDeductionChart" height="220"></canvas>
                    <div class="dash-empty-state" id="dashDeductionEmpty" style="display:none;">
                        <i class="fa-regular fa-chart-bar"></i>
                        <p>No payroll data available for this period.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================
             GRAPH: PAYROLL BY DEPARTMENT
             ============================================================ -->
        <div class="dash-chart-card dash-chart-card-wide">
            <div class="dash-chart-header">
                <h2><i class="fa-solid fa-building"></i> Payroll Cost by Department</h2>
                <span class="dash-chart-sub">Net pay distribution across departments &mdash; latest finalized payroll run</span>
            </div>
            <div class="dash-chart-body">
                <canvas id="dashDepartmentChart" height="100"></canvas>
                <div class="dash-empty-state" id="dashDepartmentEmpty" style="display:none;">
                    <i class="fa-regular fa-chart-bar"></i>
                    <p>No payroll data available for this period.</p>
                </div>
            </div>
        </div>

        <!-- ============================================================
             GRAPH: EMPLOYEE DISTRIBUTION
             ============================================================ -->
        <div class="dash-chart-grid">

            <div class="dash-chart-card">
                <div class="dash-chart-header">
                    <h2><i class="fa-solid fa-sitemap"></i> Active Employees by Department</h2>
                </div>
                <div class="dash-chart-body">
                    <canvas id="dashEmployeeDeptChart" height="220"></canvas>
                    <div class="dash-empty-state" id="dashEmployeeDeptEmpty" style="display:none;">
                        <i class="fa-regular fa-chart-bar"></i>
                        <p>No employee data available.</p>
                    </div>
                </div>
            </div>

            <div class="dash-chart-card">
                <div class="dash-chart-header">
                    <h2><i class="fa-solid fa-id-badge"></i> Employees by Employment Type</h2>
                </div>
                <div class="dash-chart-body">
                    <canvas id="dashEmployeeTypeChart" height="220"></canvas>
                    <div class="dash-empty-state" id="dashEmployeeTypeEmpty" style="display:none;">
                        <i class="fa-regular fa-chart-bar"></i>
                        <p>No employee data available.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================
             RECENT PAYROLL ACTIVITY
             ============================================================ -->
        <div class="pm-table-card dash-recent-card">
            <div class="dash-chart-header dash-recent-header">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent Payroll Activity</h2>
                <a href="?page=reports" data-page="reports" class="dash-view-all-link">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="pm-table-wrapper">
                <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Payroll Period</th>
                            <th>Employees</th>
                            <th>Gross Payroll</th>
                            <th>Deductions</th>
                            <th>Net Payroll</th>
                            <th>Status</th>
                            <th>Pay Date</th>
                        </tr>
                    </thead>
                    <tbody id="dashRecentRunsBody">
                        <tr>
                            <td colspan="7" class="pm-loading-row">
                                <i class="fa-solid fa-spinner fa-spin"></i> Loading recent payroll activity&hellip;
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="pm-empty-state" id="dashRecentRunsEmpty" style="display:none;">
                <i class="fa-regular fa-folder-open"></i>
                <p>No finalized payroll runs yet.</p>
            </div>
        </div>

    </div>
</div>