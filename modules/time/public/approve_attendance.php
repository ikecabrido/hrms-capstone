<?php
/**
 * Attendance Approval Page - Time & Attendance System
 * Review and approve pending manual attendance entries
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
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

$attendanceModel = new Attendance();
$auditLog = new AuditLog();
$user_id = AuthController::getCurrentUserId();

$message = "";
$messageType = "";

// Handle approval
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST['action'] ?? '');
    $attendance_id = (int)($_POST['attendance_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'approve' && $attendance_id > 0) {
        if ($attendanceModel->approve($attendance_id, $user_id, $remarks)) {
            $message = "Attendance record approved successfully!";
            $messageType = "success";
            $auditLog->log('ATTENDANCE_APPROVED', $user_id, null, $attendance_id, 
                ['remarks' => $remarks], 'SUCCESS');
        } else {
            $message = "Failed to approve attendance record.";
            $messageType = "error";
        }
    }
}

// Get all pending approvals
$pendingApprovals = $attendanceModel->getPendingApprovals(1000);

$current_page = 'approve_attendance.php';
$current_role = $_SESSION['role'] ?? 'HR_ADMIN';
$page_title = 'Approve Manual Time';
$page_subtitle = 'Review and approve pending manual attendance entries';
$page_head_extra = "<link rel=\"icon\" href=\"../Bestlink College of the Philippines.jpeg\" type=\"image/jpeg\">\n<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css\">\n<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback\">\n<link rel=\"stylesheet\" href=\"../../assets/dist/css/adminlte.min.css\">\n<link rel=\"stylesheet\" href=\"../../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css\">\n<link rel=\"stylesheet\" href=\"../assets/css/style.css\">\n<link rel=\"stylesheet\" href=\"../assets/css/dashboard.css\">\n<link rel=\"stylesheet\" href=\"../assets/css/adminlte-overrides.css\">\n<link rel=\"stylesheet\" href=\"../assets/css/hr-template.css\">\n<script src=\"../assets/js/mobile-responsive.js\" defer></script>";
?>
<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<?php require_once __DIR__ . '/../layout/sidebar.php'; ?>
<?php $page_title = 'Approve Manual Time'; $page_subtitle = 'Review and approve manual time entries'; $page_icon = 'fa-check-circle'; ?>
<?php require_once __DIR__ . '/../layout/content_header.php'; ?>

<link rel="stylesheet" href="../assets/css/approve-attendance.css">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="container glass-panel">
                <?php if (empty($pendingApprovals)): ?>
                    <div class="alert" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                        <strong>All Clear!</strong> No pending attendance records to approve.
                    </div>
                <?php else: ?>
                    <table class="approvals-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApprovals as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo Helper::formatDate($record['attendance_date']); ?></td>
                                    <td><?php echo Helper::formatTime($record['time_in']); ?></td>
                                    <td><?php echo Helper::formatTime($record['time_out'] ?? 'N/A'); ?></td>
                                    <td><span class="badge badge-info"><?php echo $record['recorded_by']; ?></span></td>
                                    <td>
                                        <button class="action-btn btn-approve" onclick="openApproveModal(<?php echo $record['attendance_id']; ?>, '<?php echo htmlspecialchars($record['full_name']); ?>', '<?php echo Helper::formatDate($record['attendance_date']); ?>', '<?php echo Helper::formatTime($record['time_in']); ?>', '<?php echo Helper::formatTime($record['time_out'] ?? 'N/A'); ?>')">Approve</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="approval-pagination" id="approvalPagination" aria-label="Manual time pagination">
                        <span class="approval-pagination-info" id="approvalPaginationInfo"></span>
                        <div class="approval-page-buttons" id="approvalPageButtons"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('approveModal')">&times;</span>
            <h3>Approve Attendance Record</h3>
            
            <div class="info-box" id="recordInfo"></div>

            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="attendance_id" id="approveRecordId">
                
                <div class="form-group">
                    <label>Approval Remarks (Optional)</label>
                    <textarea name="remarks" placeholder="Add any notes about this approval..."></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="action-btn btn-approve" style="flex: 1;">Approve</button>
                    <button type="button" class="action-btn" onclick="closeModal('approveModal')" style="background: #95a5a6; color: white; flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/approve-attendance.js"></script>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>
