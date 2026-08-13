<?php
/**
 * Absence & Late Records Page
 * Displays all absence and late arrival records with status, reasons, and appeal options
 * Only HR (time role) can access this page
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check if user is authenticated
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

// Only HR can access this page
if (!AuthController::hasRole('time')) {
    header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    exit;
}

$absenceLateMgmt = new AbsenceLateMgmt();
$employeeModel = new Employee();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$employee_id = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
$type_filter = $_GET['type'] ?? ''; // ABSENCE, LATE, WAITING_FOR_SHIFT, WEEKEND
$excuse_status = $_GET['excuse_status'] ?? ''; // ALL, PENDING, APPROVED, REJECTED
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

// Build filters
$filters = [
    'start_date' => $start_date,
    'end_date' => $end_date,
    'employee_id' => $employee_id,
    'limit' => $limit,
    'offset' => $offset
];

if (!empty($type_filter)) {
    $filters['type'] = $type_filter;
}

if (!empty($excuse_status) && $excuse_status !== 'ALL') {
    $filters['excuse_status'] = $excuse_status;
}

// Get all records
$records = $absenceLateMgmt->getRecords($filters);

// Get all employees for dropdown
$employees = $employeeModel->getAll('ACTIVE', 1000);

$current_page = 'absence_late_records.php';
$current_role = $_SESSION['role'] ?? 'HR_ADMIN';
?>
<?php
$page_title = 'Absence & Late Records';
$page_subtitle = 'All absence and late arrival records';
$page_icon = 'fa-exclamation-circle';
$page_head_extra = <<<HTML
<link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/adminlte-overrides.css">
<script src="../assets/js/mobile-responsive.js" defer></script>
<link rel="stylesheet" href="../assets/css/absence-late-records.css">
HTML;
?>
<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layout/content_header.php'; ?>

            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-row">
                        <div>
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        <div>
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        <div>
                            <label for="employee_id">Employee</label>
                            <select id="employee_id" name="employee_id">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['employee_id']; ?>" 
                                        <?php echo ($employee_id == $emp['employee_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['full_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="filter-row">
                        <div>
                            <label for="type_filter">Record Type</label>
                            <select id="type_filter" name="type">
                                <option value="">All Types</option>
                                <option value="ABSENCE" <?php echo ($type_filter === 'ABSENCE') ? 'selected' : ''; ?>>Absence</option>
                                <option value="LATE" <?php echo ($type_filter === 'LATE') ? 'selected' : ''; ?>>Late Arrival</option>
                                <option value="WAITING_FOR_SHIFT" <?php echo ($type_filter === 'WAITING_FOR_SHIFT') ? 'selected' : ''; ?>>Waiting for Shift</option>
                                <option value="WEEKEND" <?php echo ($type_filter === 'WEEKEND') ? 'selected' : ''; ?>>Weekend</option>
                            </select>
                        </div>
                        <div>
                            <label for="excuse_status">Excuse Status</label>
                            <select id="excuse_status" name="excuse_status">
                                <option value="ALL">All Status</option>
                                <option value="PENDING" <?php echo ($excuse_status === 'PENDING') ? 'selected' : ''; ?>>Pending Review</option>
                                <option value="APPROVED" <?php echo ($excuse_status === 'APPROVED') ? 'selected' : ''; ?>>Approved</option>
                                <option value="REJECTED" <?php echo ($excuse_status === 'REJECTED') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="?page=1" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="records-table">
                <?php if (!empty($records)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Excuse Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>
                                        <br>
                                        <small style="color: #999;"><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($record['absence_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $type_class = 'badge-' . strtolower($record['type']);
                                        $type_display = ucfirst(strtolower($record['type']));
                                        if ($record['type'] === 'WAITING_FOR_SHIFT') {
                                            $type_display = 'Waiting for Shift';
                                            $type_class = 'badge-waiting';
                                        }
                                        ?>
                                        <span class="badge <?php echo $type_class; ?>">
                                            <?php echo $type_display; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($record['type'] === 'LATE'): ?>
                                            <strong><?php echo (int)$record['minutes_late'] ?? 0; ?> min late</strong>
                                        <?php elseif ($record['type'] === 'ABSENCE'): ?>
                                            No time-in recorded
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($record['notes'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $excuse_class = 'badge-' . strtolower($record['excuse_status'] ?? 'pending');
                                        $excuse_display = ucfirst(strtolower($record['excuse_status'] ?? 'PENDING'));
                                        ?>
                                        <span class="badge <?php echo $excuse_class; ?>">
                                            <?php echo $excuse_display; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_appeal.php?record_id=<?php echo $record['record_id']; ?>" 
                                           class="btn btn-primary" style="font-size: 12px;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No absence or late records found for the selected filters.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($records)): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&employee_id=<?php echo $employee_id; ?>&type=<?php echo urlencode($type_filter); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span class="active"><?php echo $page; ?></span>

                    <?php if (count($records) >= $limit): ?>
                        <a href="?page=<?php echo $page + 1; ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&employee_id=<?php echo $employee_id; ?>&type=<?php echo urlencode($type_filter); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>
