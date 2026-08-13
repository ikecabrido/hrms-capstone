<?php
/**
 * Calendar View - Monthly Attendance Calendar
 * Displays attendance records in a calendar format with color-coding
 */

require_once __DIR__ . '/../../../database/db.php';
require_once '../app/core/Session.php';
require_once '../app/models/Attendance.php';
require_once '../app/models/Employee.php';
require_once '../app/controllers/AuthController.php';

// Verify session
Session::start();
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$user_id = AuthController::getCurrentUserId();
$current_role = AuthController::getCurrentRole();

// Get employee details
$employeeModel = new Employee();
$employee = $employeeModel->getByUserId($user_id);
$employee_id = $employee['employee_id'];

$database = Database::getInstance();
$db = $database->getConnection();

// Get month and year from URL or use current
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month/year
$month = max(1, min(12, $month));
$year = max(2020, min(date('Y') + 1, $year));

// Get employee filter (for managers)
$view_employee_id = $employee_id;
if ($current_role === 'HR_ADMIN' || $current_role === 'DEPARTMENT_HEAD') {
    if (isset($_GET['employee_id']) && is_numeric($_GET['employee_id'])) {
        $view_employee_id = (int)$_GET['employee_id'];
    }
}

// Get attendance data for the month
$start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = date('Y-m-t', strtotime($start_date));

$stmt = $db->prepare("
    SELECT 
        DATE(time_in) as attendance_date,
        COUNT(*) as record_count,
        SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN TIME(time_in) > '08:00:00' THEN 1 ELSE 0 END) as late_count,
        AVG(total_hours_worked) as avg_hours,
        MAX(time_out) as last_time_out
    FROM ta_attendance
    WHERE employee_id = :employee_id
    AND DATE(time_in) BETWEEN :start_date AND :end_date
    GROUP BY DATE(time_in)
    ORDER BY DATE(time_in)
");

$stmt->execute([
    ':employee_id' => $view_employee_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);

$attendance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create lookup array
$attendance_by_date = [];
foreach ($attendance_data as $record) {
    $day = (int)date('d', strtotime($record['attendance_date']));
    $attendance_by_date[$day] = [
        'status' => determineStatus($record),
        'records' => $record['record_count'],
        'late' => $record['late_count'],
        'hours' => $record['avg_hours'],
        'date' => $record['attendance_date']
    ];
}

// Get employee info for header
if ($view_employee_id !== $employee_id) {
    $emp_stmt = $db->prepare("SELECT full_name FROM employees WHERE employee_id = :id");
    $emp_stmt->execute([':id' => $view_employee_id]);
    $employee_info = $emp_stmt->fetch(PDO::FETCH_ASSOC);
    $view_employee_name = ($employee_info) ? $employee_info['full_name'] : 'Unknown';
} else {
    $view_employee_name = htmlspecialchars($_SESSION['full_name'] ?? 'You');
}

// Get month name
$month_name = date('F Y', strtotime($start_date));

/**
 * Determine attendance status
 */
function determineStatus($record) {
    if ($record['record_count'] == 0) {
        return 'absent';
    } elseif ($record['late_count'] > 0) {
        return 'late';
    } else {
        return 'present';
    }
}

/**
 * Get calendar days for the month
 */
function getCalendarDays($year, $month) {
    $first_day = mktime(0, 0, 0, $month, 1, $year);
    $last_day = mktime(0, 0, 0, $month + 1, 0, $year);
    $first_day_of_week = date('w', $first_day);
    $num_days = date('d', $last_day);
    
    return [
        'first_day_of_week' => $first_day_of_week,
        'num_days' => $num_days
    ];
}

$calendar_info = getCalendarDays($year, $month);

// Get navigation dates
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get employees for dropdown (only for managers)
$employees_list = [];
if ($current_role === 'HR_ADMIN' || $current_role === 'DEPARTMENT_HEAD') {
    $emp_query = "
        SELECT employee_id, full_name 
        FROM employees 
        WHERE employment_status = 'Active'
    ";
    
    if ($current_role === 'DEPARTMENT_HEAD') {
        // Get current user's department
        $dept_stmt = $db->prepare("
            SELECT department FROM employees WHERE employee_id = :id
        ");
        $dept_stmt->execute([':id' => $employee_id]);
        $dept_result = $dept_stmt->fetch(PDO::FETCH_ASSOC);
        $current_dept = $dept_result['department'] ?? '';
        
        if ($current_dept) {
            $emp_query .= " AND department = '{$current_dept}'";
        }
    }
    
    $emp_query .= " ORDER BY full_name";
    $employees_list = $db->query($emp_query)->fetchAll(PDO::FETCH_ASSOC);
}

$current_page = 'calendar.php';
?>
<?php
$page_title = 'Attendance Calendar';
$page_subtitle = 'Monthly attendance calendar';
$page_head_extra = <<<'HTML'
<link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/mobile-responsive.js" defer></script>
<link rel="stylesheet" href="../assets/css/calendar.css">
HTML;
?>
</head>
<body>
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble" src="../../assets/pics/bcpLogo.png" alt="AdminLTELogo" height="60" width="60" />
    </div>
    <?php include '../app/components/Sidebar.php'; ?>

    <div class="main-content">
        <div class="top-header">
            <div class="top-header-content">
                <h1 style="margin: 0; font-size: 24px;">Attendance Calendar</h1>
                <div class="breadcrumb">
                    <a href="index.php">Dashboard</a> / Calendar
                </div>
            </div>
        </div>

        <main>
            <div class="calendar-header">
                <h2 class="calendar-title"><?php echo $month_name; ?></h2>
                <div class="calendar-nav">
                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?><?php echo ($view_employee_id !== $employee_id) ? '&employee_id=' . $view_employee_id : ''; ?>" class="nav-btn">
                        ← Previous
                    </a>
                    <span class="current-month"><?php echo date('M Y', strtotime("$year-$month-01")); ?></span>
                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?><?php echo ($view_employee_id !== $employee_id) ? '&employee_id=' . $view_employee_id : ''; ?>" class="nav-btn">
                        Next →
                    </a>
                    <a href="?" class="nav-btn">Today</a>
                </div>
            </div>

            <?php if (!empty($employees_list)): ?>
            <div class="employee-filter">
                <label for="employee_select">View Employee:</label>
                <select id="employee_select" onchange="changeEmployee()">
                    <option value="<?php echo $employee_id; ?>" <?php echo ($view_employee_id === $employee_id) ? 'selected' : ''; ?>>
                        My Calendar
                    </option>
                    <?php foreach ($employees_list as $emp): ?>
                        <option value="<?php echo $emp['employee_id']; ?>" <?php echo ($view_employee_id === $emp['employee_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="calendar-container">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sunday</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $day_counter = 1;
                        $first_day_of_week = $calendar_info['first_day_of_week'];
                        $num_days = $calendar_info['num_days'];

                        // Fill empty cells at start of month
                        for ($i = 0; $i < $first_day_of_week; $i++) {
                            echo '<td class="empty"></td>';
                        }

                        // Fill calendar days
                        for ($day = 1; $day <= $num_days; $day++) {
                            // New row every Sunday
                            if (($day - 1 + $first_day_of_week) % 7 === 0 && $day !== 1) {
                                echo '</tr><tr>';
                            }

                            $has_data = isset($attendance_by_date[$day]);
                            $status = $has_data ? $attendance_by_date[$day]['status'] : 'no-data';
                            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $is_today = ($date_str === date('Y-m-d'));
                            $is_future = strtotime($date_str) > time();

                            echo '<td class="' . ($is_today ? 'today' : '') . '" onclick="' . ($has_data ? "showDetails('$date_str')" : '') . '" style="' . ($is_future ? 'opacity: 0.5;' : '') . '">';
                            echo '<div class="day-number">' . $day . '</div>';

                            if ($has_data) {
                                $data = $attendance_by_date[$day];
                                echo '<div class="day-status status-' . $data['status'] . '">';
                                if ($data['status'] === 'present') {
                                    echo 'Present';
                                } elseif ($data['status'] === 'late') {
                                    echo 'Late';
                                } else {
                                    echo '✗ Absent';
                                }
                                echo '</div>';
                                if ($data['hours']) {
                                    echo '<div class="day-hours">' . number_format($data['hours'], 1) . 'h</div>';
                                }
                            }

                            echo '</td>';
                        }

                        // Fill empty cells at end of month
                        $total_cells = ($day - 1 + $first_day_of_week);
                        $remaining_cells = (ceil($total_cells / 7) * 7) - $total_cells;
                        for ($i = 0; $i < $remaining_cells; $i++) {
                            echo '<td class="empty"></td>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-box legend-present"></div>
                    <span>Present - On time attendance</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-late"></div>
                    <span>Late - Arrived after 8:00 AM</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box legend-absent"></div>
                    <span>Absent - No records</span>
                </div>
            </div>

            <?php
            // Calculate stats
            $total_present = 0;
            $total_late = 0;
            $total_absent = 0;
            $total_hours = 0;

            foreach ($attendance_by_date as $day_data) {
                if ($day_data['status'] === 'present') {
                    $total_present++;
                } elseif ($day_data['status'] === 'late') {
                    $total_late++;
                }
                $total_hours += $day_data['hours'] ?? 0;
            }

            // Count absent days (working days without records)
            $working_days = 0;
            for ($day = 1; $day <= $num_days; $day++) {
                $date_obj = new DateTime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT));
                $day_of_week = $date_obj->format('w');
                // Count weekdays only
                if ($day_of_week != 0 && $day_of_week != 6) {
                    $working_days++;
                    if (!isset($attendance_by_date[$day])) {
                        $total_absent++;
                    }
                }
            }
            ?>

            <div class="stats-summary">
                <div class="stat-card">
                    <h3>Present</h3>
                    <div class="value" style="color: #27ae60;"><?php echo $total_present; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Late</h3>
                    <div class="value" style="color: #f39c12;"><?php echo $total_late; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Absent</h3>
                    <div class="value" style="color: #e74c3c;"><?php echo $total_absent; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Hours</h3>
                    <div class="value" style="color: #3498db;"><?php echo number_format($total_hours, 1); ?></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Day Details Modal -->
    <div id="detailsModal" class="modal" onclick="closeDetails(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <span id="modalDate"></span>
                <button class="modal-close" onclick="closeDetails()">×</button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

<script>
    window.__TA_CONFIG = {
        attendanceData: <?php echo json_encode($attendance_by_date); ?>,
        employeeId: <?php echo json_encode($view_employee_id); ?>,
        month: <?php echo json_encode($month); ?>,
        year: <?php echo json_encode($year); ?>
    };
</script>
<script src="../assets/js/calendar.js"></script>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>



