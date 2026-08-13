<?php
/**
 * Employee Dashboard Tab Component
 * Displays employee attendance, leave balance, shift schedule, and metrics
 * Integrated with all time & attendance functions
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Employee.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Leave.php';
require_once __DIR__ . '/../models/EmployeeShift.php';
require_once __DIR__ . '/../models/Holiday.php';
require_once __DIR__ . '/../models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../helpers/Helper.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

try {
    Session::start();

    // Get current employee info - support both Session class and $_SESSION
    $user_id = Session::get('user_id') ?? $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception('No user session found');
    }

    $employeeModel = new Employee();
    $employee = $employeeModel->getByUserId($user_id);

    // Fallback to direct employee_id if available in session
    if (!$employee && isset($_SESSION['user']['employee_id'])) {
        $employee_id = $_SESSION['user']['employee_id'];
    } else {
        $employee_id = $employee['employee_id'] ?? null;
    }

    if (!$employee_id) {
        throw new Exception('Unable to load employee information');
    }

    $attendanceModel = new Attendance();
    $leaveModel = new Leave();
    $holidayModel = new Holiday();
    $absenceModel = new AbsenceLateMgmt();

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get today's status - use null coalescing to handle missing data
    $todayStatus = $attendanceModel->getTodayAttendance($employee_id) ?? [];
    $statusInfo = new AttendanceController();
    $currentStatus = $statusInfo->getStatus($employee_id) ?? 'ABSENT';

    // Get current month data
    $currentMonthStart = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');

    // Get monthly statistics
    $monthlyAttendance = $attendanceModel->getByDateRange($currentMonthStart, $currentMonthEnd, $employee_id, 500) ?? [];
    $presentCount = 0;
    $lateCount = 0;
    $absentCount = 0;

    foreach ($monthlyAttendance as $record) {
        if ($record['status'] === 'PRESENT') {
            $presentCount++;
        } elseif ($record['status'] === 'LATE') {
            $lateCount++;
        } elseif ($record['status'] === 'ABSENT') {
            $absentCount++;
        }
    }
    
    // Default values if no data
    $presentCount = $presentCount ?? 0;
    $lateCount = $lateCount ?? 0;
    $absentCount = $absentCount ?? 0;

    // Get current shift
    $todayShift = null;
    try {
        $stmt = $conn->prepare("SELECT es.*, s.shift_name, s.start_time, s.end_time, s.break_duration
            FROM ta_employee_shifts es
            JOIN ta_shifts s ON es.shift_id = s.shift_id
            WHERE es.employee_id = ? AND es.is_active = 1
            AND (es.effective_to IS NULL OR es.effective_to >= CURDATE())
            LIMIT 1");
        $stmt->execute([$employee_id]);
        $todayShift = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Shift query error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    // Display error message if dashboard fails to load
    echo '<div class="alert alert-warning" style="margin: 20px;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Dashboard Loading Issue</strong><br>
        ' . htmlspecialchars($e->getMessage()) . '<br>
        <small>Check browser console (F12) for more details.</small>
    </div>';
    error_log('Employee Dashboard Error: ' . $e->getMessage());
    return;
}
?>

<div id="employee-dashboard-tab" style="padding: 20px 0;">
    
    <!-- Time In/Out Quick Access Card -->
    <div class="row mb-3">
        <div class="col-lg-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="fas fa-clock"></i> Time In / Time Out
                    </h5>
                    
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 style="color: #999; margin-bottom: 10px;">Time In</h6>
                            <h3 style="color: #28a745; margin: 0;">
                                <?php echo ($todayStatus && $todayStatus['time_in']) ? Helper::formatTime($todayStatus['time_in']) : '--:--'; ?>
                            </h3>
                        </div>
                        <div class="col-md-4">
                            <h6 style="color: #999; margin-bottom: 10px;">Time Out</h6>
                            <h3 style="color: #dc3545; margin: 0;">
                                <?php echo ($todayStatus && $todayStatus['time_out']) ? Helper::formatTime($todayStatus['time_out']) : '--:--'; ?>
                            </h3>
                        </div>
                        <div class="col-md-4">
                            <h6 style="color: #999; margin-bottom: 10px;">Duration</h6>
                            <h3 style="color: #0066cc; margin: 0;">
                                <?php echo ($todayStatus && $todayStatus['time_in'] && $todayStatus['time_out']) ? 
                                    Helper::calculateWorkHours($todayStatus['time_in'], $todayStatus['time_out']) . 'h' : '--'; ?>
                            </h3>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0;">
                    
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <?php if (!$todayStatus || !$todayStatus['time_in']): ?>
                            <form method="POST" action="../app/api/time_in.php" style="display: inline;">
                                <button type="submit" class="btn btn-success btn-lg" style="font-weight: bold; padding: 10px 30px;">
                                    <i class="fas fa-arrow-right"></i> Time In (Manual)
                                </button>
                            </form>
                        <?php elseif (!$todayStatus['time_out']): ?>
                            <form method="POST" action="../app/api/time_out.php" style="display: inline;">
                                <button type="submit" class="btn btn-danger btn-lg" style="font-weight: bold; padding: 10px 30px;">
                                    <i class="fas fa-arrow-left"></i> Time Out (Manual)
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg" disabled style="font-weight: bold; padding: 10px 30px;">
                                <i class="fas fa-check"></i> Time In/Out Complete
                            </button>
                        <?php endif; ?>
                        
                        <a href="javascript:void(0);" onclick="alert('QR Scanner feature - To be implemented')" class="btn btn-info btn-lg" style="font-weight: bold; padding: 10px 30px;">
                            <i class="fas fa-qrcode"></i> Scan QR Code
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Today's Shift Card -->
        <div class="col-lg-4">
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(255,255,255,0.2);">
                    <h5 class="card-title m-0">
                        <i class="fas fa-calendar-alt"></i> Today's Shift
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($todayShift): ?>
                        <h4 style="margin: 0 0 15px 0;"><?php echo htmlspecialchars($todayShift['shift_name']); ?></h4>
                        
                        <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 6px; margin-bottom: 10px;">
                            <small style="opacity: 0.8;">Start Time</small>
                            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold;">
                                <?php echo date('h:i A', strtotime($todayShift['start_time'])); ?>
                            </p>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 6px; margin-bottom: 10px;">
                            <small style="opacity: 0.8;">End Time</small>
                            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold;">
                                <?php echo date('h:i A', strtotime($todayShift['end_time'])); ?>
                            </p>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 6px;">
                            <small style="opacity: 0.8;">Break Duration</small>
                            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold;">
                                <?php echo htmlspecialchars($todayShift['break_duration']); ?> mins
                            </p>
                        </div>
                    <?php else: ?>
                        <p style="margin: 0; opacity: 0.8; font-style: italic;">📋 No shift assigned for today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Present This Month</span>
                    <span class="info-box-number"><?php echo $presentCount; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-exclamation"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Late Arrivals</span>
                    <span class="info-box-number"><?php echo $lateCount; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Absences</span>
                    <span class="info-box-number"><?php echo $absentCount; ?></span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Holidays (30d)</span>
                    <span class="info-box-number"><?php echo count($upcomingHolidays); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balance Section -->
    <div class="row mb-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 class="card-title m-0">
                            <i class="fas fa-leaf"></i> Leave Balance
                        </h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openLeaveRequestModal()">
                            <i class="fas fa-plus"></i> Request Leave
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($leaveBalances)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Leave Type</th>
                                        <th class="text-center">Balance</th>
                                        <th class="text-center">Used</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaveTypes as $type): ?>
                                        <?php 
                                        $balance = $leaveModel->checkLeaveBalance($employee_id, $type['leave_type_id'], 0);
                                        $totalBalance = $balance['balance'] ?? 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($type['leave_type_name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-success"><?php echo $totalBalance; ?> days</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-danger">--</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> No leave balance information available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Holidays Section -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="fas fa-calendar-check"></i> Upcoming Holidays (Next 30 Days)
                    </h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (!empty($upcomingHolidays)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcomingHolidays as $holiday): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($holiday['holiday_name']); ?>
                                            <span class="badge badge-<?php echo ($holiday['category'] === 'NATIONAL') ? 'danger' : 'warning'; ?>">
                                                <?php echo ucfirst($holiday['category']); ?>
                                            </span>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('F j, Y', strtotime($holiday['holiday_date'])); ?>
                                        </small>
                                    </div>
                                    <?php 
                                    $daysUntil = (strtotime($holiday['holiday_date']) - time()) / 86400;
                                    ?>
                                    <span class="badge badge-info"><?php echo ceil($daysUntil); ?>d</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> No holidays in the next 30 days
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Requests History -->
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="fas fa-file-alt"></i> My Leave Requests (Recent)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentLeaveRequests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLeaveRequests as $req): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($req['leave_type_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($req['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($req['end_date'])); ?></td>
                                            <td>
                                                <?php 
                                                $start = new DateTime($req['start_date']);
                                                $end = new DateTime($req['end_date']);
                                                $end->modify('+1 day');
                                                $days = $start->diff($end)->days;
                                                echo $days;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo ($req['status'] === 'PENDING') ? 'warning' :
                                                         (($req['status'] === 'APPROVED_BY_HR' || $req['status'] === 'APPROVED_BY_HEAD') ? 'success' : 
                                                         (strpos($req['status'], 'REJECT') !== false ? 'danger' : 'secondary'));
                                                ?>">
                                                    <?php echo str_replace('_', ' ', $req['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> No leave requests yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Absence & Late Records -->
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="fas fa-exclamation-triangle"></i> Absence & Late Records (This Month)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($absenceRecords)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Details</th>
                                        <th>Excused</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($absenceRecords as $record): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo ($record['type'] === 'LATE') ? 'warning' : 'danger'; ?>">
                                                    <?php echo $record['type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($record['type'] === 'LATE') {
                                                    echo $record['late_minutes'] ?? '-- ';
                                                    echo ' minutes late';
                                                } else {
                                                    echo htmlspecialchars($record['reason'] ?? 'No reason provided');
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-<?php echo $record['is_excused'] ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo ($record['status'] === 'PENDING') ? 'warning' :
                                                         (($record['status'] === 'APPROVED') ? 'success' : 'secondary');
                                                ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> No absences or late records this month
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Calendar Widget -->
    <div class="row mb-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">
                        <i class="fas fa-calendar-days"></i> Your Schedule (This Month)
                    </h5>
                </div>
                <div class="card-body">
                    <p style="color: #999; font-size: 13px; margin: 0; text-align: center;">
                        <i class="fas fa-info-circle"></i> 
                        For detailed schedule calendar with daily timeline, please visit the <strong>"Schedule Calendar"</strong> tab
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Leave Request Modal -->
<div id="leaveRequestModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-leaf"></i> Request Leave
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="leaveRequestForm" method="POST" action="../app/api/submit_leave.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="leaveType">Leave Type <span style="color: red;">*</span></label>
                        <select class="form-control" id="leaveType" name="leave_type_id" required>
                            <option value="">-- Select Leave Type --</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['leave_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['leave_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="startDate">Start Date <span style="color: red;">*</span></label>
                            <input type="date" class="form-control" id="startDate" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="endDate">End Date <span style="color: red;">*</span></label>
                            <input type="date" class="form-control" id="endDate" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Leave <span style="color: red;">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Please explain why you need this leave..." required></textarea>
                    </div>
                    
                    <div id="leaveMessage" style="display: none;" class="alert" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitLeaveBtn">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/employee-dashboard-tab.js"></script>
