<?php
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/models/Holiday.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
require_once __DIR__ . '/../app/core/TimeDatabase.php';

use App\Models\Holiday;

$attendanceModel = new Attendance();
$employeeModel = new Employee();
$auditLog = new AuditLog();

$database = TimeDatabase::getInstance();
$db = $database->getConnection();

$holidayModel = new Holiday($db);
$isHolidayToday = $holidayModel->isHoliday(date('Y-m-d'));
$todayHolidayInfo = $holidayModel->getHolidayByDate(date('Y-m-d'));

$employeeShiftModel = new EmployeeShift($db);
$employeesWithoutShift = $employeeShiftModel->getEmployeesWithoutShift();
$employeesWithoutShiftCount = count($employeesWithoutShift);

$todayStats = $attendanceModel->getTodaySummary();
$allEmployees = $employeeModel->getTotalCount('ACTIVE');
$todayRecords = $attendanceModel->getTodayAllEmployees(100);
$pendingApprovals = $attendanceModel->getPendingApprovals(10);
$activeEmployees = $employeeModel->getAll('Active');

$attendancePercentage = 0;
if ($allEmployees > 0 && $todayStats) {
    $attendancePercentage = round(($todayStats['present_count'] / $allEmployees) * 100, 2);
}

$current_role = $_SESSION['role'] ?? 'time';
?>
<link rel="stylesheet" href="css/pages/dashboard.css">

<div class="module-header">
    <h1>Time and Attendance Dashboard</h1>
</div>

<div class="ta-dashboard">
    <?php if ($employeesWithoutShiftCount > 0): ?>
    <div class="alert-banner alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Shift Assignment Required</strong>
            <p><strong><?php echo $employeesWithoutShiftCount; ?> <?php echo $employeesWithoutShiftCount > 1 ? 'employees' : 'employee'; ?></strong>
                <?php echo $employeesWithoutShiftCount > 1 ? 'do' : 'does'; ?> not have an active shift assignment.
                This may affect attendance tracking and payroll calculations.</p>
            <a href="?page=shifts" class="btn-alert">Manage Shifts</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isHolidayToday && $todayHolidayInfo): ?>
    <div class="alert-banner alert-info">
        <i class="fas fa-calendar-check"></i>
        <div>
            <strong>Today is a Public Holiday</strong>
            <p><strong><?php echo htmlspecialchars($todayHolidayInfo['name']); ?></strong> - All employees are marked as HOLIDAY. No absences will be recorded.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-primary"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <span class="stat-label">Total Employees</span>
                <span class="stat-number"><?php echo $allEmployees; ?></span>
                <span class="stat-unit">Active employees</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-success"><i class="fas fa-user-check"></i></div>
            <div class="stat-content">
                <span class="stat-label">Present Today</span>
                <span class="stat-number"><?php echo $todayStats['present_count'] ?? 0; ?></span>
                <span class="stat-unit"><?php echo $attendancePercentage; ?>% attendance</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-warning"><i class="fas fa-user-times"></i></div>
            <div class="stat-content">
                <span class="stat-label">Absent Today</span>
                <span class="stat-number"><?php echo $todayStats['absent_count'] ?? 0; ?></span>
                <span class="stat-unit">Need follow-up</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-info"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-content">
                <span class="stat-label">Pending Approvals</span>
                <span class="stat-number"><?php echo count($pendingApprovals); ?></span>
                <span class="stat-unit">Manual entries</span>
            </div>
        </div>
    </div>

    <div class="attendance-panel">
        <h2>Today's Attendance (<?php echo count($todayRecords); ?> employees)</h2>

        <div class="filter-controls">
            <input type="text" id="attendanceSearch" placeholder="Search by name, employee #, or department...">
            <select id="attendanceSort">
                <option value="name">Sort: Name (A-Z)</option>
                <option value="name-desc">Sort: Name (Z-A)</option>
                <option value="time">Sort: Time In (Latest)</option>
                <option value="time-asc">Sort: Time In (Earliest)</option>
                <option value="department">Sort: Department</option>
                <option value="status">Sort: Status</option>
            </select>
        </div>

        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>QR / ID</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="attendanceBody"></tbody>
        </table>

        <div id="attendancePagination">
            <div id="attendancePageInfo"></div>
            <div>
                <button type="button" id="attendancePrev" class="btn btn-sm btn-secondary">Previous</button>
                <button type="button" id="attendanceNext" class="btn btn-sm btn-secondary">Next</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    window.__TA_CONFIG = {
        isHolidayToday: <?php echo json_encode($isHolidayToday); ?>,
        holidayInfo: <?php echo json_encode($todayHolidayInfo); ?>,
        attendanceData: <?php echo json_encode($todayRecords); ?>,
        employees: <?php echo json_encode($activeEmployees); ?>
    };
</script>
<script src="assets/js/dashboard.js"></script>