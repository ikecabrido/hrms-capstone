<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Analytics & Reporting Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="../assets/bcp-logo2.png" alt="BCP" class="logo-image">
            </div>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">HR</div>
            <h3 class="user-name">HR Analytics</h3>
            <p class="user-email">analytics@bcp.edu.ph</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h4 class="nav-section-title">WFA ANALYTICS</h4>
                <button class="nav-btn active" data-tab="dashboard" onclick="switchTab('dashboard')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </button>
                <button class="nav-btn" data-tab="attrition" onclick="switchTab('attrition')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Attrition</span>
                </button>
                <button class="nav-btn" data-tab="diversity" onclick="switchTab('diversity')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Diversity</span>
                </button>
                <button class="nav-btn" data-tab="performance" onclick="switchTab('performance')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                    <span>Performance</span>
                </button>
                <button class="nav-btn" data-tab="custom-report" onclick="switchTab('custom-report')">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Reports</span>
                </button>
            </div>

            <div class="nav-section">
                <h4 class="nav-section-title">DOCUMENTATION</h4>
                <a href="../../WFA_QUICK_START.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                    </svg>
                    <span>Quick Start</span>
                </a>
                <a href="../../WFA_QUICK_REFERENCE.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <span>Quick Reference</span>
                </a>
                <a href="../../WFA_IMPLEMENTATION_COMPLETE.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                    </svg>
                    <span>Implementation Guide</span>
                </a>
                <a href="../../WFA_SYSTEM_INDEX.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m3.08 3.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m3.08-3.08l4.24-4.24"></path>
                    </svg>
                    <span>System Index</span>
                </a>
            </div>

            <div class="nav-section">
                <h4 class="nav-section-title">API ENDPOINTS</h4>
                <a href="../../api/wfa/dashboard_metrics.php" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                        <rect x="1" y="3" width="22" height="5"></rect>
                        <path d="M10 12v4m4-4v4"></path>
                    </svg>
                    <span>Dashboard Metrics</span>
                </a>
                <a href="../../api/wfa/at_risk_employees.php" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 9v2m0 4v2m0 4v2"></path>
                        <circle cx="12" cy="12" r="9"></circle>
                    </svg>
                    <span>At-Risk Employees</span>
                </a>
                <a href="../../api/wfa/attrition_metrics.php" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="3" x2="21" y2="3"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="21" x2="21" y2="21"></line>
                    </svg>
                    <span>Attrition Metrics</span>
                </a>
                <a href="../../api/wfa/department_analytics.php" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Department Analytics</span>
                </a>
                <a href="../../api/wfa/diversity_metrics.php" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Diversity Metrics</span>
                </a>
            </div>

            <div class="nav-section">
                <h4 class="nav-section-title">RESOURCES</h4>
                <a href="../../WFA_PROJECT_COMPLETE.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Project Summary</span>
                </a>
                <a href="../../WFA_VISUAL_OVERVIEW.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>Visual Overview</span>
                </a>
                <a href="../../WFA_IMPLEMENTATION_CHECKLIST.md" class="nav-link" target="_blank">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Setup Checklist</span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <div class="container">
        <!-- Header -->
        <header class="header">
            <!-- Hamburger Menu Button -->
            <button class="hamburger-menu" id="hamburger" onclick="toggleSidebar()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="header-content">
                <h1>Bestlink College of the Philippines</h1>
                <p>Workforce Analytics & Reporting</p>
            </div>
            <div class="header-utilities">
                <div class="clock" id="clock">00:00:00</div>
                <button class="menu-btn" onclick="toggleMenu()" title="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>

        <!-- Include Tab Content Files -->
        <div id="dashboard" class="tab-content active">
            <?php include 'dashboard.php'; ?>
        </div>
        <?php include 'attrition.php'; ?>
        <?php include 'diversity.php'; ?>
        <?php include 'performance.php'; ?>
        <?php include 'reports.php'; ?>
        </div>
    </div>

    <!-- Chart instances storage -->
    <script>
        let chartInstances = {
            department: null,
            gender: null,
            age: null,
            attrition: null,
            performance: null,
            genderDiversity: null,
            ageDiversity: null,
            departmentDiversity: null,
            performanceDist: null,
            salary: null,
            tenure: null
        };

        let allAtRiskEmployees = {};
        let customReportData = [];
    </script>

    <script src="../assets/app.js"></script>
</body>
</html>
