<aside class="sidebar">

    <!-- Logo + Icons -->
    <?php require __DIR__ . '/sidebar-logo-icon.php'; ?>

    <!-- Employee Information -->
    <?php require __DIR__ . '/sidebar-employee-info.php'; ?>

    <!-- Navigation -->
    <h2>Employee Portal</h2>
    <ul>
        <li>
            <a href="index.php?url=employee-dashboard" class="menu-link">
                <i class="fa-solid fa-home"></i>
                Dashboard
            </a>
        </li>
        <div class="separator"></div>
        <h3>Employee Services</h3>
        <li>
            <a href="?page=profile" class="menu-link">
                <i class="fa-regular fa-user"></i>
                My Profile
            </a>
        </li>
        <li>
            <a href="?page=attendance" class="menu-link">
                <i class="fa-regular fa-clock"></i>
                Attendance
            </a>
        </li>
        <li>
            <a href="?page=leave" class="menu-link">
                <i class="fa-regular fa-calendar"></i>
                Leave Management
            </a>
        </li>
        <li>
            <a href="?page=payroll" class="menu-link">
                <i class="fa-solid fa-money-bill"></i>
                Payroll
            </a>
        </li>
        <li>
            <a href="?page=benefits" class="menu-link">
                <i class="fa-solid fa-hand-holding-heart"></i>
                Benefits & Contributions
            </a>
        </li>
        <div class="separator"></div>
        <h3>Employee Development</h3>
        <li>
            <a href="?page=performance" class="menu-link">
                <i class="fa-solid fa-chart-line"></i>
                Performance Evaluation
            </a>
        </li>
        <li>
            <a href="?page=training" class="menu-link">
                <i class="fa-solid fa-graduation-cap"></i>
                Training & Seminars
            </a>
        </li>
        <div class="separator"></div>
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
        <li>
            <a href="?page=resignation" class="menu-link">
                <i class="fa-solid fa-person-walking-arrow-right"></i>
                Resignation Request
            </a>
        </li>
    </ul>

</aside>