<?php
/**
 * Sidebar Navigation Component
 * Displays role-based navigation menu with AdminLTE design
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page and role
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
$current_role = $_SESSION['role'] ?? 'EMPLOYEE';
?>

<style>
    /* AdminLTE Sidebar Styling */
    body.hold-transition.sidebar-mini.layout-fixed.layout-navbar-fixed.layout-footer-fixed {
        display: flex;
        flex-direction: column;
    }

    .wrapper {
        display: flex;
        min-height: 100vh;
        flex-direction: column;
    }

    .main-header.navbar {
        background: #fff;
        color: #333;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-bottom: 1px solid #dee2e6;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        height: 60px;
        padding: 0.5rem 0;
    }

    .main-header.navbar.navbar-dark {
        background: #2c3e50;
        color: white;
    }

    .navbar-nav {
        display: flex;
        flex-direction: row;
        list-style: none;
        padding: 0;
        margin: 0;
        align-items: center;
    }

    .navbar-nav .nav-item {
        margin: 0 15px;
    }

    .navbar-nav .nav-link {
        color: #333;
        text-decoration: none;
        padding: 0.5rem 0;
        transition: color 0.3s ease;
    }

    .navbar-dark .navbar-nav .nav-link {
        color: #ecf0f1;
    }

    .navbar-nav .nav-link:hover {
        color: #2c3e50;
    }

    .navbar-dark .navbar-nav .nav-link:hover {
        color: #3498db;
    }

    .navbar-nav.ml-auto {
        margin-left: auto !important;
    }

    .main-sidebar {
        width: 250px;
        background: #2c3e50;
        color: white;
        position: fixed;
        left: 0;
        top: 60px;
        height: calc(100vh - 60px);
        overflow-y: auto;
        z-index: 900;
        flex-direction: column;
        display: flex;
    }

    .main-sidebar.sidebar-dark-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    }

    .main-sidebar.elevation-4 {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .brand-link {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 15px 20px;
        background: rgba(0, 0, 0, 0.2);
        border-bottom: 2px solid #34495e;
        text-decoration: none;
        color: white;
        transition: background 0.3s ease;
        gap: 10px;
    }

    .brand-link:hover {
        background: rgba(0, 0, 0, 0.3);
    }

    .brand-image {
        width: 60px;
        height: 60px;
        object-fit: contain;
        border-radius: 4px;
    }

    .brand-text {
        font-size: 18px;
        font-weight: 300;
        color: #ecf0f1;
    }

    .sidebar {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .user-panel {
        padding: 15px 20px;
        border-bottom: 1px solid #34495e;
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .user-panel .image {
        width: 40px;
        height: 40px;
        background: #34495e;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ecf0f1;
        font-weight: bold;
    }

    .user-panel .info {
        flex: 1;
    }

    .user-panel .info a {
        color: #ecf0f1;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: block;
        transition: color 0.3s ease;
    }

    .user-panel .info a:hover {
        color: #3498db;
    }

    .nav-sidebar {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-sidebar .nav-item {
        display: block;
    }

    .nav-sidebar .nav-link {
        padding: 12px 20px;
        color: #ecf0f1;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        font-size: 13px;
    }

    .nav-sidebar .nav-link:hover {
        background: #34495e;
        border-left-color: #27ae60;
        padding-left: 18px;
    }

    .nav-sidebar .nav-link.active {
        background: #34495e;
        border-left-color: #27ae60;
        color: #27ae60;
        font-weight: 600;
    }

    .nav-icon {
        font-size: 14px;
        width: 20px;
        text-align: center;
    }

    .nav-link > p {
        margin: 0;
    }

    .nav-header {
        padding: 10px 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #bdc3c7;
        letter-spacing: 1px;
        margin-top: 10px;
    }

    .badge {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 600;
        margin-left: auto;
    }

    .badge-info {
        background: #3498db;
        color: white;
    }

    .content-wrapper {
        margin-left: 250px;
        margin-top: 60px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .content-header {
        background: white;
        padding: 20px;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 20px;
    }

    .content-header .m-0 {
        margin: 0;
        font-size: 28px;
        font-weight: 300;
        color: #333;
    }

    .main-content {
        padding: 20px;
        flex: 1;
    }

    @media (max-width: 768px) {
        .main-sidebar {
            position: fixed;
            left: 0;
            top: 60px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1040;
            height: calc(100vh - 60px);
        }

        .main-sidebar.open {
            transform: translateX(0);
        }

        .content-wrapper {
            margin-left: 0;
        }
    }
</style>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark" style="background: #2c3e50;">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" onclick="toggleSidebar()" role="button" title="Toggle Menu">
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
            <div class="nav-link" id="clock" style="cursor: default;">--:--:--</div>
        </li>

        <!-- Fullscreen Toggle -->
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="javascript:void(0);" role="button" title="Toggle Fullscreen">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

        <!-- Dark Mode Toggle -->
        <li class="nav-item">
            <a class="nav-link" href="javascript:void(0);" id="darkToggle" role="button" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="themeIcon"></i>
            </a>
        </li>
    </ul>
</nav>
<!-- /.navbar -->

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4" id="mainSidebar">
    <!-- Brand Logo -->
    <a href="<?php echo basename($_SERVER['PHP_SELF']) === 'employee_dashboard.php' ? 'employee_dashboard.php' : 'dashboard.php'; ?>" class="brand-link">
        <img src="../assets/pics/bcpLogo.png" alt="BCP Logo" class="brand-image elevation-3" style="opacity: 0.9" />
        <span class="brand-text font-weight-light">BCP Bulacan</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="image">
                <i class="fas fa-user-circle" style="font-size: 24px;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['email'] ?? 'User'); ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard Section -->
                <li class="nav-item">
                    <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    <?php else: ?>
                        <a href="employee_dashboard.php" class="nav-link <?php echo $current_page === 'employee_dashboard.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>My Dashboard</p>
                        </a>
                    <?php endif; ?>
                </li>

                <!-- QR & Attendance Section (HR Only) -->
                <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
                    <li class="nav-item">
                        <a href="qr_display_kiosk.php" class="nav-link <?php echo $current_page === 'qr_display_kiosk.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-qrcode"></i>
                            <p>QR Kiosk</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="approve_attendance.php" class="nav-link <?php echo $current_page === 'approve_attendance.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-check-circle"></i>
                            <p>Approve Manual Time</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Calendar/Attendance -->
                <?php if (!($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN')): ?>
                    <li class="nav-item">
                        <a href="calendar.php" class="nav-link <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Calendar View</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Shift Management (HR Only) -->
                <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
                    <li class="nav-item">
                        <a href="shifts.php" class="nav-link <?php echo $current_page === 'shifts.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-clock"></i>
                            <p>Manage Shifts</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Leave Management Section -->
                <li class="nav-header">LEAVE MANAGEMENT</li>
                <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
                    <li class="nav-item">
                        <a href="leave_approvals.php" class="nav-link <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Approve Leave Requests</p>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="leave_request.php" class="nav-link <?php echo $current_page === 'leave_request.php' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-plus-circle"></i>
                            <p>Submit Request</p>
                        </a>
                    </li>
                    <?php if ($current_role === 'DEPARTMENT_HEAD'): ?>
                        <li class="nav-item">
                            <a href="leave_approvals.php" class="nav-link <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-file-alt"></i>
                                <p>Approve Requests</p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Reports Section -->
                <li class="nav-header">REPORTS</li>
                <li class="nav-item">
                    <a href="export_dashboard.php?format=excel" class="nav-link">
                        <i class="nav-icon fas fa-download"></i>
                        <p>Export Dashboard</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-header">SETTINGS</li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link" style="color: #e74c3c;">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

<script>
    // Toggle Sidebar on Mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('mainSidebar');
        sidebar.classList.toggle('open');
    }

    // Close sidebar when clicking on a link (mobile)
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('mainSidebar');
                sidebar.classList.remove('open');
            }
        });
    });

    // Live Clock
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const clockElement = document.getElementById('clock');
        if (clockElement) {
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }

    updateClock();
    setInterval(updateClock, 1000);

    // Dark Mode Toggle
    const darkToggle = document.getElementById('darkToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    if (darkToggle) {
        darkToggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
            
            if (isDarkMode) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        });
    }

    // Load dark mode preference (default to light mode for time_attendance)
    window.addEventListener('load', function() {
        const darkModeSetting = localStorage.getItem('darkMode');
        const darkMode = darkModeSetting === 'true'; // Only true if explicitly set
        
        // Reset to light mode by default on each page load for time_attendance
        if (!darkModeSetting) {
            localStorage.setItem('darkMode', 'false');
        }
        
        if (darkMode) {
            document.body.classList.add('dark-mode');
            if (themeIcon) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
        }
    });
</script>

    /* Scrollbar styling for light mode */
    .sidebar::-webkit-scrollbar {
        width: 8px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .sidebar.collapsed {
        width: 70px;
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 101;
        background: #2c3e50;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }

    .sidebar.collapsed ~ .sidebar-toggle {
        left: 80px;
    }

    .sidebar-toggle:hover {
        background: #34495e;
        transform: scale(1.1);
    }

    .sidebar-brand {
        padding: 15px 20px;
        border-bottom: 2px solid #34495e;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 10px;
        overflow: hidden;
    }

    .sidebar-brand-logo {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
        object-fit: contain;
        border-radius: 4px;
        mix-blend-mode: screen;
        transition: width 0.3s ease, height 0.3s ease;
    }

    .sidebar-brand h3 {
        margin: 0;
        font-size: 16px;
        color: #ecf0f1;
        transition: opacity 0.3s ease;
        line-height: 1.3;
        flex: 1;
        min-width: 150px;
    }

    .sidebar-brand p {
        margin: 0;
        font-size: 11px;
        color: #bdc3c7;
        transition: opacity 0.3s ease;
        display: none;
    }

    .sidebar.collapsed .sidebar-brand h3,
    .sidebar.collapsed .sidebar-brand p {
        opacity: 0;
        display: none;
    }

    .sidebar.collapsed .sidebar-brand {
        padding: 10px 5px;
    }

    .sidebar.collapsed .sidebar-brand-logo {
        width: 50px;
        height: 50px;
        margin: auto;
    }

    .dark-mode-toggle {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 24px;
        color: #ecf0f1;
        transition: all 0.3s ease;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: rotate 0.5s ease-in-out;
    }

    .dark-mode-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: scale(1.15);
    }

    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .dark-mode-toggle.active {
        animation: rotate 0.5s ease-in-out;
    }

    .sidebar-brand-content {
        flex: 1;
    }

    .nav-section {
        padding: 15px 0;
        border-bottom: 1px solid #34495e;
    }

    .nav-section-title {
        padding: 10px 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #bdc3c7;
        letter-spacing: 1px;
        transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .nav-section-title {
        opacity: 0;
        display: none;
    }

    .nav-item {
        display: block;
        padding: 12px 20px;
        color: #ecf0f1;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        overflow: hidden;
        white-space: nowrap;
        font-size: 13px;
    }

    .sidebar.collapsed .nav-item {
        padding: 12px;
        text-indent: -9999px;
        text-align: center;
    }

    .sidebar.collapsed .nav-item::before {
        content: attr(data-icon);
        text-indent: 0;
        display: inline-block;
    }

    .nav-item:hover {
        background: #34495e;
        border-left-color: #27ae60;
        padding-left: 18px;
        transform: translateX(5px);
    }

    .sidebar.collapsed .nav-item:hover {
        padding-left: 12px;
        transform: scale(1.1);
    }

    .nav-item.active {
        background: #34495e;
        border-left-color: #27ae60;
        color: #27ae60;
        font-weight: 600;
        animation: slideInLeft 0.3s ease;
    }

    @keyframes slideInLeft {
        from {
            transform: translateX(-10px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .nav-section {
        animation: fadeIn 0.3s ease;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 15px 20px;
        border-top: 2px solid #34495e;
    }

    .user-info {
        margin-bottom: 12px;
    }

    .user-info strong {
        display: block;
        color: #ecf0f1;
        font-size: 13px;
        word-break: break-word;
    }

    .user-info span {
        display: block;
        color: #bdc3c7;
        font-size: 11px;
        margin-top: 4px;
    }

    .btn-logout {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 24px;
        transition: background 0.3s ease;
        margin: 10px auto 0;
    }

    .btn-logout:hover {
        background: #c0392b;
    }

    .btn-logout svg {
        width: 24px;
        height: 24px;
    }

    .main-content {
        margin-left: 250px;
        flex: 1;
        padding: 20px;
        transition: margin-left 0.3s ease, background-color 0.3s ease, color 0.3s ease;
    }

    .sidebar.collapsed ~ .main-content {
        margin-left: 70px;
    }

    .top-header {
        background: #34495e;
        color: white;
        padding: 15px 30px;
        margin-bottom: 20px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .top-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .breadcrumb {
        font-size: 12px;
        color: #bdc3c7;
    }

    .breadcrumb a {
        color: #3498db;
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            z-index: 1000 !important;
            transform: translateX(-100%) !important;
            height: 100vh !important;
            width: 250px !important;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2) !important;
            border-radius: 0 !important;
        }

        .sidebar.open {
            transform: translateX(0) !important;
        }

        .sidebar-toggle {
            display: block !important;
            position: fixed !important;
        }

        .main-content {
            margin-left: 0 !important;
            padding-top: 20px;
        }

        .sidebar-toggle:focus {
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }

        .sidebar-brand h3,
        .sidebar-brand p {
            word-break: break-word;
        }
    }

    @media (max-width: 480px) {
        .sidebar {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            z-index: 1000 !important;
            transform: translateX(-100%) !important;
            width: 280px !important;
            height: 100vh !important;
            max-width: 85vw !important;
        }

        .sidebar.open {
            transform: translateX(0) !important;
        }

        .main-content {
            margin-left: 0 !important;
            padding-top: 60px;
        }

        .sidebar-footer {
            position: static;
            border-top: 1px solid #34495e;
            padding-top: 10px;
            margin-top: auto;
        }

        .sidebar-toggle {
            display: block !important;
        }

        .nav-item {
            font-size: 14px;
            padding: 14px 16px;
        }

        .nav-section-title {
            font-size: 11px;
            padding: 12px 16px;
        }

        .sidebar-brand {
            padding: 12px 16px;
        }

        .sidebar-brand h3 {
            font-size: 15px;
            line-height: 1.3;
        }

        .sidebar-brand p {
            font-size: 11px;
        }

        .sidebar-brand-logo {
            width: 60px !important;
            height: 60px !important;
        }
    }
</style>

<button id="sidebarToggle" class="sidebar-toggle" title="Toggle Sidebar">☰</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="../bcp-logo2.png" alt="Bestlink Logo" class="sidebar-brand-logo">
        <div class="sidebar-brand-content">
            <h3>Bestlink College of the Philippines</h3>
            <p><?php echo ucfirst(strtolower(str_replace('_', ' ', $current_role))); ?></p>
        </div>
        <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
            <svg id="moonIcon" class="mode-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block;">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <svg id="sunIcon" class="mode-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        </button>
    </div>

    <div class="nav-section">
        <div class="nav-section-title"> My Dashboard</div>
        <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
            <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">HR Dashboard</a>
        <?php else: ?>
            <a href="employee_dashboard.php" class="nav-item <?php echo $current_page === 'employee_dashboard.php' ? 'active' : ''; ?>">My Stats & Performance</a>
        <?php endif; ?>
    </div>

    <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
        <div class="nav-section">
            <div class="nav-section-title"> Attendance Management</div>
            <a href="qr_display_kiosk.php" class="nav-item <?php echo $current_page === 'qr_display_kiosk.php' ? 'active' : ''; ?>">QR Kiosk</a>
            <a href="approve_attendance.php" class="nav-item <?php echo $current_page === 'approve_attendance.php' ? 'active' : ''; ?>">Approve Manual Time</a>
        </div>
    <?php else: ?>
        <div class="nav-section">
            <div class="nav-section-title"> Attendance</div>
            <a href="calendar.php" class="nav-item <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>">Calendar View</a>
        </div>
    <?php endif; ?>

    <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
        <div class="nav-section">
            <div class="nav-section-title"> Shift Management</div>
            <a href="shifts.php" class="nav-item <?php echo $current_page === 'shifts.php' ? 'active' : ''; ?>">Manage Shifts</a>
        </div>
    <?php endif; ?>

    <div class="nav-section">
        <div class="nav-section-title"> Leave Management</div>
        <?php if ($current_role === 'HR_ADMIN' || $current_role === 'SYSTEM_ADMIN'): ?>
            <a href="leave_approvals.php" class="nav-item <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">Approve Leave Requests</a>
        <?php else: ?>
            <a href="leave_request.php" class="nav-item <?php echo $current_page === 'leave_request.php' ? 'active' : ''; ?>">Submit Request</a>
            <?php if ($current_role === 'DEPARTMENT_HEAD'): ?>
                <a href="leave_approvals.php" class="nav-item <?php echo $current_page === 'leave_approvals.php' ? 'active' : ''; ?>">Approve Requests</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="nav-section">
        <div class="nav-section-title"> Export Data</div>
        <a href="export_dashboard.php?format=excel" class="nav-item">Export Dashboard (Excel)</a>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <strong><?php echo htmlspecialchars($_SESSION['email'] ?? 'User'); ?></strong>
            <span><?php echo ucfirst(strtolower(str_replace('_', ' ', $current_role))); ?></span>
        </div>
        <a href="logout.php" class="btn-logout" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 3v9"></path>
            </svg>
        </a>
    </div>
</div>

<script>
// Mobile Menu Management
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
let sidebarOverlay = document.querySelector('.sidebar-overlay');

// Create overlay if it doesn't exist
if (!sidebarOverlay) {
    sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    document.body.appendChild(sidebarOverlay);
}

// Toggle sidebar on mobile
function toggleSidebar() {
    sidebar.classList.toggle('open');
    sidebarOverlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : 'auto';
}

// Close sidebar on mobile
function closeSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = 'auto';
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
}

// Close sidebar when clicking overlay
sidebarOverlay.addEventListener('click', closeSidebar);

// Close sidebar when clicking on navigation links on mobile
const navItems = document.querySelectorAll('.nav-item');
navItems.forEach(item => {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            closeSidebar();
        }
    });
});

// Close sidebar on window resize if going to desktop
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});

// Keyboard accessibility - close on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && window.innerWidth <= 768) {
        closeSidebar();
    }
});

// Dark Mode Toggle
const darkModeToggle = document.getElementById('darkModeToggle');
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', function() {
        this.classList.add('active');
        const isDarkMode = document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);
        // Toggle SVG icons with animation
        const moonIcon = document.getElementById('moonIcon');
        const sunIcon = document.getElementById('sunIcon');
        if (isDarkMode) {
            moonIcon.style.display = 'none';
            sunIcon.style.display = 'block';
        } else {
            moonIcon.style.display = 'block';
            sunIcon.style.display = 'none';
        }
    });
}

// Load dark mode preference
window.addEventListener('load', function() {
    const darkMode = localStorage.getItem('darkMode') === 'true';
    if (darkMode) {
        document.body.classList.add('dark-mode');
        if (themeIcon) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }
    }
});
</script>
