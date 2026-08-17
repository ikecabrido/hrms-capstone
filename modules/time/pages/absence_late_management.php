<?php
/**
 * Absence & Late Management Interface
 * HR interface for managing absence and late arrival records
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

$absenceLateMgmt = new AbsenceLateMgmt();
$employeeModel = new Employee();
$current_page = 'absence_late_management';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';

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
                                    <?php echo isset($record['late_minutes']) && $record['late_minutes'] !== null ? number_format((float)$record['late_minutes'] / 60, 2) . 'h' : 'N/A'; ?>
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
