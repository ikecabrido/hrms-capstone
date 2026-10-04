<?php
/**
 * Absence & Late Management Interface
 * HR interface for managing absence and late arrival records
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/TimeDatabase.php';

Session::start();

$absenceLateMgmt = new AbsenceLateMgmt();
$employeeModel = new Employee();
$database = TimeDatabase::getInstance();
$db = $database->getConnection();
$employeeShiftModel = new EmployeeShift($db);
$current_page = 'absence_late_management';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';

$employeesNearTermination = $employeeShiftModel->getEmployeesNearTermination(7, 10);

// Get initial data
$filters = [
    'type' => $_GET['type'] ?? null,
    'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
    'limit' => 50
];

$records = $absenceLateMgmt->getRecords($filters);
$summaryStats = $absenceLateMgmt->getSummaryStats(['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']]);
?>
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/absence-late-management.css">

    <div class="module-header">
        <h1>Absence & Late Management</h1>
    </div>

    <div class="module-content">
            <div class="absence-late-container glass-panel">

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #0d47a1, #1976d2);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h4>Total Records</h4>
                            <div class="stat-value"><?php echo $summaryStats['total_records'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #c62828, #e53935);">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-content">
                            <h4>Total Absences</h4>
                            <div class="stat-value"><?php echo $summaryStats['total_absents'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f57f17, #fbc02d);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h4>Total Late Arrivals</h4>
                            <div class="stat-value"><?php echo $summaryStats['total_lates'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>

                <div style="background: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; margin-bottom: 20px; border-radius: 6px; box-shadow: 0 4px 12px rgba(211, 47, 47, 0.08);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <i class="fas fa-user-slash" style="font-size: 28px; color: #d32f2f;"></i>
                        <div>
                            <strong style="color: #b71c1c; font-size: 16px;">Near Termination Alert</strong>
                            <p style="margin: 4px 0 0 0; color: #6d1b1b; font-size: 14px;">
                                Employees listed below may need HR review because they missed their assigned shifts recently.
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($employeesNearTermination)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                        <?php foreach ($employeesNearTermination as $employee): ?>
                        <div style="background: #fff; border: 1px solid #f5c6cb; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 6px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                                <strong style="color: #2d3748; font-size: 14px;">
                                    <?php echo htmlspecialchars($employee['full_name'] ?? 'Unknown Employee'); ?>
                                </strong>
                                <span style="background: #fbe9e7; color: #c62828; border-radius: 999px; padding: 4px 8px; font-size: 11px; font-weight: 700;">
                                    <?php echo (int)($employee['missed_shift_days'] ?? 0); ?> missed
                                </span>
                            </div>
                            <div style="font-size: 12px; color: #5f6368;">
                                <?php echo htmlspecialchars($employee['department'] ?? 'No department'); ?>
                                <?php if (!empty($employee['position'])): ?>
                                    · <?php echo htmlspecialchars($employee['position']); ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 12px; color: #b71c1c; font-weight: 600;">
                                Last missed shift: <?php echo !empty($employee['last_missed_shift']) ? date('M d, Y', strtotime($employee['last_missed_shift'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div style="background: #fff; border: 1px dashed #d7a4a4; color: #7d2b2b; border-radius: 8px; padding: 12px 14px; font-size: 13px;">
                        No employees are currently flagged for near-termination review.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Filters -->
                <div class="filter-section glass-panel">
                    <input type="date" id="startDate" value="<?php echo $filters['start_date']; ?>" placeholder="Start Date">
                    <input type="date" id="endDate" value="<?php echo $filters['end_date']; ?>" placeholder="End Date">

                    <select id="typeFilter">
                        <option value="">All Types</option>
                        <option value="ABSENT" <?php echo $filters['type'] === 'ABSENT' ? 'selected' : ''; ?>>Absence</option>
                        <option value="LATE" <?php echo $filters['type'] === 'LATE' ? 'selected' : ''; ?>>Late</option>
                    </select>

                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>

                    <button class="btn btn-secondary" onclick="generateReport()">
                        <i class="fas fa-file-pdf"></i> Generate Report
                    </button>
                </div>

                <!-- Records Table -->
                <div class="records-table glass-panel">
                    <?php if (count($records) > 0): ?>
                    <table id="recordsTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Days Absent</th>
                                <th>Late Hours</th>
                                <th>Working Hours</th>
                                <th>Reason</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['absence_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $record['type'] === 'ABSENT' ? 'badge-absent' : 'badge-late'; ?>">
                                        <?php echo $record['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        // Days absent: for legacy records or attendance ABSENT entries we treat as 1 day; for LATE show '-'
                                        if (isset($record['type']) && strtoupper($record['type']) === 'ABSENT') {
                                            echo '1d';
                                        } else {
                                            echo '&mdash;';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <?php
                                        if (isset($record['late_minutes']) && $record['late_minutes'] !== null) {
                                            $late = (int)$record['late_minutes'];
                                            $lh = intdiv($late, 60);
                                            $lm = $late % 60;
                                            echo $lh . 'h ' . $lm . 'm';
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php echo isset($record['total_hours_worked']) && $record['total_hours_worked'] !== null ? number_format((float)$record['total_hours_worked'], 2) . 'h' : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($record['reason'] ?? '', 0, 30)); ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($record['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewRecord(<?php echo $record['record_id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No absence or late records found</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
    </div>

    <!-- View Record Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Absence/Late Details</h2>
                <button class="close-btn" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div id="recordDetails"></div>
            <div class="form-actions">
                <button class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/absence-late-management.js"></script>
