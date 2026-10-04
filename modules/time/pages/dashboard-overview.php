<?php
/**
 * HR Dashboard - Time & Attendance System
 * Main interface for HR to view attendance, generate QR codes, and manage approvals
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../app/models/Holiday.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
require_once __DIR__ . '/../app/core/TimeDatabase.php';

use App\Models\Holiday;

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
$employeesNearTermination = $employeeShiftModel->getEmployeesNearTermination(7, 10);
$employeesNearTerminationCount = count($employeesNearTermination);

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

// Today's status breakdown (present, late, absent, on leave)
$todayStatusCounts = [
    'PRESENT' => 0,
    'LATE' => 0,
    'ABSENT' => 0,
    'ON_LEAVE' => 0,
];
$sql = "SELECT status, COUNT(*) as cnt FROM ta_attendance WHERE attendance_date = CURDATE() GROUP BY status";
$stmt = $db->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $k = strtoupper($row['status']);
    if (isset($todayStatusCounts[$k])) $todayStatusCounts[$k] = (int)$row['cnt'];
}

// Past 7 days present counts for trend
$trendLabels = [];
$trendPresent = [];
$trendAvgHours = [];
$days = 6; // past 6 days + today = 7
for ($i = $days; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $trendLabels[] = date('M d', strtotime($d));
    $q = "SELECT SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as present_count FROM ta_attendance WHERE attendance_date = :d";
    $s = $db->prepare($q);
    $s->execute([':d' => $d]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    $trendPresent[] = (int)($r['present_count'] ?? 0);

    // average hours per day (nullable)
    $q2 = "SELECT AVG(total_hours_worked) as avg_hours FROM ta_attendance WHERE attendance_date = :d AND total_hours_worked IS NOT NULL";
    $s2 = $db->prepare($q2);
    $s2->execute([':d' => $d]);
    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
    $trendAvgHours[] = $r2 && $r2['avg_hours'] !== null ? (float)$r2['avg_hours'] : null;
}

// Expected present line (approx): current active shift assignments count (used as baseline)
$expectedPresentBaseline = count($employeeShiftModel->getAllAssignments());

// Average working hours today (decimal hours)
$avgHours = null;
$stmt = $db->prepare("SELECT AVG(total_hours_worked) as avg_hours FROM ta_attendance WHERE attendance_date = CURDATE() AND total_hours_worked IS NOT NULL");
if ($stmt->execute()) {
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r && $r['avg_hours'] !== null) {
        $avgHours = (float)$r['avg_hours'];
    }
}

function formatHoursHM($decimalHours) {
    if ($decimalHours === null) return 'N/A';
    $totalMinutes = (int) round($decimalHours * 60);
    $h = intdiv($totalMinutes, 60);
    $m = $totalMinutes % 60;
    return sprintf('%dh %02dm', $h, $m);
}

$avgHoursLabel = $avgHours !== null ? number_format($avgHours, 2) : 'N/A';
$avgHoursHM = $avgHours !== null ? formatHoursHM($avgHours) : 'N/A';
$avgHoursPercent = $avgHours !== null ? min(100, round(($avgHours / 8.0) * 100)) : 0; // compared to 8h expected

// 7-day average (daily averages averaged, ignoring nulls)
$validDaily = array_filter($trendAvgHours, function($v){ return $v !== null; });
$avg7 = null;
if (count($validDaily) > 0) {
    $avg7 = array_sum($validDaily) / count($validDaily);
}
$avg7Label = $avg7 !== null ? number_format($avg7, 2) : 'N/A';
$avg7HM = $avg7 !== null ? formatHoursHM($avg7) : 'N/A';
$avg7Percent = $avg7 !== null ? min(100, round(($avg7 / 8.0) * 100)) : 0;

// Count employees with average hours < 6 over the same 7-day range
$shortThreshold = 8.0;
$rangeStart = date('Y-m-d', strtotime('-6 days'));
$rangeEnd = date('Y-m-d');
$shortSql = "SELECT COUNT(*) AS cnt FROM (SELECT employee_id, AVG(total_hours_worked) AS avg_h FROM ta_attendance WHERE attendance_date BETWEEN :start AND :end AND total_hours_worked IS NOT NULL GROUP BY employee_id HAVING avg_h < :threshold) t";
$shortStmt = $db->prepare($shortSql);
$shortStmt->execute([':start' => $rangeStart, ':end' => $rangeEnd, ':threshold' => $shortThreshold]);
$shortRow = $shortStmt->fetch(PDO::FETCH_ASSOC);
$shortCount = $shortRow ? (int)$shortRow['cnt'] : 0;
$shortPercent = $allEmployees > 0 ? round(($shortCount / $allEmployees) * 100, 1) : 0;

// Top active employees (by present count in last 7 days)
// Top employees: build query dynamically depending on whether profile tables exist
$topEmployees = [];
$start = date('Y-m-d', strtotime('-6 days'));
$end = date('Y-m-d');

// helper: check if a table exists in current database
function tableExists($db, $tableName) {
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
        $s->execute([':t' => $tableName]);
        return (int)$s->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

$hasEmUsers = tableExists($db, 'em_users');
$hasUsers = tableExists($db, 'users');

$profileSelect = "'' AS profile_pic";
$extraJoins = "";
if ($hasEmUsers && $hasUsers) {
    $extraJoins = "LEFT JOIN em_users eu ON eu.employee_id = e.employee_id\n           LEFT JOIN users u ON u.employee_id = e.employee_id";
    $profileSelect = "COALESCE(u.profile_pic, eu.profile_pic, '') AS profile_pic";
} elseif ($hasEmUsers) {
    $extraJoins = "LEFT JOIN em_users eu ON eu.employee_id = e.employee_id";
    $profileSelect = "COALESCE(eu.profile_pic, '') AS profile_pic";
} elseif ($hasUsers) {
    $extraJoins = "LEFT JOIN users u ON u.employee_id = e.employee_id";
    $profileSelect = "COALESCE(u.profile_pic, '') AS profile_pic";
}

$topSql = "SELECT e.employee_id, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, COALESCE(d.department_name, '') AS department, " . $profileSelect . " , COUNT(a.attendance_id) AS present_count
           FROM em_employees e
           LEFT JOIN em_departments d ON e.department_id = d.department_id
           LEFT JOIN ta_attendance a ON a.employee_id = e.employee_id AND a.time_in IS NOT NULL AND a.attendance_date BETWEEN :start AND :end\n           " . $extraJoins . "\n           WHERE LOWER(e.employment_status) = 'active'
           GROUP BY e.employee_id
           ORDER BY present_count DESC
           LIMIT 5";

try {
    $ts = $db->prepare($topSql);
    $ts->execute([':start' => $start, ':end' => $end]);
    $topEmployees = $ts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // On error (missing tables or other issues), fall back to a minimal query without joins
    try {
        $fallbackSql = "SELECT e.employee_id, CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.last_name, '')) AS full_name, '' AS department, '' AS profile_pic, COUNT(a.attendance_id) AS present_count
                        FROM em_employees e
                        LEFT JOIN ta_attendance a ON a.employee_id = e.employee_id AND a.time_in IS NOT NULL AND a.attendance_date BETWEEN :start AND :end
                        WHERE LOWER(e.employment_status) = 'active'
                        GROUP BY e.employee_id
                        ORDER BY present_count DESC
                        LIMIT 5";
        $fts = $db->prepare($fallbackSql);
        $fts->execute([':start' => $start, ':end' => $end]);
        $topEmployees = $fts->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        // give up and leave $topEmployees empty
        $topEmployees = [];
    }
}


$current_page = 'dashboard-overview';
$current_role = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? 'time';
?>
<link rel="stylesheet" href="assets/css/dashboard-template.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/realtime-dashboard.css">
<link rel="stylesheet" href="assets/css/upcoming-holidays-widget.css">

    <div class="module-header">
        <h1>Time & Attendance Dashboard</h1>
    </div>

    
            <!-- Top controls -->
            <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                <!-- Button moved into Near Termination alert -->
            </div>
            <!-- Alerts removed per request -->

            <!-- Holiday Alert -->
            <?php if ($isHolidayToday && $todayHolidayInfo): ?>
            <div style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-calendar-check" style="font-size: 28px; color: #2196F3;"></i>
                <div>
                    <strong style="color: #1565c0; font-size: 16px;">Today is a Public Holiday</strong>
                    <p style="margin: 8px 0 0 0; color: #455a64; font-size: 14px;">
                        <strong><?php echo htmlspecialchars($todayHolidayInfo['name']); ?></strong> - All employees are marked as HOLIDAY. No absences will be recorded.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard grid: left = Top Employees, middle = KPIs + Today's Attendance, right = Avg & Trend -->
            <div class="dashboard-grid" style="display:grid;grid-template-columns:280px 1fr 360px;gap:16px;align-items:start;margin-bottom:12px;">
                <!-- Left column: Top active employees (tall) -->
                <div style="background:#fff;border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(16,24,40,0.04);min-height:320px;">
                    <div style="font-weight:800;margin-bottom:8px;color:#0f172a;">Top Active Employees</div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <?php if (!empty($topEmployees)): ?>
                            <?php foreach ($topEmployees as $emp): ?>
                                <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;background:#f8fafc;">
                                    <?php
                                        $pic = $emp['profile_pic'] ?? null;
                                        $imgSrc = null;
                                        if (!empty($pic)) {
                                            $imgSrc = htmlspecialchars($pic);
                                        }
                                        $initials = '';
                                        if (isset($emp['full_name'])) {
                                            $parts = explode(' ', trim($emp['full_name']));
                                            $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                                        }
                                    ?>
                                    <div style="width:44px;height:44px;border-radius:8px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#e2e8f0;color:#0f172a;font-weight:700;">
                                        <?php if (!empty($imgSrc)): ?>
                                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($emp['full_name']); ?>" style="width:100%;height:100%;object-fit:cover;" />
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($initials); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-weight:700;color:#0f172a;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($emp['full_name']); ?></div>
                                        <div style="font-size:12px;color:#64748b;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($emp['department'] ?? ''); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:#64748b;font-size:13px;">No data</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Middle column: KPI cards on top, then Today's Attendance -->
                <div>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                        <div class="kpi-card kpi-total">
                            <div style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:12px;opacity:0.95;font-weight:700;letter-spacing:0.6px;">TOTAL EMPLOYEES</div>
                                <div style="font-size:22px;font-weight:800;margin-top:6px;"> <?php echo $allEmployees; ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;">Active employees</div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-present">
                            <div style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:12px;opacity:0.95;font-weight:700;letter-spacing:0.6px;">PRESENT TODAY</div>
                                <div style="font-size:22px;font-weight:800;margin-top:6px;"> <?php echo $todayStats['present_count'] ?? 0; ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;"> <?php echo $attendancePercentage; ?>% attendance</div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-absent">
                            <div style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:12px;opacity:0.95;font-weight:700;letter-spacing:0.6px;">ABSENT TODAY</div>
                                <div style="font-size:22px;font-weight:800;margin-top:6px;"> <?php echo $todayStats['absent_count'] ?? 0; ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;">Need follow-up</div>
                            </div>
                        </div>

                        <div class="kpi-card kpi-pending">
                            <div style="width:56px;height:56px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:12px;opacity:0.95;font-weight:700;letter-spacing:0.6px;">PENDING APPROVALS</div>
                                <div style="font-size:22px;font-weight:800;margin-top:6px;"> <?php echo count($pendingApprovals); ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;">Manual entries</div>
                            </div>
                        </div>
                    </div>

                    <!-- Middle column: Today's Attendance panel (full width of middle column) -->
                    <div style="background:#fff3e0;border:1px solid #f4e5d5;padding:16px;border-radius:10px;margin-top:6px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <strong>Today's Attendance</strong>
                            <small style="color:#667085;">As of <?php echo date('M d, Y'); ?></small>
                        </div>
                        <div style="display:flex;gap:6px;margin-top:10px;align-items:center;">
                            <div style="flex:1;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="width:10px;height:10px;background:#0b5cab;display:inline-block;border-radius:2px;"></span> Present <strong style="margin-left:6px;"><?php echo $todayStatusCounts['PRESENT']; ?></strong></div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="width:10px;height:10px;background:#ff9800;display:inline-block;border-radius:2px;"></span> Late <strong style="margin-left:6px;"><?php echo $todayStatusCounts['LATE']; ?></strong></div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="width:10px;height:10px;background:#b42318;display:inline-block;border-radius:2px;"></span> Absent <strong style="margin-left:6px;"><?php echo $todayStatusCounts['ABSENT']; ?></strong></div>
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><span style="width:10px;height:10px;background:#0b9aa6;display:inline-block;border-radius:2px;"></span> On Leave <strong style="margin-left:6px;"><?php echo $todayStatusCounts['ON_LEAVE']; ?></strong></div>
                            </div>
                            <div style="width:320px;height:160px;display:flex;align-items:center;justify-content:center;">
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:transparent;border-radius:10px;padding:8px;">
                                    <canvas id="attendanceDonut" style="max-width:100%;max-height:100%"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right column: stacked Avg & Short, then Attendance Trend below -->
                <div>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div class="right-stat avg">
                            <div>
                                <div style="font-size:13px;opacity:0.95;">Average Working Hours (7 days)</div>
                                <div style="font-size:26px;font-weight:800;margin-top:6px;"><?php echo $avg7Label; ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;"><?php echo $avg7HM; ?></div>
                            </div>
                            <div style="width:72px;height:72px;display:flex;align-items:center;justify-content:center;">
                                <canvas id="avg7Donut" width="72" height="72"></canvas>
                            </div>
                        </div>

                        <div class="right-stat below">
                            <div>
                                <div style="font-size:13px;opacity:0.95;">Employees Below Threshold (7 days)</div>
                                <div style="font-size:26px;font-weight:800;margin-top:6px;"><?php echo number_format($shortCount); ?></div>
                                <div style="font-size:12px;opacity:0.95;margin-top:4px;"><?php echo $shortPercent; ?>% of active employees</div>
                            </div>
                            <div style="width:72px;height:72px;display:flex;align-items:center;justify-content:center;">
                                <canvas id="shortDonut" width="72" height="72"></canvas>
                            </div>
                        </div>

                        <div style="background:#fff;border:1px solid #e9eef6;padding:12px;border-radius:8px;min-height:160px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <strong>Attendance Trend (7 days)</strong>
                                <small style="color:#667085;">Expected baseline: <?php echo $expectedPresentBaseline; ?></small>
                            </div>
                            <div style="margin-top:10px;">
                                <canvas id="attendanceTrend" style="width:100%;height:140px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Attendance History Panel (hidden by default) -->
                <div id="attendanceHistoryPanel" style="display:none;width:100%;margin-top:20px;padding:14px;background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(16,24,40,0.06);grid-column:1 / -1;">
                    <h2 style="margin-top:0;">Attendance History</h2>
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
                        <button id="historyPrevDay" class="btn btn-sm btn-secondary">Prev Day</button>
                        <input type="date" id="historyDate" value="<?php echo date('Y-m-d'); ?>" style="padding:6px;border-radius:6px;border:1px solid #d1d5db;" />
                        <button id="historyNextDay" class="btn btn-sm btn-secondary">Next Day</button>
                        <select id="historyLimit" class="form-control" style="width:120px;margin-left:auto;">
                            <option value="25">25 / page</option>
                            <option value="50" selected>50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </div>

                    <div class="time-table-scroll">
                    <table id="historyTable" style="width:100%;border-collapse:collapse;table-layout:auto;">
                        <thead>
                            <tr>
                                <th style="width:100px;">Date</th>
                                <th>QR / ID</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody"></tbody>
                    </table>
                    </div>

                    <div id="historyPagination" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
                        <div id="historyPageInfo" style="color:#555;"></div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button id="historyPrev" class="btn btn-sm btn-secondary">Previous</button>
                            <button id="historyNext" class="btn btn-sm btn-secondary">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Today's Attendance Table -->
            <div class="attendance-table-wrap" style="width:100%;margin-top:20px;padding:14px;background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(16,24,40,0.06);grid-column:1 / -1;">
                <h2 style="margin-top:0;">Today's Attendance (<?php echo count($todayRecords); ?> employees)</h2>

                <div class="filter-controls" style="flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    <input type="text" id="attendanceSearch" placeholder="Search by name or employee #..." style="flex: 1 1 320px;" />
                    <select id="attendanceSort" style="width: 220px;">
                        <option value="name">Sort: Name (A-Z)</option>
                        <option value="name-desc">Sort: Name (Z-A)</option>
                        <option value="time">Sort: Time In (Latest)</option>
                        <option value="time-asc">Sort: Time In (Earliest)</option>
                        <option value="status">Sort: Status</option>
                    </select>
                </div>

                <div class="time-table-scroll">
                <table id="attendanceTable" style="width:100%;border-collapse:collapse;table-layout:auto;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">QR / ID</th>
                            <th>Employee</th>
                            <th>Shift Start</th>
                            <th>Shift End</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th style="width: 120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody">
                    </tbody>
                </table>
                </div>

                <div class="modal fade" id="attendanceInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Employee Attendance Details</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="attendanceInfoModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="attendancePagination" style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px; gap: 10px; flex-wrap: wrap;">
                    <div id="attendancePageInfo" style="font-size: 14px; color: #555;"></div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <button type="button" id="attendancePrev" class="btn btn-sm btn-secondary">Previous</button>
                        <button type="button" id="attendanceNext" class="btn btn-sm btn-secondary">Next</button>
                    </div>
                </div>

                <!-- Chart.js -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                <script>
                    (function(){
                        const donutCtx = document.getElementById('attendanceDonut');
                        if (donutCtx) {
                            new Chart(donutCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Present','Late','Absent','On Leave'],
                                    datasets: [{
                                        data: [<?php echo $todayStatusCounts['PRESENT']; ?>, <?php echo $todayStatusCounts['LATE']; ?>, <?php echo $todayStatusCounts['ABSENT']; ?>, <?php echo $todayStatusCounts['ON_LEAVE']; ?>],
                                        backgroundColor: ['#0b5cab','#ff9800','#b42318','#0b9aa6']
                                    }]
                                },
                                options: {plugins:{legend:{display:false}},cutout:'60%'}
                            });
                        }

                        const trendCanvas = document.getElementById('attendanceTrend');
                        if (trendCanvas) {
                            const presentData = <?php echo json_encode($trendPresent); ?> || [];
                            const expectedBaseline = <?php echo json_encode($expectedPresentBaseline); ?> || 0;

                            const rawMax = Math.max.apply(null, presentData.map(v => (v === null || v === undefined) ? 0 : v).concat([expectedBaseline]));
                            const padding = Math.max(1, Math.ceil(rawMax * 0.2));
                            const suggestedMax = rawMax + padding;

                            const lineShadowPlugin = {
                                id: 'lineShadow',
                                afterDatasetsDraw(chart) {
                                    const ctx = chart.ctx;
                                    const meta = chart.getDatasetMeta(0);
                                    if (!meta || !meta.data) return;
                                    ctx.save();
                                    ctx.globalCompositeOperation = 'destination-over';
                                    ctx.shadowColor = 'rgba(11,92,171,0.28)';
                                    ctx.shadowBlur = 18;
                                    ctx.lineWidth = 0;
                                    ctx.beginPath();
                                    meta.data.forEach((pt, i) => {
                                        if (i === 0) ctx.moveTo(pt.x, pt.y);
                                        else ctx.lineTo(pt.x, pt.y);
                                    });
                                    ctx.strokeStyle = 'rgba(11,92,171,0.0)';
                                    ctx.stroke();
                                    ctx.restore();
                                }
                            };

                            Chart.register(lineShadowPlugin);

                            new Chart(trendCanvas, {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode($trendLabels); ?>,
                                    datasets: [
                                        {
                                            label: 'Present',
                                            data: presentData,
                                            borderColor: '#0b5cab',
                                            borderWidth: 2,
                                            tension: 0.4,
                                            fill: true,
                                            pointRadius: 2,
                                            pointHoverRadius: 4,
                                            backgroundColor: function(context) {
                                                const chart = context.chart;
                                                const {ctx, chartArea} = chart;
                                                if (!chartArea) {
                                                    return 'rgba(11,92,171,0.12)';
                                                }
                                                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                                gradient.addColorStop(0, 'rgba(11,92,171,0.36)');
                                                gradient.addColorStop(0.18, 'rgba(11,92,171,0.26)');
                                                gradient.addColorStop(0.35, 'rgba(11,92,171,0.18)');
                                                gradient.addColorStop(0.55, 'rgba(11,92,171,0.10)');
                                                gradient.addColorStop(1, 'rgba(11,92,171,0.02)');
                                                return gradient;
                                            }
                                        },
                                        {
                                            label: 'Expected',
                                            data: Array(<?php echo count($trendPresent); ?>).fill(expectedBaseline),
                                            borderColor: '#90a4ae',
                                            borderDash: [6,4],
                                            tension: 0.1,
                                            fill: false
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: { beginAtZero: true, suggestedMax: suggestedMax }
                                    },
                                    plugins: { legend: { position: 'bottom' } }
                                }
                            });
                        }

                        // 7-day average donut
                        const avg7Donut = document.getElementById('avg7Donut');
                        if (avg7Donut) {
                            const avg7Pct = <?php echo json_encode($avg7Percent); ?>;
                            new Chart(avg7Donut, {
                                type: 'doughnut',
                                data: { labels:['Avg','Rest'], datasets:[{data:[avg7Pct, 100-avg7Pct], backgroundColor:['#ffffff','rgba(255,255,255,0.18)'], borderWidth:0}]},
                                options:{cutout:'70%',plugins:{legend:{display:false},tooltip:{enabled:false}}}
                            });
                        }

                        // Short-hours donut
                        const shortDonut = document.getElementById('shortDonut');
                        if (shortDonut) {
                            const shortPct = <?php echo json_encode($shortPercent); ?>;
                            new Chart(shortDonut, {
                                type: 'doughnut',
                                data: { labels:['Short','Rest'], datasets:[{data:[shortPct, 100-shortPct], backgroundColor:['#ffffff','rgba(255,255,255,0.18)'], borderWidth:0}]},
                                options:{cutout:'70%',plugins:{legend:{display:false},tooltip:{enabled:false}}}
                            });
                        }
                    })();
                </script>
            </div>


    <script>
        window.__TA_CONFIG = {
            isHolidayToday: <?php echo json_encode($isHolidayToday); ?>,
            holidayInfo: <?php echo json_encode($todayHolidayInfo); ?>,
            attendanceData: <?php echo json_encode($todayRecords); ?>,
            employees: <?php echo json_encode($activeEmployees); ?>
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="assets/js/dashboard.js"></script>
    
