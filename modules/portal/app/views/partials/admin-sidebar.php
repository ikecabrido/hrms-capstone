<aside class="sidebar">
    <?php require __DIR__ . '/sidebar-data.php'; ?>
    <!-- Logo + Icons -->
    <?php require __DIR__ . '/sidebar-logo-icon.php'; ?>

    <!-- Employee Information -->
    <?php require __DIR__ . '/sidebar-employee-info.php'; ?>

    <!-- Navigation -->
    <h2>Admin Portal</h2>

    <ul>

        <!-- DASHBOARD -->
        <li>
            <a href="index.php?url=admin-dashboard" class="menu-link">
                <i class="fa-solid fa-home"></i>
                Dashboard
            </a>
        </li>

        <div class="separator"></div>

        <!-- EMPLOYEE MANAGEMENT -->
        <h3>Employee Management</h3>

        <li>
            <a href="index.php?url=view-all-employees" class="menu-link">
                <i class="fa-solid fa-users"></i>
                View All Employees
            </a>
        </li>

        <li>
            <a href="index.php?url=view-all-attendance" class="menu-link">
                <i class="fa-regular fa-clock"></i>
                View All Attendance
            </a>
        </li>

        <li>
            <a href="index.php?url=user-account" class="menu-link">
                <i class="fa-solid fa-user-gear"></i>
                Manage User Accounts
            </a>
        </li>

        <div class="separator"></div>

        <!-- REQUEST MANAGEMENT -->
        <h3>Request Management</h3>

        <li>
            <a href="index.php?url=admin-leave-request" class="menu-link">
                <i class="fa-regular fa-calendar"></i>
                Leave Requests
            </a>
        </li>

        <li>
            <a href="?page=payroll-requests" class="menu-link">
                <i class="fa-solid fa-money-bill"></i>
                Payroll Requests
            </a>
        </li>

        <li>
            <a href="?page=benefits" class="menu-link">
                <i class="fa-solid fa-hand-holding-heart"></i>
                Benefits & Contributions
            </a>
        </li>

        <li>
            <a href="?page=resignation-requests" class="menu-link">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
                Resignation Requests
            </a>
        </li>

        <div class="separator"></div>

        <!-- PERFORMANCE & DEVELOPMENT -->
        <h3>Performance & Development</h3>

        <li>
            <a href="?page=performance" class="menu-link">
                <i class="fa-solid fa-chart-line"></i>
                Performance Evaluation
            </a>
        </li>

        <div class="separator"></div>

        <!-- EMPLOYEE RELATIONS -->
        <h3>Employee Relations</h3>

        <li>
            <a href="?page=complaints" class="menu-link">
                <i class="fa-regular fa-message"></i>
                Complaints
            </a>
        </li>

        <li>
            <a href="?page=grievances" class="menu-link">
                <i class="fa-solid fa-scale-balanced"></i>
                Grievances
            </a>
        </li>

        <div class="separator"></div>

        <!-- COMMUNICATION -->
        <h3>Communication</h3>

        <li>
            <a href="index.php?url=admin-online-meeting" class="menu-link">
                <i class="fa-solid fa-video"></i>
                Online Meetings
            </a>
        </li>

        <li>
            <a href="?page=notifications" class="menu-link">
                <i class="fa-regular fa-bell"></i>
                Notifications
            </a>
        </li>

        <li>
            <a href="?page=announcements" class="menu-link">
                <i class="fa-solid fa-bullhorn"></i>
                Announcements
            </a>
        </li>

        <div class="separator"></div>

        <!-- SYSTEM -->
        <h3>System</h3>

        <li>
            <a href="?page=backup-restore" class="menu-link">
                <i class="fa-solid fa-database"></i>
                Backup & Restore
            </a>
        </li>

    </ul>

</aside>