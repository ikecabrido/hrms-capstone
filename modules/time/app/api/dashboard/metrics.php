<?php
/**
 * Attendance Metrics API
 * Provides endpoints for calculating and retrieving attendance metrics
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../../../database/db.php';
require_once "../controllers/AuthController.php";
require_once "../helpers/MetricsCalculator.php";
require_once "../core/Session.php";

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Initialize database and metrics calculator
    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();
    $calculator = new MetricsCalculator($conn);

    switch ($action) {
        case 'calculate_punctuality':
            handleCalculatePunctuality($calculator);
            break;

        case 'calculate_overtime_frequency':
            handleCalculateOvertimeFrequency($calculator);
            break;

        case 'calculate_all_metrics':
            handleCalculateAllMetrics($calculator);
            break;

        case 'get_punctuality_score':
            handleGetPunctualityScore($calculator);
            break;

        case 'get_overtime_frequency':
            handleGetOvertimeFrequency($calculator);
            break;

        case 'get_attendance_metrics':
            handleGetAttendanceMetrics($calculator);
            break;

        case 'record_late_minutes':
            handleRecordLateMinutes($calculator);
            break;

        case 'record_overtime_event':
            handleRecordOvertimeEvent($calculator);
            break;

        case 'get_employee_metrics_dashboard':
            handleGetEmployeeMetricsDashboard($calculator);
            break;

        case 'get_attendance_metrics_summary':
            handleGetAttendanceMetricsSummary($calculator);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}

// ===== HANDLER FUNCTIONS =====

function handleCalculatePunctuality($calculator)
{
    $employeeId = $_GET['employee_id'] ?? $_POST['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? $_POST['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $result = $calculator->calculatePunctualityScore($employeeId, $monthYear);
    echo json_encode($result);
}

function handleCalculateOvertimeFrequency($calculator)
{
    $employeeId = $_GET['employee_id'] ?? $_POST['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? $_POST['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $result = $calculator->calculateOvertimeFrequency($employeeId, $monthYear);
    echo json_encode($result);
}

function handleCalculateAllMetrics($calculator)
{
    $employeeId = $_GET['employee_id'] ?? $_POST['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? $_POST['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $metrics = $calculator->calculateAttendanceMetrics($employeeId, $monthYear);
    $punctuality = $calculator->calculatePunctualityScore($employeeId, $monthYear);
    $overtime = $calculator->calculateOvertimeFrequency($employeeId, $monthYear);

    echo json_encode([
        'success' => true,
        'attendance_metrics' => $metrics,
        'punctuality_details' => $punctuality,
        'overtime_details' => $overtime
    ]);
}

function handleGetPunctualityScore($calculator)
{
    $employeeId = $_GET['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $score = $calculator->getPunctualityScore($employeeId, $monthYear);
    
    if ($score) {
        echo json_encode(['success' => true, 'data' => $score]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No punctuality score found']);
    }
}

function handleGetOvertimeFrequency($calculator)
{
    $employeeId = $_GET['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $frequency = $calculator->getOvertimeFrequency($employeeId, $monthYear);
    
    if ($frequency) {
        echo json_encode(['success' => true, 'data' => $frequency]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No overtime frequency data found']);
    }
}

function handleGetAttendanceMetrics($calculator)
{
    $employeeId = $_GET['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $metrics = $calculator->getAttendanceMetrics($employeeId, $monthYear);
    
    if ($metrics) {
        echo json_encode(['success' => true, 'data' => $metrics]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No attendance metrics found']);
    }
}

function handleRecordLateMinutes($calculator)
{
    $attendanceId = $_POST['attendance_id'] ?? '';
    $lateMinutes = (int)($_POST['late_minutes'] ?? 0);

    if (!$attendanceId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Attendance ID required']);
        return;
    }

    $result = $calculator->recordLateMinutes($attendanceId, $lateMinutes);
    echo json_encode($result);
}

function handleRecordOvertimeEvent($calculator)
{
    $employeeId = $_POST['employee_id'] ?? '';
    $attendanceId = $_POST['attendance_id'] ?? '';
    $overtimeHours = (float)($_POST['overtime_hours'] ?? 0);
    $categoryKey = $_POST['reason_category'] ?? 'OTHER';
    $notes = $_POST['reason_notes'] ?? '';

    if (!$employeeId || $overtimeHours <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID and overtime hours required']);
        return;
    }

    $result = $calculator->recordOvertimeEvent($employeeId, $attendanceId, $overtimeHours, $categoryKey, $notes);
    echo json_encode($result);
}

function handleGetEmployeeMetricsDashboard($calculator)
{
    $employeeId = $_GET['employee_id'] ?? '';
    $monthYear = $_GET['month_year'] ?? date('Y-m');

    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Employee ID required']);
        return;
    }

    $metrics = $calculator->getAttendanceMetrics($employeeId, $monthYear);
    $punctuality = $calculator->getPunctualityScore($employeeId, $monthYear);
    $overtime = $calculator->getOvertimeFrequency($employeeId, $monthYear);

    echo json_encode([
        'success' => true,
        'employee_id' => $employeeId,
        'month_year' => $monthYear,
        'metrics' => $metrics,
        'punctuality' => $punctuality,
        'overtime' => $overtime,
        'dashboard' => [
            'attendance_rate' => $metrics ? $metrics['attendance_rate'] : 0,
            'absence_rate' => $metrics ? $metrics['absence_rate'] : 0,
            'punctuality_score' => $metrics ? $metrics['punctuality_score'] : 0,
            'overall_performance_score' => $metrics ? $metrics['overall_performance_score'] : 0,
            'overtime_frequency_rating' => $metrics ? $metrics['overtime_frequency_rating'] : 'LOW',
            'late_instances' => $punctuality ? $punctuality['total_late_incidents'] : 0,
            'total_overtime_hours' => $overtime ? $overtime['total_overtime_hours'] : 0
        ]
    ]);
}

function handleGetAttendanceMetricsSummary($calculator)
{
    $monthYear = $_GET['month_year'] ?? date('Y-m');
    
    try {
        $db = TimeDatabase::getInstance();
        $conn = $db->getConnection();
        
        // Get metrics from ta_attendance_metrics table
        $query = "SELECT 
                    COUNT(*) as total_employees,
                    ROUND(AVG(attendance_rate), 2) as avg_attendance_rate,
                    ROUND(AVG(absence_rate), 2) as avg_absence_rate,
                    ROUND(AVG(punctuality_score), 2) as avg_punctuality_score,
                    ROUND(AVG(overall_performance_score), 2) as avg_overall_performance,
                    SUM(total_late_incidents) as total_late_incidents,
                    SUM(total_overtime_hours) as total_overtime_hours
                 FROM ta_attendance_metrics
                 WHERE month_year = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$monthYear]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$metrics || $metrics['total_employees'] == 0) {
            // No data yet, show sample data
            $metrics = [
                'total_employees' => 0,
                'avg_attendance_rate' => 0,
                'avg_absence_rate' => 0,
                'avg_punctuality_score' => 0,
                'avg_overall_performance' => 0,
                'total_late_incidents' => 0,
                'total_overtime_hours' => 0
            ];
        }
        
        // Count excellent performers (score >= 90)
        $query_excellent = "SELECT COUNT(*) as count FROM ta_attendance_metrics 
                           WHERE month_year = ? AND overall_performance_score >= 90";
        $stmt_excellent = $conn->prepare($query_excellent);
        $stmt_excellent->execute([$monthYear]);
        $excellent = $stmt_excellent->fetch(PDO::FETCH_ASSOC);
        
        // Count critical issues (score < 60 or absence_rate > 20)
        $query_critical = "SELECT COUNT(*) as count FROM ta_attendance_metrics 
                          WHERE month_year = ? AND (overall_performance_score < 60 OR absence_rate > 20)";
        $stmt_critical = $conn->prepare($query_critical);
        $stmt_critical->execute([$monthYear]);
        $critical = $stmt_critical->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'month_year' => $monthYear,
            'summary' => [
                'total_employees' => (int)($metrics['total_employees'] ?? 0),
                'avg_attendance_rate' => (float)($metrics['avg_attendance_rate'] ?? 0),
                'avg_absence_rate' => (float)($metrics['avg_absence_rate'] ?? 0),
                'avg_punctuality_score' => (float)($metrics['avg_punctuality_score'] ?? 0),
                'avg_overall_performance' => (float)($metrics['avg_overall_performance'] ?? 0),
                'total_late_incidents' => (int)($metrics['total_late_incidents'] ?? 0),
                'total_overtime_hours' => (float)($metrics['total_overtime_hours'] ?? 0),
                'excellent_performers' => (int)($excellent['count'] ?? 0),
                'critical_issues' => (int)($critical['count'] ?? 0)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
