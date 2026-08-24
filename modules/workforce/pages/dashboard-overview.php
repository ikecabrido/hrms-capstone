    <div class="module-header">
        <h1>Dashboard Overview</h1>
    </div>

    <div class="module-content">
        <!-- Include Workforce Analytics Dashboard -->
        <?php 
            $dashboardPath = dirname(__FILE__) . '/../public/dashboard.php';
            if (file_exists($dashboardPath)) {
                include $dashboardPath;
            } else {
                echo '<div class="wfa-error"><p>Dashboard file not found at: ' . htmlspecialchars($dashboardPath) . '</p></div>';
            }
        ?>
    </div>

    <style>
        /* Workforce Analytics Dashboard Styles */
        .wfa-container {
            background: #e6ffed;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .wfa-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .wfa-metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .wfa-metric-card.danger { border-left-color: #dc3545; }
        .wfa-metric-card.warning { border-left-color: #ffc107; }
        .wfa-metric-card.success { border-left-color: #28a745; }
        .wfa-metric-card.info { border-left-color: #17a2b8; }

        .wfa-metric-icon {
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .wfa-metric-content {
            flex: 1;
        }

        .wfa-metric-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .wfa-metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #212529;
            margin-bottom: 5px;
        }

        .wfa-metric-change {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .wfa-summary-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .wfa-chart-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .wfa-chart-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .wfa-table-container {
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fb 100%);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 18px 45px rgba(56, 85, 123, 0.08);
            margin-bottom: 20px;
            border: 1px solid rgba(33, 37, 41, 0.06);
        }

        .wfa-table-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1f2a44;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .wfa-section-header {
            margin-bottom: 20px;
        }

        .wfa-section-header h3 {
            margin: 0 0 8px 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2a44;
        }

        .wfa-section-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .wfa-scroll-table {
            overflow-x: auto;
        }

        .wfa-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            min-width: 0;
        }

        .wfa-table thead {
            background-color: transparent;
        }

        .wfa-table th {
            padding: 16px 18px;
            text-align: left;
            font-weight: 700;
            color: #495057;
            font-size: 0.92rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: none;
            white-space: nowrap;
        }

        .wfa-table td {
            padding: 16px 18px;
            background: white;
            border: none;
            vertical-align: middle;
            color: #495057;
            font-size: 0.95rem;
        }

        .wfa-table tbody tr {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(40, 54, 66, 0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .wfa-table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(40, 54, 66, 0.09);
        }

        .wfa-table tbody tr td:first-child {
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .wfa-table tbody tr td:last-child {
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        .wfa-loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .wfa-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .wfa-dashboard-hero {
            margin-bottom: 30px;
        }

        .wfa-dashboard-hero h2 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1f2a44;
        }

        .wfa-dashboard-hero p {
            margin: 0;
            color: #64748b;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .wfa-metrics-grid {
                grid-template-columns: 1fr;
            }

            .wfa-summary-charts {
                grid-template-columns: 1fr;
            }

            .wfa-metric-value {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
        // Initialize dashboard on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeDashboardOverview);
        } else {
            initializeDashboardOverview();
        }

        function initializeDashboardOverview() {
            console.log('Initializing Dashboard Overview...');
            loadDashboardData();
        }

        async function loadDashboardData() {
            try {
                const date = new Date().toISOString().split('T')[0];
                console.log('Loading Dashboard for date:', date);

                // Load all necessary data from APIs
                const responses = await Promise.allSettled([
                    fetch('../api/wfa/employees_data.php').then(r => r.json()),
                    fetch('../api/wfa/insights_analytics.php').then(r => r.json()),
                    fetch(`../api/wfa/dashboard_metrics.php?date=${date}`).then(r => r.json()),
                    fetch('../api/wfa/at_risk_employees.php?limit=5&risk_level=high').then(r => r.json()),
                    fetch('../api/wfa/attrition_metrics.php').then(r => r.json()),
                    fetch(`../api/wfa/department_analytics.php?date=${date}`).then(r => r.json()),
                    fetch(`../api/wfa/diversity_metrics.php?date=${date}&category=gender`).then(r => r.json()),
                    fetch('../api/wfa/get_attendance_data.php?limit=50&days=30').then(r => r.json()),
                    fetch('../api/wfa/get_performance_data.php?limit=50').then(r => r.json())
                ]);

                // Extract results, using empty objects as defaults
                const [empResult, insightsResult, metricsResult, atRiskResult, attritionResult, deptResult, diversityResult, attendanceResult, performanceResult] = responses;

                const empData = empResult.status === 'fulfilled' ? empResult.value : { data: { employees: [] } };
                const insightsData = insightsResult.status === 'fulfilled' ? insightsResult.value : {};
                const metricsData = metricsResult.status === 'fulfilled' ? metricsResult.value : { data: {} };
                const atRiskData = atRiskResult.status === 'fulfilled' ? atRiskResult.value : { data: [] };
                const attritionData = attritionResult.status === 'fulfilled' ? attritionResult.value : { data: {} };
                const deptData = deptResult.status === 'fulfilled' ? deptResult.value : { data: {} };
                const diversityData = diversityResult.status === 'fulfilled' ? diversityResult.value : { data: {} };
                const attendanceData = attendanceResult.status === 'fulfilled' ? attendanceResult.value : { data: { records: [], summary: {} } };
                const performanceData = performanceResult.status === 'fulfilled' ? performanceResult.value : { data: { records: [], summary: {} } };

                console.log('All data loaded successfully');
                console.log('Employee count:', Array.isArray(empData) ? empData.length : empData.data?.employees?.length || 0);
                console.log('Metrics data:', metricsData);
                console.log('At-risk employees:', atRiskData);
                console.log('Attendance data:', attendanceData);
                console.log('Performance data:', performanceData);

                updateDashboardMetrics(empData, metricsData, atRiskData, attritionData, attendanceData, performanceData);
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        function updateDashboardMetrics(empData, metricsData, atRiskData, attritionData, attendanceData, performanceData) {
            // Extract employee count
            let employees = [];
            if (Array.isArray(empData)) {
                employees = empData;
            } else if (empData.data && Array.isArray(empData.data.employees)) {
                employees = empData.data.employees;
            } else if (empData.data && Array.isArray(empData.data)) {
                employees = empData.data;
            }

            // Update attendance rate if available
            if (attendanceData.data && attendanceData.data.summary) {
                const attendanceRate = attendanceData.data.summary.attendance_rate || 0;
                const rateElement = document.getElementById('attendance-rate');
                if (rateElement) {
                    rateElement.textContent = (attendanceRate * 100).toFixed(1) + '%' || '-';
                }
            }

            // Update performance rating if available
            if (performanceData.data && performanceData.data.summary) {
                const avgRating = performanceData.data.summary.average_rating || 0;
                const ratingElement = document.getElementById('daily-rating-percent');
                if (ratingElement) {
                    ratingElement.textContent = (avgRating * 100).toFixed(1) + '%' || '-';
                }
            }

            // Update at-risk count
            const atRiskCount = metricsData.data?.at_risk_count || atRiskData.data?.length || 0;
            const atRiskElement = document.getElementById('at-risk-count');
            if (atRiskElement) {
                atRiskElement.textContent = atRiskCount;
            }

            console.log('Dashboard metrics updated successfully');
            console.log('Total employees:', employees.length);
            console.log('At-risk count:', atRiskCount);
        }
    </script>