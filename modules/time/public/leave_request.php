<?php
/**
 * Leave Request Page
 * Employees can submit leave requests for approval
 */

require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/LeaveController.php';
require_once __DIR__ . '/../app/models/Employee.php';
require_once __DIR__ . '/../app/models/Leave.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check if user is authenticated and is employee
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

if (!AuthController::hasRole('EMPLOYEE')) {
    header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
    exit;
}

$user_id = AuthController::getCurrentUserId();
$employeeModel = new Employee();
$leaveModel = new Leave();

$employee = $employeeModel->getByUserId($user_id);
$employee_id = $employee['employee_id'];

$message = "";
$messageType = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'submit_request') {
        $leave_type_id = (int)$_POST['leave_type_id'] ?? 0;
        $start_date = Helper::sanitize($_POST['start_date'] ?? '');
        $end_date = Helper::sanitize($_POST['end_date'] ?? '');
        $reason = Helper::sanitize($_POST['reason'] ?? '');

        // Validation
        $errors = [];
        if (!$leave_type_id) $errors[] = "Leave type is required";
        if (!$start_date) $errors[] = "Start date is required";
        if (!$end_date) $errors[] = "End date is required";
        if (strtotime($end_date) < strtotime($start_date)) $errors[] = "End date must be after start date";

        if (empty($errors)) {
            $total_days = Helper::calculateWorkingDays($start_date, $end_date);

            $data = [
                'employee_id' => $employee_id,
                'leave_type_id' => $leave_type_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
                'total_days' => $total_days
            ];

            if ($leaveModel->createRequest($data)) {
                $message = "Leave request submitted successfully! Waiting for department head approval.";
                $messageType = "success";
            } else {
                $message = "Failed to submit leave request. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = implode("<br>", $errors);
            $messageType = "error";
        }
    }
}

// Get leave types using Database class
require_once __DIR__ . '/../../../database/db.php';
$database = Database::getInstance();
$db = $database->getConnection();
$query = "SELECT * FROM ta_leave_types WHERE is_deductible = 1 ORDER BY leave_type_name";
$stmt = $db->prepare($query);
$stmt->execute();
$leaveTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$page_title = 'Leave Request';
$page_subtitle = 'Submit a leave request for approval by your department head and HR administration';
$page_icon = 'fa-plus-circle';
$page_head_extra = <<<HTML
<link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/adminlte-overrides.css">
<script src="../assets/js/mobile-responsive.js" defer></script>
<link rel="stylesheet" href="../assets/css/leave-request.css">
HTML;
$page_footer_extra = <<<HTML
<script src="../assets/js/preloader.js"></script>
HTML;
?>
<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layout/content_header.php'; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="page-content-container">
                <form method="POST" class="form-section">
                    <div class="form-section-title">
                        <h3>Request Details</h3>
                    </div>

                    <input type="hidden" name="action" value="submit_request">

                    <div class="form-group">
                        <label>Leave Type *</label>
                        <select name="leave_type_id" required>
                            <option value="">-- Select Leave Type --</option>
                            <?php foreach ($leaveTypes as $type): ?>
                                <option value="<?php echo $type['leave_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['leave_type_name']); ?> (<?php echo $type['days_per_year']; ?> days/year)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" required>
                        </div>

                        <div class="form-group">
                            <label>End Date *</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Reason for Leave</label>
                        <textarea name="reason" placeholder="Please provide a brief reason for your leave request (optional)..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Leave Request</button>
                </form>

                <div class="info-box">
                    <h3>Important Information</h3>
                    <ul>
                        <li>Leave requests are subject to approval by your department head</li>
                        <li>HR administration will conduct final review of all leave requests</li>
                        <li>Ensure your start and end dates do not exceed your available leave balance</li>
                        <li>You will receive notification once your request has been processed</li>
                        <li>For urgent requests, please contact your department head directly</li>
                    </ul>
                </div>
            </div>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>



