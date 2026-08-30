<?php
include_once __DIR__ . '/../../../auth/session.php';
include __DIR__ . '/../classes/Employee.php';
$employeeClass = new Employee();

?>

<aside class="sidebar">
    <?php
    // Load time DB and models to provide module notifications
    require_once __DIR__ . '/../app/core/TimeDatabase.php';
    require_once __DIR__ . '/../app/models/EmployeeShift.php';

    try {
        $database = TimeDatabase::getInstance();
        $db = $database->getConnection();
        $employeeShiftModel = new EmployeeShift($db);
        $employeesWithoutShift = $employeeShiftModel->getEmployeesWithoutShift();
        $employeesWithoutShiftCount = count($employeesWithoutShift);
        $employeesNearTermination = $employeeShiftModel->getEmployeesNearTermination(7, 5);
        $employeesNearTerminationCount = count($employeesNearTermination);
    } catch (Exception $e) {
        $employeesWithoutShift = [];
        $employeesWithoutShiftCount = 0;
        $employeesNearTermination = [];
        $employeesNearTerminationCount = 0;
    }
    ?>
    <div class="school-logo">
        <img src="assets/bcp-logo.png" alt="School Logo">
        <div class="sidebar-icons">

            <!-- Bell Icon + Notification Dropdown -->
            <div class="icon-wrapper" id="bellWrapper">
                <i class="fa-regular fa-bell" id="bellBtn"></i>
                <span class="notif-badge hidden" id="notifBadge"></span>
                <div class="icon-dropdown" id="bellDropdown">
                <div class="dropdown-header">
                        <span>Notifications</span>
                        <button class="mark-all-read">Mark all read</button>
                    </div>
                    <ul class="notif-list">
                        <?php if ($employeesWithoutShiftCount > 0): ?>
                            <li class="notif-item unread" style="padding:8px;border-bottom:1px solid #eee;">
                                <a href="?page=shifts" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#000;">
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

                        <li class="notif-item" style="padding:8px;border-bottom:1px solid #eee;">
                            <a href="?page=absence_late_management" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#000;">
                                <div class="notif-icon" style="background:transparent;color:#000;width:20px;height:20px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-user-slash" style="font-size:14px;color:#000;"></i>
                                </div>
                                <div>
                                    <div>Near Termination</div>
                                    <div style="font-size:12px;"><?php echo $employeesNearTerminationCount; ?> flagged</div>
                                </div>
                            </a>

                            <?php if ($employeesNearTerminationCount > 0): ?>
                                <div style="margin-top:6px;font-size:13px;"><?php echo $employeesNearTerminationCount; ?> employee<?php echo $employeesNearTerminationCount>1? 's' : ''; ?> have alert status.</div>
                            <?php else: ?>
                                <div style="margin-top:6px;font-size:13px;">No employees flagged for termination review.</div>
                            <?php endif; ?>
                        </li>

                    </ul>
                    <div class="dropdown-footer">
                        <a href="?page=notifications">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- User Icon + Profile Dropdown -->
            <div class="icon-wrapper" id="userWrapper">
                <i class="fa-regular fa-circle-user" id="userBtn"></i>
                <div class="icon-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-info">
                            <div class="dropdown-avatar">
                                <?= substr(htmlspecialchars($employeeClass->getEmployeeName()), 0, 1) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($employeeClass->getEmployeeName()) ?></strong>
                                <span><?= htmlspecialchars($employeeClass->getEmployeePosition()) ?></span>
                            </div>
                        </div>
                    </div>
                    <ul class="user-menu">
                        <li>
                            <a href="#"><i class="fa-regular fa-user"></i> Profile Settings</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-lock"></i> Change Password</a>
                        </li>
                        <li class="divider"></li>
                        <li>
                            <a href="../../auth/logout.php" class="signout-link">
                                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
        <div class="sidebar-header">
        <div class="user_avatar"><?= substr(htmlspecialchars($employeeClass->getEmployeeName()), 0, 1) ?></div>
        <h1 class="employee_name"><?= htmlspecialchars($employeeClass->getEmployeeName()) ?></h1>
        <p class="employee_position"><?= htmlspecialchars($employeeClass->getEmployeePosition()) ?></p>
    </div>
        <h2>Dashboard</h2>
    <ul>
        <?php $pageController->renderNav(); ?>
    </ul>
</aside>
