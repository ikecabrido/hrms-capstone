<?php
/**
 * Sidebar Navigation Component
 * Displays role-based navigation menu
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page and role
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'EMPLOYEE';
?>

<!-- NOTE: Inline sidebar styles were intentionally removed so the module
    uses the centralized `time_attendance/assets/css/dashboard.css` for layout
     and color theming. If needed, add scoped overrides to that stylesheet.
 -->

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" onclick="toggleSidebar()" role="button" title="Toggle Menu" style="font-size: 20px;">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo basename($_SERVER['PHP_SELF']) === 'employee_dashboard.php' ? 'employee_dashboard.php' : 'dashboard.php'; ?>" class="nav-link">Home</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Live Clock -->
        <li class="nav-item">
            <div class="nav-link" id="clock">--:--:--</div>
        </li>

        <!-- Fullscreen Toggle -->
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" role="button" title="Toggle Fullscreen" style="font-size: 16px;">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

        <!-- Dark Mode Toggle -->
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" id="darkToggle" role="button" title="Toggle Dark Mode" style="font-size: 16px;">
                <i class="fas fa-moon" id="themeIcon"></i>
            </a>
        </li>
    </ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<aside class="main-sidebar" id="mainSidebar">
    <!-- Brand Logo -->
    <a href="<?php echo basename($_SERVER['PHP_SELF']) === 'employee_dashboard.php' ? 'employee_dashboard.php' : 'dashboard.php'; ?>" class="brand-link">
        <img src="../bcp-logo2.png" alt="BCP Logo" class="brand-image" />
        <span class="brand-text">BCP Bulacan</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?php 
                    $role = $current_role === 'time' ? 'HR' : 'User';
                    $userName = htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['email'] ?? 'User');
                    echo $role . ' ' . $userName;
                    ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav>
            <ul class="nav-sidebar">
                <!-- Dashboard Section -->
                <li class="nav-item">
                    <?php if ($current_role === 'time'): ?>
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt animation__wobble"></i>
                            <p>Dashboard</p>
                        </a>
                    <?php else: ?>
                        <a href="employee_dashboard.php" class="nav-link <?php echo $current_page === 'employee_dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt animation__wobble"></i>
                            <p>My Dashboard</p>
                        </a>
                    <?php endif; ?>
                </li>

                <!-- Absence & Late Appeals (Employee Only) -->
                <?php if ($current_role !== 'time'): ?>
                    <li class="nav-item">
                        <a href="my_absence_appeals.php" class="nav-link <?php echo $current_page === 'my_absence_appeals.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-times animation__wobble"></i>
                            <p>My Absence & Late Appeals</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- QR & Attendance Section (HR Only) -->
                <?php if ($current_role === 'time'): ?>
                    <li class="nav-item">
                        <a href="qr_display_kiosk.php" class="nav-link <?php echo $current_page === 'qr_display_kiosk.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-qrcode animation__wobble"></i>
                            <p>QR Kiosk</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="approve_attendance.php" class="nav-link <?php echo $current_page === 'approve_attendance.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-check-circle animation__wobble"></i>
                            <p>Approve Manual Time</p>
                        </a>
                    </li>
                    <!-- Biometric features removed from sidebar per UI update -->
                <?php endif; ?>

                <!-- Calendar/Attendance -->
                <?php if (!($current_role === 'time')): ?>
                    <li class="nav-item">
                        <a href="calendar.php" class="nav-link <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-alt animation__wobble"></i>
                            <p>Calendar View</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Shift Management (HR Only) -->
                <?php if ($current_role === 'time'): ?>
                    <li class="nav-item">
                        <a href="shifts.php" class="nav-link <?php echo $current_page === 'shifts.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-clock animation__wobble"></i>
                            <p>Manage Shifts</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="schedule_calendar.php" class="nav-link <?php echo $current_page === 'schedule_calendar.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar animation__wobble"></i>
                            <p>Schedule Calendar</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="holidays.php" class="nav-link <?php echo $current_page === 'holidays.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-alt animation__wobble"></i>
                            <p>Holidays</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Leave Management Section -->
                <?php if ($current_role === 'time'): ?>
                    <li class="nav-item">
                        <a href="leave_approvals.php" class="nav-link <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-file-alt animation__wobble"></i>
                            <p>Leave Management</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="absence_late_management.php" class="nav-link <?php echo $current_page === 'absence_late_management.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-times animation__wobble"></i>
                            <p>Absence & Late Management</p>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="leave_request.php" class="nav-link <?php echo $current_page === 'leave_request.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-plus-circle animation__wobble"></i>
                            <p>Submit Request</p>
                        </a>
                    </li>
                    <?php if ($current_role === 'DEPARTMENT_HEAD'): ?>
                        <li class="nav-item">
                            <a href="leave_approvals.php" class="nav-link <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-file-alt animation__wobble"></i>
                                <p>Leave Management</p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Logout -->
                <li class="nav-header">SETTINGS</li>
                <li class="nav-item">
                            <a href="javascript:void(0);" id="logoutLink" class="nav-link" style="color: #e74c3c;">
                                <i class="nav-icon fas fa-sign-out-alt animation__wobble"></i>
                                <p>Logout</p>
                            </a>
                </li>
            </ul>
        </nav>
    </div>
    <!-- /.sidebar -->
</aside>

<script src="../assets/js/sidebar.js"></script>
