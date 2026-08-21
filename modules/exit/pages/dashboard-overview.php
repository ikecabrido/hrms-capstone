<?php
require_once __DIR__ . '/../classes/Employee.php';

$employee = new Employee();
$currentEmployeeName = method_exists($employee, 'getEmployeeName')
    ? $employee->getEmployeeName()
    : 'HR Staff';
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>


<link rel="stylesheet" href="assets/css/custom.css">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header" style="display:none;">
        <h1>Exit Management</h1>
        <p style="margin:4px 0 0;color:#666;font-size:14px;">Welcome, <?php echo htmlspecialchars($currentEmployeeName); ?></p>
    </div>

    <div class="module-content">
        <div id="dashboard-section" class="exit-dashboard">
            <h2 class="dashboard-title">Dashboard</h2>

            <div class="dashboard-kpi-grid">
                <div class="dashboard-kpi-card dashboard-kpi-blue">
                    <div class="dashboard-kpi-icon"><i class="fas fa-arrow-right"></i></div>
                    <div class="dashboard-kpi-value" id="active-exits">0</div>
                    <div class="dashboard-kpi-label">Total exited (this year)</div>
                </div>
                <div class="dashboard-kpi-card dashboard-kpi-blue">
                    <div class="dashboard-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="dashboard-kpi-value" id="pending-approval">0</div>
                    <div class="dashboard-kpi-label">Avg notice period (days)</div>
                </div>
                <div class="dashboard-kpi-card dashboard-kpi-blue">
                    <div class="dashboard-kpi-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="dashboard-kpi-value" id="approved-preclearances">0</div>
                    <div class="dashboard-kpi-label">Top resignation reason</div>
                </div>
                <div class="dashboard-kpi-card dashboard-kpi-green">
                    <div class="dashboard-kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="dashboard-kpi-value" id="upcoming-exits">0</div>
                    <div class="dashboard-kpi-label">Settlements pending</div>
                </div>
            </div>

            <div class="dashboard-panel chart-panel">
                <div class="dashboard-panel-header">Exit Process Pipeline</div>
                <div class="dashboard-panel-body chart-container">
                    <canvas id="exitPipelineChart" height="260"></canvas>
                </div>
            </div>

            <div class="dashboard-lower-grid">
                <div class="dashboard-panel mini-panel">
                    <div class="dashboard-panel-header">Upcoming Last Working Dates</div>
                    <div class="dashboard-panel-body">
                        <ul id="upcoming-exits-list" class="dashboard-list">
                            <li class="empty-state">No upcoming exits found</li>
                        </ul>
                    </div>
                </div>

                <div class="dashboard-panel mini-panel">
                    <div class="dashboard-panel-header">
                        <span>Action Required</span>
                        <button type="button" class="panel-refresh-btn" onclick="loadActionRequiredList();">Refresh</button>
                    </div>
                    <div class="dashboard-panel-body">
                        <div id="action-required-list" class="dashboard-action-list">
                            <div class="empty-state">No actions required</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-panel full-panel">
                <div class="dashboard-panel-header">Recent / Active Exit Cases</div>
                <div class="dashboard-panel-body">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Last Day</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="empty-row">No recent cases</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/chartjs/chart.umd.js"></script>
<script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
<script src="assets/js/custom.js"></script>
