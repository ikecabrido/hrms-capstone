<?php
/**
 * Export Dashboard Data to Excel with Professional Template
 */

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/core/Session.php';

// Include metrics calculator with namespace handling
if (!class_exists('MetricsCalculator', false)) {
    require_once __DIR__ . '/../app/helpers/MetricsCalculator.php';
}

Session::start();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$user_id = AuthController::getCurrentUserId();
$employeeModel = new Employee();
$employee = $employeeModel->getByUserId($user_id);
$employee_id = $employee['employee_id'];

// Check if this is a preview request
$is_preview = isset($_GET['format']) && $_GET['format'] === 'preview';

// Debug log
error_log('Export requested - format: ' . ($_GET['format'] ?? 'none') . ', is_preview: ' . ($is_preview ? 'true' : 'false'));

// Get data
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$db = Database::getInstance();
$conn = $db->getConnection();

// Get monthly attendance
$query = "SELECT * FROM ta_attendance 
          WHERE employee_id = ? AND DATE(time_in) BETWEEN ? AND ?
          ORDER BY time_in DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$employee_id, $current_month_start, $current_month_end]);
$monthly_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get leave requests
$query_leave = "SELECT lr.*, lt.leave_type_name 
                FROM ta_leave_requests lr
                JOIN ta_leave_types lt ON lr.leave_type_id = lt.leave_type_id
                WHERE lr.employee_id = ? AND YEAR(start_date) = ?
                ORDER BY start_date DESC";
$stmt_leave = $conn->prepare($query_leave);
$stmt_leave->execute([$employee_id, date('Y')]);
$leave_requests = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);

// Get leave balance
$query_balance = "SELECT lb.*, lt.leave_type_name 
                  FROM ta_leave_balances lb
                  JOIN ta_leave_types lt ON lb.leave_type_id = lt.leave_type_id
                  WHERE lb.employee_id = ? AND lb.year = ?";
$stmt_balance = $conn->prepare($query_balance);
$stmt_balance->execute([$employee_id, date('Y')]);
$leave_balances = $stmt_balance->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$present_count = 0;
$late_count = 0;
$total_hours = 0;
$total_overtime = 0;

foreach ($monthly_attendance as $record) {
    $total_hours += $record['total_hours_worked'] ?? 0;
    $total_overtime += $record['overtime_hours'] ?? 0;
    
    if ($record['status'] === 'ON_TIME' || $record['status'] === 'EARLY') {
        $present_count++;
    } elseif ($record['status'] === 'LATE') {
        $late_count++;
    }
}

// Prepare employee info
$emp_name = htmlspecialchars($employee['full_name']);
$emp_id = htmlspecialchars($employee_id);
$emp_dept = htmlspecialchars($employee['department'] ?? 'N/A');
$emp_pos = htmlspecialchars($employee['position'] ?? 'N/A');
$month_year = date('F Y');
$current_date = date('F d, Y H:i:s');

// Calculate metrics
$calculator = new MetricsCalculator($conn);
$monthYear = date('Y-m');
$metricsResult = $calculator->calculateAttendanceMetrics($employee_id, $monthYear);
$punctualityResult = $calculator->calculatePunctualityScore($employee_id, $monthYear);
$overtimeResult = $calculator->calculateOvertimeFrequency($employee_id, $monthYear);

$metrics = $metricsResult['success'] ? $metricsResult : [];
$punctuality = $punctualityResult['success'] ? $punctualityResult : [];
$overtime = $overtimeResult['success'] ? $overtimeResult : [];

// If preview mode, return JSON data
if ($is_preview) {
    error_log('Preview mode - returning JSON');
    
    $preview_data = [
        'employee_name' => $emp_name,
        'employee_id' => $emp_id,
        'department' => $emp_dept,
        'position' => $emp_pos,
        'month_year' => $month_year,
        'current_date' => $current_date,
        'present_count' => $present_count,
        'late_count' => $late_count,
        'total_hours' => number_format($total_hours, 2),
        'total_overtime' => number_format($total_overtime, 2),
        'attendance_count' => count($monthly_attendance),
        'leave_count' => count($leave_requests),
        'balance_count' => count($leave_balances),
        'file_size_estimate' => ceil((count($monthly_attendance) * 0.15 + count($leave_requests) * 0.10 + count($leave_balances) * 0.05) * 1.2) ?: 50,
        // New metrics
        'metrics' => $metrics,
        'punctuality' => $punctuality,
        'overtime' => $overtime
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($preview_data);
    exit;
}

error_log('Excel download mode - sending file');
// Build HTML content
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/export-dashboard.css">
</head>
<body>';

$html .= '<div class="header">EMPLOYEE DASHBOARD EXPORT</div>';
$html .= '<p><strong>Generated:</strong> ' . $current_date . '</p>';

// Employee Info Section
$html .= '<div class="subheader">EMPLOYEE INFORMATION</div>';
$html .= '<table class="info-table">
    <tr><td>Full Name</td><td>' . $emp_name . '</td></tr>
    <tr><td>Employee ID</td><td>' . $emp_id . '</td></tr>
    <tr><td>Department</td><td>' . $emp_dept . '</td></tr>
    <tr><td>Position</td><td>' . $emp_pos . '</td></tr>
</table>';

// Monthly Statistics Section
$html .= '<div class="subheader">MONTHLY STATISTICS - ' . $month_year . '</div>';
$html .= '<table class="stat-table">
    <tr><td>Present Days</td><td>' . $present_count . '</td></tr>
    <tr><td>Late Arrivals</td><td>' . $late_count . '</td></tr>
    <tr><td>Total Hours Worked</td><td>' . number_format($total_hours, 2) . '</td></tr>
    <tr><td>Overtime Hours</td><td>' . number_format($total_overtime, 2) . '</td></tr>
</table>';

// Attendance Records Section
$html .= '<div class="subheader">ATTENDANCE RECORDS</div>';
$html .= '<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Status</th>
            <th>Total Hours</th>
            <th>Regular Hours</th>
            <th>Overtime Hours</th>
        </tr>
    </thead>
    <tbody>';

foreach ($monthly_attendance as $record) {
    $date = date('Y-m-d', strtotime($record['time_in']));
    $time_in = date('H:i', strtotime($record['time_in']));
    $time_out = !empty($record['time_out']) ? date('H:i', strtotime($record['time_out'])) : '';
    $status = htmlspecialchars($record['status'] ?? '');
    $total_hrs = number_format($record['total_hours_worked'] ?? 0, 2);
    $regular_hrs = number_format($record['regular_hours_worked'] ?? 0, 2);
    $overtime_hrs = number_format($record['overtime_hours'] ?? 0, 2);
    
    $html .= '<tr>
        <td>' . $date . '</td>
        <td>' . $time_in . '</td>
        <td>' . $time_out . '</td>
        <td>' . $status . '</td>
        <td>' . $total_hrs . '</td>
        <td>' . $regular_hrs . '</td>
        <td>' . $overtime_hrs . '</td>
    </tr>';
}

$html .= '</tbody>
</table>';

// Leave Requests Section
$html .= '<div class="subheader">LEAVE REQUESTS</div>';
$html .= '<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Days</th>
            <th>Status</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>';

foreach ($leave_requests as $leave) {
    $type = htmlspecialchars($leave['leave_type_name'] ?? '');
    $start = $leave['start_date'] ?? '';
    $end = $leave['end_date'] ?? '';
    $days = $leave['total_days'] ?? '';
    $status = htmlspecialchars($leave['status'] ?? '');
    $remarks = htmlspecialchars($leave['hr_admin_remarks'] ?? $leave['department_head_remarks'] ?? '');
    
    $html .= '<tr>
        <td>' . $type . '</td>
        <td>' . $start . '</td>
        <td>' . $end . '</td>
        <td>' . $days . '</td>
        <td>' . $status . '</td>
        <td>' . $remarks . '</td>
    </tr>';
}

$html .= '</tbody>
</table>';

// Leave Balance Section
$html .= '<div class="subheader">LEAVE BALANCE - ' . date('Y') . '</div>';
$html .= '<table>
    <thead>
        <tr>
            <th>Leave Type</th>
            <th>Total Days</th>
            <th>Used</th>
            <th>Available</th>
        </tr>
    </thead>
    <tbody>';

foreach ($leave_balances as $balance) {
    $type = htmlspecialchars($balance['leave_type_name'] ?? '');
    $total = $balance['total_days'] ?? 0;
    $used = $balance['days_used'] ?? 0;
    $available = $total - $used;
    
    $html .= '<tr>
        <td>' . $type . '</td>
        <td>' . $total . '</td>
        <td>' . $used . '</td>
        <td>' . $available . '</td>
    </tr>';
}

$html .= '</tbody>
</table>';

// Footer
$html .= '<p style="margin-top: 30px; color: #666; font-size: 12px;">
    This report was generated by the Time & Attendance Management System.<br>
    For questions or discrepancies, please contact HR.
</p>';

$html .= '</body>
</html>';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="Dashboard_Export_' . date('Y-m-d_H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

echo $html;
exit;
