<?php
/**
 * Leave Management Page
 * Department heads and HR can review leave requests and view employee leave balances
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/LeaveController.php';
require_once __DIR__ . '/../app/models/Leave.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/LeaveAbsenceHelper.php';
require_once __DIR__ . '/../app/core/Session.php';

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

if (!$authenticated) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

// Only the time module approver roles can access this page
if (!in_array($role, ['time', 'HR_ADMIN', 'DEPARTMENT_HEAD'], true)) {
    header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    exit;
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
$employeeSearch = trim($_REQUEST['employee_search'] ?? '');

$employeeOffset = ($employeePage - 1) * $recordsPerPage;
$leaveOffset = ($leavePage - 1) * $recordsPerPage;

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
    $is_hr = in_array($role, ['time', 'HR_ADMIN'], true);
    $is_department_head = $role === 'DEPARTMENT_HEAD';
    $submittedEmployeePageSize = max(5, min(50, (int)($_POST['employee_page_size'] ?? $recordsPerPage)));
    $submittedEmployeeSearch = trim($_POST['employee_search'] ?? $employeeSearch);

    // Debug logging
    error_log("DEBUG: POST received - Action: $action, LeaveID: $leave_request_id, User: $user_id, Role: $role");

    if ($leave_request_id && $action === 'approve') {
        $result = $leaveController->approve($leave_request_id, $user_id, $is_hr, $remarks);
        if ($result['success']) {
            $_SESSION['flash_message'] = "Leave request approved successfully!";
            $_SESSION['flash_type'] = "success";
            header("Location: " . $_SERVER['PHP_SELF'] . "?employee_page={$employeePage}&leave_page={$leavePage}&employee_page_size={$submittedEmployeePageSize}&employee_search=" . urlencode($submittedEmployeeSearch));
            exit;
        }

        $message = $result['message'] ?? 'Failed to process approval.';
        $messageType = "error";
    } elseif ($leave_request_id && $action === 'reject') {
        if (empty($remarks)) {
            $message = "Rejection reason is required.";
            $messageType = "error";
        } else {
            $result = $leaveController->reject($leave_request_id, $user_id, $remarks);
            if ($result['success']) {
                $_SESSION['flash_message'] = "Leave request rejected.";
                $_SESSION['flash_type'] = "warning";
                header("Location: " . $_SERVER['PHP_SELF'] . "?employee_page={$employeePage}&leave_page={$leavePage}&employee_page_size={$submittedEmployeePageSize}&employee_search=" . urlencode($submittedEmployeeSearch));
                exit;
            }
            $message = $result['message'] ?? 'Failed to process rejection.';
            $messageType = "error";
        }
    }
}


$current_page = 'leave_approvals.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
$page_title = 'Leave Management';
$page_subtitle = 'Review requests and view employee leave balances in one place';
$page_icon = 'fa-file-signature';
$page_head_extra = "<link rel=\"icon\" href=\"assets/images/Bestlink College of the Philippines.jpeg\" type=\"image/jpeg\">\n<link rel=\"stylesheet\" href=\"assets/css/style.css\">\n<link rel=\"stylesheet\" href=\"assets/css/dashboard.css\">\n<link rel=\"stylesheet\" href=\"assets/css/hr-template.css\">\n<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback\">\n<script src=\"assets/js/mobile-responsive.js\" defer></script>";
?>

<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layout/content_header.php'; ?>

<link rel="stylesheet" href="assets/css/leave-approvals.css">

    <div class="card shadow-sm border-0">
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
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap" style="gap: 10px;">
                <form method="GET" class="d-flex flex-wrap" style="gap: 10px; align-items: center; margin: 0;">
                    <input type="hidden" name="leave_page" value="<?php echo $leavePage; ?>">
                    <input type="hidden" name="employee_page_size" value="<?php echo $recordsPerPage; ?>">
                    <input type="text" name="employee_search" class="form-control" placeholder="Search employees..." value="<?php echo htmlspecialchars($employeeSearch); ?>" style="min-width:240px;">
                    <button type="submit" class="action-btn btn-approve" style="padding: 10px 18px;">Search</button>
                    <a href="leave_approvals.php?employee_page_size=<?php echo $recordsPerPage; ?>&leave_page=<?php echo $leavePage; ?>&employee_search=" class="action-btn" style="background: #ffffff; color: #0066cc; padding: 10px 18px; border: 1px solid #cce4ff;">Clear</a>
                </form>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap: wrap;">
                    <label for="employeePageSize" style="margin:0; font-weight:600;">Page size:</label>
                    <select id="employeePageSize" class="form-control" onchange="changeEmployeePageSize(this.value)" style="min-width:100px;">
                        <?php foreach ([5, 10, 15, 20, 30] as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $recordsPerPage === $size ? 'selected' : ''; ?>><?php echo $size; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="action-btn btn-approve" type="button" onclick="provisionLeaveBalances()" style="padding: 10px 18px;">Provision Balances for All Active Employees</button>
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
                <div class="pagination mb-4">
                    <?php if ($employeePage > 1): ?>
                        <a href="?employee_page=<?php echo $employeePage - 1; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&employee_search=<?php echo urlencode($employeeSearch); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    <span class="active">Employee Page <?php echo $employeePage; ?> of <?php echo $totalEmployeePages; ?></span>
                    <?php if ($employeePage < $totalEmployeePages): ?>
                        <a href="?employee_page=<?php echo $employeePage + 1; ?>&leave_page=<?php echo $leavePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&employee_search=<?php echo urlencode($employeeSearch); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section-header">
                <h4>Pending Leave Requests</h4>
                <p>Review request approvals alongside employee leave balances.</p>
            </div>

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
                </div>
                <div class="pagination">
                    <?php if ($leavePage > 1): ?>
                        <a href="?leave_page=<?php echo $leavePage - 1; ?>&employee_page=<?php echo $employeePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&employee_search=<?php echo urlencode($employeeSearch); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    <span class="active">Leave Page <?php echo $leavePage; ?> of <?php echo $totalLeavePages; ?></span>
                    <?php if ($leavePage < $totalLeavePages): ?>
                        <a href="?leave_page=<?php echo $leavePage + 1; ?>&employee_page=<?php echo $employeePage; ?>&employee_page_size=<?php echo $recordsPerPage; ?>&employee_search=<?php echo urlencode($employeeSearch); ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('approveModal')">&times;</span>
            <h3>Approve Leave Request</h3>
            <form id="approveForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
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
            <form id="rejectForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
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
    window.__TA_CONFIG = {
        balanceApiUrl: <?php echo json_encode(dirname($_SERVER['SCRIPT_NAME']) . '/../app/api/get_leave_balance.php'); ?>,
        provisionUrl: <?php echo json_encode(dirname($_SERVER['SCRIPT_NAME']) . '/../app/api/provision_leave_balances.php'); ?>,
        leavePage: <?php echo (int) $leavePage; ?>,
        recordsPerPage: <?php echo (int) $recordsPerPage; ?>
    };
</script>
<script src="assets/js/leave-approvals.js"></script>
<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>


