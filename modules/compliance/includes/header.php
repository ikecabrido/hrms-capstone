<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <style>
        :root {
            --color1: rgba(32, 0, 130, 1);
            --color2: rgba(51, 65, 85, 1);
            --color3: rgba(255, 255, 255, 1);
            --color4: rgba(186, 186, 186, 1);
            --color5: rgba(95, 95, 95, 1);
            --color6: rgba(51, 65, 85, 1);
            --color7: rgb(81, 70, 183);
            --color8: rgb(0, 0, 0);
            --color9: rgba(240, 240, 240, 1);
            --color10: #e53e3e;
            --sidebar-width: 252px;
            --header-height: 60px;
            --success-50: #ecfdf5;
            --success-100: #d1fae5;
            --success-500: #10b981;
            --success-600: #059669;
            --success-700: #047857;
            --warning-50: #fffbeb;
            --warning-100: #fef3c7;
            --warning-500: #f59e0b;
            --warning-600: #d97706;
            --warning-700: #b45309;
            --danger-50: #fef2f2;
            --danger-100: #fee2e2;
            --danger-500: #ef4444;
            --danger-600: #dc2626;
            --danger-700: #b91c1c;
            --info-50: #ecfeff;
            --info-100: #cffafe;
            --info-500: #06b6d4;
            --info-600: #0891b2;
            --info-700: #0e7490;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--color9); }
        .main-content { margin-left: var(--sidebar-width); margin-top: var(--header-height); min-height: calc(100vh - var(--header-height)); padding: 2rem; position: relative; transition: margin-left 0.3s ease; }
        .main-content.sidebar-collapsed { margin-left: 0; }
        .main-content::-webkit-scrollbar { width: 8px; display: block; }
        .main-content::-webkit-scrollbar-track { background: transparent; }
        .main-content::-webkit-scrollbar-thumb { background: var(--color4); border-radius: 4px; }
        .main-content::-webkit-scrollbar-thumb:hover { background: var(--color5); }
        .container { max-width: 1284px; width: 100%; margin: 0 auto; padding: 0 1rem; }
        ::-webkit-scrollbar { display: none; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--color4); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--color5); }
        @media (max-width: 1024px) { .container { max-width: 960px; } .module-content { max-width: 100%; padding: 1.5rem; } }
@media (max-width: 768px) { .main-content { margin-left: 0; min-height: calc(100vh - var(--header-height)); padding: 1rem; } .container { padding: 0 0.5rem; } }
@media (max-width: 480px) { .main-content { margin-top: 50px; min-height: calc(100vh - 50px); padding: 0.75rem; } }
        @media print { .sidebar, header, footer, .hamburger { display: none; } .main-content { margin-left: 0; margin-top: 0; } }
    </style>

    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/layout/sidebar.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/layout/footer.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/layout/header.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/layout/module-container.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/components/dropdown.css?v=3">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/components/calendar.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/components/list_action_buttons.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/dashboard.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/notification-compose.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/labor-law-references.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/sent-history.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/case-records.css?v=2">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/document-requests.css?v=11">
    <link rel="stylesheet" href="/hrms-capstone/modules/compliance/css/pages/onboarding-package.css?v=2">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="icon" type="image/png" href="/hrms-capstone/modules/compliance/assets/bcp_logo.png">
    <title>Legal & Compliance</title>
</head>
<body>
    <header>
        <div class="hamburger" id="hamburgerBtn" aria-label="Toggle sidebar" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
        </div>
        <div class="header-actions">
            <div class="realtime" id="realtimeClock" aria-live="polite">--:--</div>
            <button type="button" class="header-icon-btn" title="Mail" onclick="window.location.href='?page=notification-compose&mode=reply&notification_id=0'">
                <i class="bi bi-envelope"></i>
            </button>
            <?php include __DIR__ . '/../lib/includes/calendar_button.php'; ?>
        </div>

        <!-- Notification Dropdown -->
        <div class="icon-dropdown" id="bellDropdown">
            <div class="dropdown-header">
                <span>Notifications</span>
                <button class="mark-all-read" id="markAllRead">Mark all as read</button>
            </div>
            <ul class="notif-list" id="notifList">
                <li class="notif-item empty-notif">Loading notifications...</li>
            </ul>
        </div>

        <!-- User Profile Dropdown -->
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
                    <a href="/hrms-capstone/auth/logout.php" class="signout-link">
                        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                    </a>
                </li>
            </ul>
        </div>
    </header>

    <!-- Notification Detail Popover (detached from header) -->
    <div class="icon-dropdown notif-detail-dropdown" id="notifDetailDropdown">
        <div class="dropdown-header">
            <span id="notifDetailTitle">Notification</span>
            <button class="mark-all-read" id="notifDetailClose">Close</button>
        </div>
        <div class="notif-detail-body" id="notifDetailBody"></div>
    </div>


