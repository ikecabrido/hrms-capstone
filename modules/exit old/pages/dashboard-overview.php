<?php
require_once __DIR__ . '/../classes/Employee.php';

$employee = new Employee();
$currentEmployeeName = method_exists($employee, 'getEmployeeName')
    ? $employee->getEmployeeName()
    : 'HR Staff';
$currentRoleName = $_SESSION['role_name'] ?? 'Exit';
?>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="assets/css/custom.css">
<script>
    window.exitManagementUserRole = <?php echo json_encode($currentRoleName); ?>;
    window.exitManagementUserId = <?php echo json_encode($_SESSION['employee_id'] ?? null); ?>;
</script>

    <div class="module-header">
        <h1>Exit Management</h1>
        <p style="margin:4px 0 0;color:#666;font-size:14px;">Welcome, <?php echo htmlspecialchars($currentEmployeeName); ?></p>
    </div>

    <div class="module-content">
        <div id="dashboard-section" class="section">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="active-exits">0</h3>
                            <p>Active Exit Cases</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="pending-approval">0</h3>
                            <p>Pending Approval</p>
                        </div>
                        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="approved-preclearances">0</h3>
                            <p>Approved Preclearances</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="upcoming-exits">0</h3>
                            <p>Upcoming Exits (14d)</p>
                        </div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Exit Status Distribution</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="exitStatusChart" style="min-height:250px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Resignation Trend</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="resignationTrendChart" style="min-height:250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Upcoming Exits</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped" id="upcomingExitsTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Last Working Date</th>
                                        <th>Days Left</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Action Required</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" id="actionRequiredList"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped" id="recentActiveCasesTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="customToastContainer" style="position: fixed; top: 1rem; right: 1rem; z-index: 11000; display: flex; flex-direction: column; gap: .75rem;"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="assets/js/custom.js"></script>
