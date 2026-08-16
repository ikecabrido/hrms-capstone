<aside class="sidebar" style="
    height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    direction:rtl;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
">

    <div style="direction:ltr;">
        <?php require __DIR__ . '/sidebar-data.php'; ?>

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
                <a href="index.php?url=user-profile" class="menu-link">
                    <i class="fa-regular fa-user"></i>
                    My Profile
                </a>
            </li>
            <li>
                <a href="index.php?url=attendance" class="menu-link">
                    <i class="fa-regular fa-clock"></i>
                    Attendance
                </a>
            </li>
            <li>
                <a href="index.php?url=leave-request" class="menu-link">
                    <i class="fa-regular fa-calendar"></i>
                    Leave 
                </a>
            </li>
            <li>
                <a href="index.php?url=payroll" class="menu-link">
                    <i class="fa-solid fa-money-bill"></i>
                    Payroll
                </a>
            </li>
            <li>
                <a href="index.php?url=benefits-and-government-contribution" class="menu-link">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    Benefits & Contributions
                </a>
            </li>
            <div class="separator"></div>
            <h3>Employee Development</h3>
            <li>
                <a href="index.php?url=performance" class="menu-link">
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
                <a href="index.php?url=complaint" class="menu-link">
                    <i class="fa-regular fa-message"></i>
                    Complaints
                </a>
            </li>
            <li>
                <a href="index.php?url=grievance" class="menu-link">
                    <i class="fa-solid fa-scale-balanced"></i>
                    Grievances
                </a>
            </li>
            <li>
                <a href="index.php?url=resignation" class="menu-link">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                    Resignation Request
                </a>
            </li>
            <li>
                <a href="index.php?url=online-meeting" class="menu-link">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                    Online Meeting
                </a>
            </li>
        </ul>
    </div>
</aside>