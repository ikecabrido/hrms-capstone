<aside id="mainSidebar" class="sidebar" style="
    width:260px;
    min-width:260px;
    height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    direction:rtl;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
    transition:
        width .35s ease,
        min-width .35s ease,
        margin-left .35s ease,
        padding .35s ease,
        border .35s ease;
">

    <div style="
        direction:ltr;
        width:260px;
        min-width:260px;
    ">

        <?php require __DIR__ . '/sidebar-data.php'; ?>

        <!-- Logo + Icons -->
        <?php require __DIR__ . '/sidebar-logo-icon.php'; ?>

        <!-- Employee Information -->
        <?php require __DIR__ . '/sidebar-employee-info.php'; ?>

        <!-- Navigation -->
        <h2>Employee Portal</h2>

        <ul>

            <li>
                <a href="index.php?url=employee-dashboard" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="separator"></div>

            <h3>Employee Services</h3>

            <li>
                <a href="index.php?url=user-profile" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-regular fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=attendance" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-regular fa-clock"></i>
                    <span>Attendance</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=leave-request" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-regular fa-calendar"></i>
                    <span>Leave</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=payroll" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-money-bill"></i>
                    <span>Payroll</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=benefits-and-government-contribution" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    <span>Benefits & Contributions</span>
                </a>
            </li>

            <div class="separator"></div>

            <h3>Employee Development</h3>

            <li>
                <a href="index.php?url=performance" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Performance Evaluation</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=training" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Training & Seminars</span>
                </a>
            </li>

            <div class="separator"></div>

            <h3>Employee Relations</h3>

            <li>
                <a href="index.php?url=complaint" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-regular fa-message"></i>
                    <span>Complaints</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=grievance" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <span>Grievances</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=resignation" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                    <span>Resignation Request</span>
                </a>
            </li>

            <li>
                <a href="index.php?url=online-meeting" class="menu-link" style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">
                    <i class="fa-solid fa-person-walking-arrow-right"></i>
                    <span>Online Meeting</span>
                </a>
            </li>

        </ul>

    </div>

</aside>

<script>
function toggleSidebar() {

    const sidebar = document.getElementById('mainSidebar');
    const header = document.querySelector('header');

    if (!sidebar || !header) return;

    const isCollapsed = sidebar.dataset.collapsed === 'true';

    if (isCollapsed) {

        // =========================
        // OPEN SIDEBAR
        // =========================
        sidebar.style.width = '260px';
        sidebar.style.minWidth = '260px';
        sidebar.style.marginLeft = '0';
        sidebar.style.padding = '';
        sidebar.style.border = '';
        sidebar.style.overflowY = 'auto';
        sidebar.style.overflowX = 'hidden';

        // SHRINK HEADER BACK
        header.style.width = '';
        header.style.marginLeft = '';

        sidebar.dataset.collapsed = 'false';

    } else {

        // =========================
        // CLOSE SIDEBAR
        // =========================
        sidebar.style.width = '0';
        sidebar.style.minWidth = '0';
        sidebar.style.marginLeft = '-260px';
        sidebar.style.padding = '0';
        sidebar.style.border = '0';
        sidebar.style.overflow = 'hidden';

        // EXPAND HEADER INTO SIDEBAR SPACE
        header.style.width = 'calc(100% + 260px)';
        header.style.marginLeft = '-260px';

        sidebar.dataset.collapsed = 'true';
    }
}
</script>