<?php
// Notifications fragment for Time module (hrms-capstone)
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../core/TimeDatabase.php';
require_once __DIR__ . '/../models/EmployeeShift.php';
require_once __DIR__ . '/../models/Leave.php';

try {
    $db = TimeDatabase::getInstance()->getConnection();
    $employeeShiftModel = new EmployeeShift($db);
    $employeesWithoutShift = $employeeShiftModel->getEmployeesWithoutShift();
    $employeesWithoutShiftCount = count($employeesWithoutShift);
    $employeesNearTermination = $employeeShiftModel->getEmployeesNearTermination(7, 5);
    $employeesNearTerminationCount = count($employeesNearTermination);
    $leaveModel = new Leave();
    $pendingLeaveCount = $leaveModel->countForHRApproval();
} catch (Exception $e) {
    $employeesWithoutShift = [];
    $employeesWithoutShiftCount = 0;
    $employeesNearTermination = [];
    $employeesNearTerminationCount = 0;
    $pendingLeaveCount = 0;
}

ob_start();
?>
    <!-- Manage Shifts Alert (plain text, module logo only; clickable) -->
    <?php if ($employeesWithoutShiftCount > 0): ?>
    <li class="notif-item unread" data-notif-id="shift_assignment" style="padding:8px;border-bottom:1px solid #eee;">
        <a href="?page=shifts" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
            <div class="notif-icon" style="background:transparent;color:#000;width:20px;height:20px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-user-clock" style="font-size:14px;color:#000;"></i>
            </div>
            <div>
                <div>Shift Assignment Required</div>
                <div style="font-size:12px;"><?php echo $employeesWithoutShiftCount; ?> employee<?php echo $employeesWithoutShiftCount>1? 's' : ''; ?> need shift assignment.</div>
            </div>
        </a>
    </li>
    <?php endif; ?>

    <!-- Near Termination Alert (plain text, module logo only; clickable) -->
    <li class="notif-item <?php echo $employeesNearTerminationCount>0 ? 'unread' : ''; ?>" data-notif-id="near_termination" style="padding:8px;border-bottom:1px solid #eee;">
        <a href="?page=absence_late_management" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
            <div class="notif-icon" style="background:transparent;color:#000;width:20px;height:20px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-user-slash" style="font-size:14px;color:#000;"></i>
            </div>
            <div>
                <div>Near Termination</div>
                <div style="font-size:12px;"><?php echo $employeesNearTerminationCount; ?> employee<?php echo $employeesNearTerminationCount>1? 's' : ''; ?> have alert status.</div>
            </div>
        </a>
    </li>
    <?php if ($pendingLeaveCount > 0): ?>
    <!-- Leave Requests Alert -->
    <li class="notif-item unread" data-notif-id="leave_requests" style="padding:8px;border-bottom:1px solid #eee;">
        <a href="?page=leave_requests" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
            <div class="notif-icon" style="background:transparent;color:#000;width:20px;height:20px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-file-invoice" style="font-size:14px;color:#000;"></i>
            </div>
            <div>
                <div>Leave Requests</div>
                <div style="font-size:12px;">You have <?php echo $pendingLeaveCount; ?> pending leave request<?php echo $pendingLeaveCount>1? 's' : ''; ?>.</div>
            </div>
        </a>
    </li>
    <?php endif; ?>
<?php
echo ob_get_clean();
