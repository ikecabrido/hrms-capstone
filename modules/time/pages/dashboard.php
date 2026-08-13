<?php
/**
 * HR Dashboard - Time & Attendance System
 * Main interface for HR to view attendance, generate QR codes, and manage approvals
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/models/Holiday.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/TimeDatabase.php';

use App\Models\Holiday;

Session::start();

// Check if user is authenticated
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$attendanceModel = new Attendance();
$employeeModel = new Employee();
$auditLog = new AuditLog();

// Initialize Holiday model and check if today is a holiday
$database = TimeDatabase::getInstance();
$db = $database->getConnection();
$holidayModel = new Holiday($db);
$isHolidayToday = $holidayModel->isHoliday(date('Y-m-d'));
$todayHolidayInfo = $holidayModel->getHolidayByDate(date('Y-m-d'));

// Initialize EmployeeShift model and check for employees without shifts
$employeeShiftModel = new EmployeeShift($db);
$employeesWithoutShift = $employeeShiftModel->getEmployeesWithoutShift();
$employeesWithoutShiftCount = count($employeesWithoutShift);

// Get statistics
$todayStats = $attendanceModel->getTodaySummary();
$allEmployees = $employeeModel->getTotalCount('ACTIVE');
$todayRecords = $attendanceModel->getTodayAllEmployees(100);
$pendingApprovals = $attendanceModel->getPendingApprovals(10);

// Get all active employees for QR Directory
$activeEmployees = $employeeModel->getAll('Active');

// Calculate today's attendance percentage
$attendancePercentage = 0;
if ($allEmployees > 0 && $todayStats) {
    $attendancePercentage = round(($todayStats['present_count'] / $allEmployees) * 100, 2);
}

$current_page = 'dashboard.php';
$current_role = $_SESSION['user']['role']?? $_SESSION['role']?? 'time';

$page_title = 'HR Dashboard - Time & Attendance System';?>
<?php // require_once __DIR__. '/../layout/page_start.php';?>
<?php require_once __DIR__. '/../includes/sidebar.php';?>
<?php // $page_title = 'Time & Attendance Dashboard'; //$page_subtitle = 'Real-time attendance and HR analytics'; $page_icon = 'fa-chart-line';?>
<?php require_once __DIR__. '/dashboard-overview.php';?>
<?php require_once __DIR__. '/../includes/header.php';?>




    <div class="ta-dashboard absence-late-container glass-panel">
            <!-- Shift Assignment Alert -->
            <?php if ($employeesWithoutShiftCount > 0):?>
            <div style="background: #fff3e0; border-left: 4px solid #FF9800; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: #FF9800;"></i>
                <div style="flex: 1;">
                    <strong style="color: #e65100; font-size: 16px;">Shift Assignment Required</strong>
                    <p style="margin: 8px 0 0 0; color: #5d4037; font-size: 14px;">
                        <strong><?php echo $employeesWithoutShiftCount;?> <?php echo $employeesWithoutShiftCount > 1? 'employees' : 'employee';?></strong> <?php echo $employeesWithoutShiftCount > 1? 'do' : 'does';?> not have an active shift assignment. This may affect attendance tracking and payroll calculations.
                    </p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="shifts.php" style="background: #FF9800; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block;">Manage Shifts</a>
                    </p>
                </div>
            </div>
            <?php endif;?>

            <!-- Holiday Alert -->
            <?php if ($isHolidayToday && $todayHolidayInfo):?>
            <div style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-calendar-check" style="font-size: 28px; color: #2196F3;"></i>
                <div>
                    <strong style="color: #1565c0; font-size: 16px;">Today is a Public Holiday</strong>
                    <p style="margin: 8px 0 0 0; color: #455a64; font-size: 14px;">
                        <strong><?php echo htmlspecialchars($todayHolidayInfo['name']);?></strong> - All employees are marked as HOLIDAY. No absences will be recorded.
                    </p>
                </div>
            </div>
            <?php endif;?>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="info-box-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Employees</span>
                        <span class="info-box-number"><?php echo $allEmployees;?></span>
                        <span class="info-box-unit">Active employees</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="info-box-icon bg-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Present Today</span>
                        <span class="info-box-number"><?php echo $todayStats['present_count']?? 0;?></span>
                        <span class="info-box-unit"><?php echo $attendancePercentage;?>% attendance</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="info-box-icon bg-warning">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Absent Today</span>
                        <span class="info-box-number"><?php echo $todayStats['absent_count']?? 0;?></span>
                        <span class="info-box-unit">Need follow-up</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="info-box-icon bg-info">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Approvals</span>
                        <span class="info-box-number"><?php echo count($pendingApprovals);?></span>
                        <span class="info-box-unit">Manual entries</span>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance Table -->
            <div class="container">
                <h2>Today's Attendance (<?php echo count($todayRecords);?> employees)</h2>

                <div class="filter-controls" style="flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    <input type="text" id="attendanceSearch" placeholder="Search by name, employee #, or department..." style="flex: 1 1 320px;" />
                    <select id="attendanceSort" style="width: 220px;">
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
                            <th style="width: 100px;">QR / ID</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody">
                    </tbody>
                </table>

                <div id="attendancePagination" style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; gap: 10px; flex-wrap: wrap;">
                    <div id="attendancePageInfo" style="font-size: 14px; color: #555;"></div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" id="attendancePrev" class="btn btn-sm btn-secondary">Previous</button>
                        <button type="button" id="attendanceNext" class="btn btn-sm btn-secondary">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee QR moved to Employee QR Directory tab -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
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
<script src="../assets/js/dashboard.js"></script>
