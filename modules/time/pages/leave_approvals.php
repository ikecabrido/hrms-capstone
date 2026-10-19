<?php
/**
 * Leave Management Page
 * Department heads and HR can review leave requests and view employee leave balances
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/LeaveController.php';
require_once __DIR__ . '/../app/models/Leave.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/LeaveAbsenceHelper.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/TimeDatabase.php';
require_once __DIR__ . '/../includes/ta_paths.php';

Session::start();

// Check if user is authenticated
// Try global session first, then time_attendance session
$authenticated = false;
$role = null;
$user_id = null;

// Check global login session
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $authenticated = true;
    $role = $_SESSION['user']['role'];
    $user_id = $_SESSION['user']['id'];
} else if (AuthController::isAuthenticated()) {
    // Fallback to time_attendance auth check
    $authenticated = true;
    $role = AuthController::getCurrentRole();
    $user_id = AuthController::getCurrentUserId();
}

$leaveModel = new Leave();
$employeeModel = new Employee();
$leaveController = new LeaveController();

$message = "";
$messageType = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Pagination settings
$recordsPerPage = max(5, min(50, (int)($_REQUEST['employee_page_size'] ?? 10)));
$employeePage = max(1, (int)($_REQUEST['employee_page'] ?? 1));
$leavePage = max(1, (int)($_REQUEST['leave_page'] ?? 1));
$historyPage = max(1, (int)($_REQUEST['history_page'] ?? 1));
$historyPageSize = max(5, min(50, (int)($_REQUEST['history_page_size'] ?? $recordsPerPage)));
$employeeSearch = trim($_REQUEST['employee_search'] ?? '');

$historyLeaveTypes = $leaveModel->getLeaveTypes();
$historyLeaveTypeMap = [];
foreach ($historyLeaveTypes as $leaveType) {
    $historyLeaveTypeMap[(int)($leaveType['leave_type_id'] ?? 0)] = true;
}

$historyFromDate = trim((string)($_REQUEST['history_from_date'] ?? ''));
$historyToDate = trim((string)($_REQUEST['history_to_date'] ?? ''));
if ($historyFromDate !== '') {
    $fromDateObj = DateTime::createFromFormat('Y-m-d', $historyFromDate);
    if ($fromDateObj === false || $fromDateObj->format('Y-m-d') !== $historyFromDate) {
        $historyFromDate = '';
    }
}
if ($historyToDate !== '') {
    $toDateObj = DateTime::createFromFormat('Y-m-d', $historyToDate);
    if ($toDateObj === false || $toDateObj->format('Y-m-d') !== $historyToDate) {
        $historyToDate = '';
    }
}
if ($historyFromDate !== '' && $historyToDate !== '') {
    $fromDateObj = new DateTimeImmutable($historyFromDate);
    $toDateObj = new DateTimeImmutable($historyToDate);
    if ($fromDateObj > $toDateObj) {
        [$historyFromDate, $historyToDate] = [$historyToDate, $historyFromDate];
    }
}

$historyStatusValue = trim((string)($_REQUEST['history_status'] ?? ''));
if ($historyStatusValue !== '') {
    $normalizedHistoryStatus = strtolower($historyStatusValue);
    if (in_array($normalizedHistoryStatus, ['approved', 'rejected'], true)) {
        $historyStatusValue = ucfirst($normalizedHistoryStatus);
    } else {
        $historyStatusValue = '';
    }
}

$historyLeaveTypeId = (int)($_REQUEST['history_leave_type_id'] ?? 0);
if ($historyLeaveTypeId > 0 && !isset($historyLeaveTypeMap[$historyLeaveTypeId])) {
    $historyLeaveTypeId = 0;
}

$historySearchValue = trim((string)($_REQUEST['history_search'] ?? ''));
$historyFilters = [
    'from_date' => $historyFromDate,
    'to_date' => $historyToDate,
    'leave_type_id' => $historyLeaveTypeId,
    'status' => $historyStatusValue,
    'search' => $historySearchValue,
];

$historyHasAnyFilter = (
    $historyFilters['from_date'] !== '' ||
    $historyFilters['to_date'] !== '' ||
    $historyFilters['leave_type_id'] > 0 ||
    $historyFilters['status'] !== '' ||
    $historyFilters['search'] !== ''
);

$employeeOffset = ($employeePage - 1) * $recordsPerPage;
$leaveOffset = ($leavePage - 1) * $recordsPerPage;
$historyOffset = ($historyPage - 1) * $historyPageSize;

// Load employee list for the management table
$employees = $employeeModel->getAll('Active', $recordsPerPage, $employeeOffset, $employeeSearch);
$totalEmployees = $employeeModel->getTotalCount('Active', $employeeSearch);
$totalEmployeePages = max(1, (int)ceil($totalEmployees / $recordsPerPage));

// Get pending requests based on role
// Determine which leave requests to display based on approver role
if ($role === 'DEPARTMENT_HEAD') {
    $pendingRequests = $leaveModel->getPendingByDepartmentHead($user_id, $recordsPerPage, $leaveOffset);
    $totalLeaveRequests = $leaveModel->countPendingByDepartmentHead($user_id);
} else {
    $pendingRequests = $leaveModel->getForHRApproval($recordsPerPage, $leaveOffset);
    $totalLeaveRequests = $leaveModel->countForHRApproval();
}

$totalLeavePages = max(1, (int)ceil($totalLeaveRequests / $recordsPerPage));

$totalHistoryRequests = $leaveModel->countHistory(null, $historyFilters);
$totalHistoryPages = max(1, (int)ceil($totalHistoryRequests / $historyPageSize));
$historyPage = min(max(1, $historyPage), $totalHistoryPages);
$historyOffset = ($historyPage - 1) * $historyPageSize;
$historyRequests = $leaveModel->getHistory(null, $historyPageSize, $historyOffset, $historyFilters);
$historyRangeStart = $totalHistoryRequests > 0 ? ($historyOffset + 1) : 0;
$historyRangeEnd = $totalHistoryRequests > 0 ? min($historyOffset + $historyPageSize, $totalHistoryRequests) : 0;
$historyTabActive = $historyHasAnyFilter || isset($_REQUEST['history_page']);

/**
 * Render numeric pagination with ellipses and jump-to-page form.
 * $pageParam: the GET param name for the page (e.g., 'employee_page')
 */
function render_pagination($pageParam, $currentPage, $totalPages, $extraParams = []) {
    $currentPage = max(1, (int)$currentPage);
    $totalPages = max(1, (int)$totalPages);
    $window = 2; // pages to show around current

    // build base query from existing GET but override page param when rendering
    $baseQuery = $_GET;
    $baseQuery['page'] = 'leave_approvals';
    // ensure other paging params preserved
    foreach ($extraParams as $k => $v) {
        $baseQuery[$k] = $v;
    }

    echo '<div class="pagination pagination--numeric" role="navigation" aria-label="Pagination">';

    // Previous
    if ($currentPage > 1) {
        $baseQuery[$pageParam] = $currentPage - 1;
        echo '<a class="page-btn" href="?' . htmlentities(http_build_query($baseQuery)) . '" aria-label="Previous page">&laquo;</a>';
    }

    // First page
    if ($currentPage - $window > 1) {
        $baseQuery[$pageParam] = 1;
        echo '<a class="page-number" href="?' . htmlentities(http_build_query($baseQuery)) . '">1</a>';
        if ($currentPage - $window > 2) echo '<span class="page-ellipsis">&hellip;</span>';
    }

    // Window
    $start = max(1, $currentPage - $window);
    $end = min($totalPages, $currentPage + $window);
    for ($p = $start; $p <= $end; $p++) {
        $baseQuery[$pageParam] = $p;
        if ($p == $currentPage) {
            echo '<span class="page-number active" aria-current="page">' . $p . '</span>';
        } else {
            echo '<a class="page-number" href="?' . htmlentities(http_build_query($baseQuery)) . '">' . $p . '</a>';
        }
    }

    // Last page
    if ($currentPage + $window < $totalPages) {
        if ($currentPage + $window < $totalPages - 1) echo '<span class="page-ellipsis">&hellip;</span>';
        $baseQuery[$pageParam] = $totalPages;
        echo '<a class="page-number" href="?' . htmlentities(http_build_query($baseQuery)) . '">' . $totalPages . '</a>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $baseQuery[$pageParam] = $currentPage + 1;
        echo '<a class="page-btn" href="?' . htmlentities(http_build_query($baseQuery)) . '" aria-label="Next page">&raquo;</a>';
    }

    // Jump-to-page form
    echo '<form class="jump-to-page" method="GET" onsubmit="return jumpToPage(this, \'' . $pageParam . '\')">';
    // include all current query params as hidden inputs
    foreach ($baseQuery as $k => $v) {
        if ($k === $pageParam) continue;
        echo '<input type="hidden" name="' . htmlentities($k) . '" value="' . htmlentities($v) . '">';
    }
    echo '<label for="jump_' . $pageParam . '" class="sr-only">Jump to page</label>';
    echo '<input id="jump_' . $pageParam . '" class="jump-input" type="number" name="' . $pageParam . '" min="1" max="' . $totalPages . '" placeholder="Page"/>'; 
    echo '<button type="submit" class="page-btn">Go</button>';
    echo '</form>';

    echo '</div>';
}

// Load leave balances for pending request rows to display alongside approvals
$leaveBalances = [];
foreach ($pendingRequests as $request) {
    $balanceKey = $request['employee_id'] . '_' . $request['leave_type_id'];
    if (!isset($leaveBalances[$balanceKey])) {
        $leaveBalances[$balanceKey] = $leaveModel->getLeaveBalance($request['employee_id'], $request['leave_type_id']);
    }
}

// Load balance availability for current employees in the employee management table
$employeeBalances = [];
foreach ($employees as $emp) {
    $employeeBalances[$emp['employee_id']] = !empty($leaveModel->getLeaveBalance($emp['employee_id']));
}

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST['action'] ?? '');
    $leave_request_id = (int)($_POST['leave_request_id'] ?? 0);
    $remarks = Helper::sanitize($_POST['remarks'] ?? '');
    $is_hr = AuthController::hasRole('admin') || AuthController::hasRole('hr') || AuthController::hasRole('HR_ADMIN');
    $is_department_head = AuthController::hasRole('DEPARTMENT_HEAD') || AuthController::hasRole('department_head');
    $submittedEmployeePageSize = max(5, min(50, (int)($_POST['employee_page_size'] ?? $recordsPerPage)));
    $submittedEmployeeSearch = trim($_POST['employee_search'] ?? $employeeSearch);

    error_log("DEBUG: POST received - Action: $action, LeaveID: $leave_request_id, User: $user_id, Role: $role");

    if ($leave_request_id && in_array($action, ['approve', 'reject'], true)) {
        $targetRequest = $leaveModel->getById($leave_request_id);

        if (!$targetRequest) {
            $message = 'Leave request not found.';
            $messageType = 'error';
        } else {
            $sessionEmployeeId = (int)($user_id ?? AuthController::getCurrentUserId());
            if ((int) $targetRequest['employee_id'] === $sessionEmployeeId) {
                $message = 'You cannot approve or reject your own leave request.';
                $messageType = 'error';
            } elseif (!$is_hr && !$is_department_head) {
                $message = 'You are not authorized to approve or reject leave requests.';
                $messageType = 'error';
            } else {
                $isDepartmentHeadAllowed = false;
                if ($is_department_head) {
                    $database = TimeDatabase::getInstance();
                    $conn = $database->getConnection();
                    $headDeptQuery = "SELECT 1
                                      FROM ta_department_heads dh
                                      INNER JOIN em_employees e ON e.employee_id = :employee_id
                                      LEFT JOIN em_departments d ON e.department_id = d.department_id
                                      WHERE dh.user_id = :user_id
                                        AND dh.is_active = 1
                                        AND dh.department = COALESCE(d.department_name, e.department)";
                    $headStmt = $conn->prepare($headDeptQuery);
                    $headStmt->bindParam(':employee_id', $targetRequest['employee_id'], PDO::PARAM_INT);
                    $headStmt->bindParam(':user_id', $sessionEmployeeId, PDO::PARAM_INT);
                    $headStmt->execute();
                    $isDepartmentHeadAllowed = (bool) $headStmt->fetch(PDO::FETCH_ASSOC);
                }

                if ($is_department_head && !$is_hr && !$isDepartmentHeadAllowed) {
                    $message = 'You are not authorized to approve or reject leave requests for this department.';
                    $messageType = 'error';
                } else {
                    if ($action === 'approve') {
                        $result = $leaveController->approve($leave_request_id, $user_id, $remarks);
                        if ($result['success']) {
                            $_SESSION['flash_message'] = "Leave request approved successfully!";
                            $_SESSION['flash_type'] = "success";
                            header("Location: " . $_SERVER['PHP_SELF'] . "?page=leave_approvals&employee_page={$employeePage}&leave_page={$leavePage}&employee_page_size={$submittedEmployeePageSize}&employee_search=" . urlencode($submittedEmployeeSearch));
                            exit;
                        }
                        $message = $result['message'] ?? 'Failed to process approval.';
                        $messageType = "error";
                    } elseif ($action === 'reject') {
                        if (empty(trim($remarks))) {
                            $message = "Rejection reason is required.";
                            $messageType = "error";
                        } else {
                            $result = $leaveController->reject($leave_request_id, $user_id, $remarks);
                            if ($result['success']) {
                                $_SESSION['flash_message'] = "Leave request rejected.";
                                $_SESSION['flash_type'] = "warning";
                                header("Location: " . $_SERVER['PHP_SELF'] . "?page=leave_approvals&employee_page={$employeePage}&leave_page={$leavePage}&employee_page_size={$submittedEmployeePageSize}&employee_search=" . urlencode($submittedEmployeeSearch));
                                exit;
                            }
                            $message = $result['message'] ?? 'Failed to process rejection.';
                            $messageType = "error";
                        }
                    }
                }
            }
        }
    }
}

$current_page = 'leave_approvals';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
?>
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/hr-template.css">
<link rel="stylesheet" href="assets/css/leave-approvals.css">
<!-- Toastr notifications -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="assets/js/mobile-responsive.js" defer></script>

    <div class="module-header">
        <h1>Leave Management</h1>
    </div>

    <div class="module-content leave-management-shell">
        <div class="summary-grid">
            <div class="summary-card summary-card--primary">
                <span class="summary-label">Pending Requests</span>
                <strong class="summary-value"><?php echo (int) $totalLeaveRequests; ?></strong>
            </div>
            <div class="summary-card summary-card--blue">
                <span class="summary-label">Active Employees</span>
                <strong class="summary-value"><?php echo (int) $totalEmployees; ?></strong>
            </div>
            <div class="summary-card summary-card--green">
                <span class="summary-label">Current Page</span>
                <strong class="summary-value"><?php echo $leavePage; ?>/<?php echo $totalLeavePages; ?></strong>
            </div>
        </div>

    <div class="card shadow-sm border-0 leave-panel">
        <?php if (!empty($message)): ?>
            <div class="card-body pb-0">
                <div class="alert alert-<?php echo $messageType; ?> mb-0">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <div class="section-header">
                <h4>Employee Leave Balances</h4>
                <p>Click an employee to view their remaining leave balances.</p>
            </div>
            <div class="toolbar-panel mb-4">
                <form method="GET" class="toolbar-form" data-skip="true">
                    <input type="hidden" name="page" value="leave_approvals">
                    <input type="hidden" name="leave_page" value="<?php echo $leavePage; ?>">
                    <input type="hidden" name="employee_page_size" value="<?php echo $recordsPerPage; ?>">
                    <input type="text" name="employee_search" class="form-control toolbar-search" placeholder="Search employees..." value="<?php echo htmlspecialchars($employeeSearch); ?>">
                    <button type="submit" class="action-btn btn-approve">Search</button>
                    <a href="?page=leave_approvals&employee_page_size=<?php echo $recordsPerPage; ?>&leave_page=<?php echo $leavePage; ?>&employee_search=" class="action-btn action-btn--light">Clear</a>
                </form>
                <div class="toolbar-actions">
                    <label for="employeePageSize" class="toolbar-label">Page size:</label>
                    <select id="employeePageSize" class="form-control toolbar-select" onchange="changeEmployeePageSize(this.value)">
                        <?php foreach ([5, 10, 15, 20, 30] as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $recordsPerPage === $size ? 'selected' : ''; ?>><?php echo $size; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="action-btn btn-approve toolbar-btn" type="button" onclick="provisionLeaveBalances()">Provision Balances</button>
                </div>
            </div>
            <?php if (empty($employees)): ?>
                <div class="alert alert-info mb-4">
                    No employees found.
                </div>
            <?php else: ?>
                <div class="table-responsive mb-4">
                    <table class="approvals-table employee-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                    <td>
                                    <?php echo htmlspecialchars($emp['department'] ?? 'N/A'); ?>
                                    <?php if (empty($employeeBalances[$emp['employee_id']])): ?>
                                        <span class="badge badge-warning" style="margin-left: 10px; font-size: 12px;">No balances yet</span>
                                    <?php endif; ?>
                                </td>
                                    <td>
                                        <button class="action-btn btn-approve balance-button" type="button" data-employee-id="<?php echo htmlspecialchars($emp['employee_id'], ENT_QUOTES, 'UTF-8'); ?>" data-full-name="<?php echo htmlspecialchars($emp['full_name'], ENT_QUOTES, 'UTF-8'); ?>">View Balance</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_pagination('employee_page', $employeePage, $totalEmployeePages, ['employee_search' => $employeeSearch, 'employee_page_size' => $recordsPerPage, 'leave_page' => $leavePage]); ?>
            <?php endif; ?>

            <div class="section-header">
                <h4>Pending Leave Requests</h4>
                <p>Review request approvals alongside employee leave balances.</p>
            </div>

            <div class="tab-container">
                <div class="tab-list">
                    <button class="tab-item <?php echo $historyTabActive ? '' : 'active'; ?>" data-tab="pending-requests">Pending</button>
                    <button class="tab-item <?php echo $historyTabActive ? 'active' : ''; ?>" data-tab="leave-history">History</button>
                </div>

                <div class="tab-content <?php echo $historyTabActive ? '' : 'active'; ?>" data-tab="pending-requests">
                <?php if (empty($pendingRequests)): ?>
                    <div class="alert alert-info mb-0">
                        No pending leave requests to review.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="approvals-table" id="leaveApprovalTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $req): ?>
                                <?php $balanceKey = $req['employee_id'] . '_' . $req['leave_type_id'];
                                      $balance = $leaveBalances[$balanceKey] ?? null;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['leave_type_name']); ?></td>
                                    <td><?php echo Helper::formatDate($req['start_date']); ?></td>
                                    <td><?php echo Helper::formatDate($req['end_date']); ?></td>
                                    <td><?php echo rtrim(rtrim(number_format($req['total_days'], 2), '0'), '.'); ?></td>
                                    <td>
                                        <?php if ($balance): ?>
                                            <span title="Used / Total">
                                                <?php echo rtrim(rtrim(number_format($balance['remaining_days'], 2), '0'), '.'); ?> left of <?php echo rtrim(rtrim(number_format($balance['total_days'], 2), '0'), '.'); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted"><?php echo rtrim(rtrim(number_format($balance['used_days'], 2), '0'), '.'); ?> used</small>
                                        <?php else: ?>
                                            <span class="text-muted">No balance record</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo LeaveAbsenceHelper::getLeaveStatusBadge($req['status']); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="action-btn btn-approve approve-request-btn" data-request-id="<?php echo intval($req['id']); ?>">Approve</button>
                                        <button type="button" class="action-btn btn-reject reject-request-btn" data-request-id="<?php echo intval($req['id']); ?>">Reject</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                        </table>
                    </div>
                    <?php render_pagination('leave_page', $leavePage, $totalLeavePages, ['employee_page' => $employeePage, 'employee_page_size' => $recordsPerPage, 'employee_search' => $employeeSearch]); ?>
                <?php endif; ?>
                </div>

                <div class="tab-content <?php echo $historyTabActive ? 'active' : ''; ?>" data-tab="leave-history">
                    <form method="GET" class="toolbar-form mb-3" style="flex-wrap: wrap;" data-skip="true">
                        <input type="hidden" name="page" value="leave_approvals">
                        <input type="hidden" name="employee_page" value="<?php echo $employeePage; ?>">
                        <input type="hidden" name="leave_page" value="<?php echo $leavePage; ?>">
                        <input type="hidden" name="employee_page_size" value="<?php echo $recordsPerPage; ?>">
                        <input type="date" name="history_from_date" class="form-control" value="<?php echo htmlspecialchars($historyFilters['from_date']); ?>" aria-label="History from date">
                        <input type="date" name="history_to_date" class="form-control" value="<?php echo htmlspecialchars($historyFilters['to_date']); ?>" aria-label="History to date">
                        <select name="history_leave_type_id" class="form-control">
                            <option value="">All leave types</option>
                            <?php foreach ($historyLeaveTypes as $leaveType): ?>
                                <option value="<?php echo (int)$leaveType['leave_type_id']; ?>" <?php echo ($historyFilters['leave_type_id'] === (int)$leaveType['leave_type_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($leaveType['leave_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="history_status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="Approved" <?php echo ($historyFilters['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo ($historyFilters['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <input type="text" name="history_search" class="form-control" placeholder="Search employee or reason" value="<?php echo htmlspecialchars($historyFilters['search']); ?>" aria-label="Search history records">
                        <button type="submit" class="action-btn btn-approve">Apply</button>
                        <a href="?page=leave_approvals&employee_page=<?php echo $employeePage; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>" class="action-btn action-btn--light">Clear</a>
                    </form>

                    <div class="quick-range-links mb-3">
                        <a href="?page=leave_approvals&employee_page=<?php echo $employeePage; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&history_from_date=<?php echo date('Y-m-01'); ?>&history_to_date=<?php echo date('Y-m-t'); ?>" class="action-btn action-btn--light">This month</a>
                        <a href="?page=leave_approvals&employee_page=<?php echo $employeePage; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&history_from_date=<?php echo date('Y-m-01', strtotime('first day of previous month')); ?>&history_to_date=<?php echo date('Y-m-t', strtotime('last day of previous month')); ?>" class="action-btn action-btn--light">Last month</a>
                        <a href="?page=leave_approvals&employee_page=<?php echo $employeePage; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&history_from_date=<?php echo date('Y-01-01'); ?>&history_to_date=<?php echo date('Y-12-31'); ?>" class="action-btn action-btn--light">This year</a>
                    </div>

                    <?php if ($totalHistoryRequests > 0): ?>
                        <div class="history-summary mb-2">Showing <?php echo $historyRangeStart; ?>–<?php echo $historyRangeEnd; ?> of <?php echo $totalHistoryRequests; ?> records</div>
                    <?php endif; ?>

                    <?php if (empty($historyRequests)): ?>
                        <div class="alert alert-info mb-0"><?php echo $historyHasAnyFilter ? 'No history records match your filters.' : 'No history records found.'; ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="approvals-table" id="leaveHistoryTable">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historyRequests as $req): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($req['full_name'] ?? $req['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($req['leave_type_name']); ?></td>
                                            <td><?php echo Helper::formatDate($req['start_date']); ?></td>
                                            <td><?php echo Helper::formatDate($req['end_date']); ?></td>
                                            <td><?php echo rtrim(rtrim(number_format((isset($req['total_days']) ? $req['total_days'] : (strtotime($req['end_date'])-strtotime($req['start_date']))/86400+1), 2), '0'), '.'); ?></td>
                                            <td><?php echo LeaveAbsenceHelper::getLeaveStatusBadge($req['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php render_pagination('history_page', $historyPage, $totalHistoryPages, [
                            'employee_page' => $employeePage,
                            'leave_page' => $leavePage,
                            'employee_page_size' => $recordsPerPage,
                            'employee_search' => $employeeSearch,
                            'history_from_date' => $historyFilters['from_date'],
                            'history_to_date' => $historyFilters['to_date'],
                            'history_leave_type_id' => $historyFilters['leave_type_id'],
                            'history_status' => $historyFilters['status'],
                            'history_search' => $historyFilters['search'],
                            'history_page_size' => $historyPageSize,
                        ]); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('approveModal')">&times;</span>
            <h3>Approve Leave Request</h3>
            <form id="approveForm" data-skip="true" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?page=leave_approvals" method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="leave_request_id" id="approveRequestId">
                <input type="hidden" name="employee_page" value="<?php echo $employeePage; ?>">
                <input type="hidden" name="leave_page" value="<?php echo $leavePage; ?>">
                <input type="hidden" name="employee_page_size" value="<?php echo $recordsPerPage; ?>">
                <input type="hidden" name="employee_search" value="<?php echo htmlspecialchars($employeeSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <div style="margin: 15px 0;">
                    <label>Remarks (optional):</label>
                    <textarea name="remarks"></textarea>
                </div>
                <button type="submit" class="action-btn btn-approve">Approve</button>
                <button type="button" class="action-btn" onclick="closeModal('approveModal')" style="background: #95a5a6;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('rejectModal')">&times;</span>
            <h3>Reject Leave Request</h3>
            <form id="rejectForm" data-skip="true" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?page=leave_approvals" method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="leave_request_id" id="rejectRequestId">
                <input type="hidden" name="employee_page" value="<?php echo $employeePage; ?>">
                <input type="hidden" name="leave_page" value="<?php echo $leavePage; ?>">
                <input type="hidden" name="employee_page_size" value="<?php echo $recordsPerPage; ?>">
                <input type="hidden" name="employee_search" value="<?php echo htmlspecialchars($employeeSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <div style="margin: 15px 0;">
                    <label>Rejection Reason:</label>
                    <textarea name="remarks" required></textarea>
                </div>
                <button type="submit" class="action-btn btn-reject">Reject</button>
                <button type="button" class="action-btn" onclick="closeModal('rejectModal')" style="background: #95a5a6;">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Employee Balance Modal -->
    <div id="balanceModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('balanceModal')">&times;</span>
            <h3 id="balanceModalTitle">Employee Leave Balances</h3>
            <div id="balanceModalBody">
                <p>Loading leave balances...</p>
            </div>
            <div class="modal-button-group">
                <button type="button" class="action-btn" onclick="closeModal('balanceModal')" style="background: #95a5a6;">Close</button>
            </div>
        </div>
    </div>

    <script>
    // Use absolute module paths to avoid dirname() ambiguity when pages are included
    window.__TA_CONFIG = {
        balanceApiUrl: <?php echo json_encode((ta_base_path() ?: '') . '/app/api/leave/get_leave_balance.php'); ?>,
        provisionUrl: <?php echo json_encode((ta_base_path() ?: '') . '/app/api/leave/provision_leave_balances.php'); ?>,
        approveLeaveUrl: <?php echo json_encode((ta_base_path() ?: '') . '/app/api/leave/approve_leave_head.php'); ?>,
        leavePage: <?php echo (int) $leavePage; ?>,
        recordsPerPage: <?php echo (int) $recordsPerPage; ?>
    };
    </script>
<script src="assets/js/leave-approvals.js"></script>
