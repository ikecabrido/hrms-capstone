<?php
/**
 * Workforce Analytics Dashboard
 * Comprehensive WFA metrics and reporting page
 */

require_once '../auth/auth_check.php';
require_once '../auth/database.php';

// Get filter parameters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$department_filter = $_GET['department'] ?? 'all';

// Fetch dashboard metrics
$api_url = $_SERVER['HTTP_HOST'];
$metrics = file_get_contents("http://$api_url/api/wfa/dashboard_metrics.php?date=$date_filter");
$metrics = json_decode($metrics, true);

// Fetch at-risk employees
$at_risk = file_get_contents("http://$api_url/api/wfa/at_risk_employees.php?limit=10&risk_level=high");
$at_risk = json_decode($at_risk, true);

// Fetch attrition metrics
$attrition = file_get_contents("http://$api_url/api/wfa/attrition_metrics.php");
$attrition = json_decode($attrition, true);

// Fetch department analytics
$dept_analytics = file_get_contents("http://$api_url/api/wfa/department_analytics.php?date=$date_filter");
$dept_analytics = json_decode($dept_analytics, true);

// Fetch diversity metrics
$diversity = file_get_contents("http://$api_url/api/wfa/diversity_metrics.php?date=$date_filter&category=gender");
$diversity = json_decode($diversity, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workforce Analytics</title>
    <link rel="stylesheet" href="../assets/plugins/bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/plugins/font-awesome/css/all.css">
    <link rel="stylesheet" href="../style.css">
    <script src="../assets/plugins/chart.js/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --border-color: #dee2e6;
        }
        
        body {
            background-color: var(--light-bg);
        }
        
        .metrics-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .metric-card.success { border-left-color: var(--success-color); }
        .metric-card.danger { border-left-color: var(--danger-color); }
        .metric-card.warning { border-left-color: var(--warning-color); }
        .metric-card.info { border-left-color: var(--info-color); }
        
        .metric-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .metric-change {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .metric-change.positive { color: var(--success-color); }
        .metric-change.negative { color: var(--danger-color); }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #212529;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .risk-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .risk-badge.high { 
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .risk-badge.medium { 
            background-color: #fff3cd;
            color: #856404;
        }
        
        .risk-badge.low { 
            background-color: #d4edda;
            color: #155724;
        }
        
        .filters {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header {
            margin-bottom: 25px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="page-header mt-4">
            <h1 class="page-title">Workforce Analytics Dashboard</h1>
            <p class="page-subtitle">Real-time metrics and performance analytics</p>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-control">
                        <option value="all" <?php echo $department_filter === 'all' ? 'selected' : ''; ?>>All Departments</option>
                        <?php
                        $depts = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department");
                        while ($row = $depts->fetch_assoc()) {
                            echo "<option value='{$row['department']}' " . ($department_filter === $row['department'] ? 'selected' : '') . ">{$row['department']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <!-- Key Metrics -->
        <div class="metrics-container">
            <div class="metric-card success">
                <div class="metric-label">Total Employees</div>
                <div class="metric-value"><?php echo $metrics['data']['employee_metrics']['total_employees'] ?? 0; ?></div>
                <div class="metric-change">Active workforce</div>
            </div>
            
            <div class="metric-card info">
                <div class="metric-label">New Hires (This Year)</div>
                <div class="metric-value"><?php echo $metrics['data']['employee_metrics']['new_hires_this_year'] ?? 0; ?></div>
                <div class="metric-change">YTD recruitment</div>
            </div>
            
            <div class="metric-card danger">
                <div class="metric-label">At-Risk Employees</div>
                <div class="metric-value"><?php echo $metrics['data']['at_risk_count'] ?? 0; ?></div>
                <div class="metric-change">Requiring attention</div>
            </div>
            
            <div class="metric-card warning">
                <div class="metric-label">Average Performance</div>
                <div class="metric-value"><?php echo number_format($metrics['data']['employee_metrics']['average_performance_score'] ?? 0, 2); ?>/5.0</div>
                <div class="metric-change">Overall rating</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">Departments</div>
                <div class="metric-value"><?php echo $metrics['data']['employee_metrics']['total_departments'] ?? 0; ?></div>
                <div class="metric-change">Organizational units</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-label">Avg. Salary</div>
                <div class="metric-value">₱<?php echo number_format($metrics['data']['employee_metrics']['average_salary'] ?? 0, 0); ?></div>
                <div class="metric-change">Organization-wide</div>
            </div>
        </div>
        
        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Department Analytics Chart -->
            <div class="chart-container">
                <div class="chart-title">Employees by Department</div>
                <canvas id="departmentChart"></canvas>
            </div>
            
            <!-- Gender Distribution Chart -->
            <div class="chart-container">
                <div class="chart-title">Gender Distribution</div>
                <canvas id="genderChart"></canvas>
            </div>
            
            <!-- Attrition Trend Chart -->
            <div class="chart-container">
                <div class="chart-title">Monthly Attrition Rate</div>
                <canvas id="attritionChart"></canvas>
            </div>
            
            <!-- Separation Type Chart -->
            <div class="chart-container">
                <div class="chart-title">Separation Types (Last 30 Days)</div>
                <canvas id="separationChart"></canvas>
            </div>
        </div>
        
        <!-- At-Risk Employees Table -->
        <div class="table-container">
            <h3 class="chart-title">High-Risk Employees</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Risk Level</th>
                        <th>Risk Score</th>
                        <th>Performance</th>
                        <th>Absence Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($at_risk['data']['employees'])) {
                        foreach ($at_risk['data']['employees'] as $emp) {
                            $risk_class = strtolower($emp['risk_level']);
                            echo "
                            <tr>
                                <td><code>{$emp['employee_id']}</code></td>
                                <td>{$emp['employee_name']}</td>
                                <td>{$emp['department']}</td>
                                <td>{$emp['position']}</td>
                                <td><span class='risk-badge $risk_class'>{$emp['risk_level']}</span></td>
                                <td><strong>{$emp['risk_score']}</strong></td>
                                <td>{$emp['performance_score']}/5.0</td>
                                <td><span class='badge badge-warning'>{$emp['absence_days']}</span></td>
                            </tr>
                            ";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center text-muted'>No at-risk employees</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Department Analytics Table -->
        <div class="table-container">
            <h3 class="chart-title">Department Statistics</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Avg. Salary</th>
                        <th>Avg. Performance</th>
                        <th>Avg. Tenure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($dept_analytics['data']['departments'])) {
                        foreach ($dept_analytics['data']['departments'] as $dept) {
                            echo "
                            <tr>
                                <td><strong>{$dept['department']}</strong></td>
                                <td>{$dept['employee_count']}</td>
                                <td>₱" . number_format($dept['average_salary'], 0) . "</td>
                                <td>{$dept['average_performance_score']}/5.0</td>
                                <td>{$dept['average_tenure_years']} years</td>
                            </tr>
                            ";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Department Chart
        const deptData = <?php echo json_encode($dept_analytics['data']['departments'] ?? array()); ?>;
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptData.map(d => d.department),
                datasets: [{
                    label: 'Employee Count',
                    data: deptData.map(d => d.employee_count),
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Gender Distribution Chart
        const genderData = <?php echo json_encode($diversity['data']['gender_summary'] ?? array()); ?>;
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: genderData.map(g => g.category_value),
                datasets: [{
                    data: genderData.map(g => g.employee_count),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Attrition Trend Chart
        const attritionData = <?php echo json_encode($attrition['data']['monthly_summary'] ?? array()); ?>;
        const attritionCtx = document.getElementById('attritionChart').getContext('2d');
        new Chart(attritionCtx, {
            type: 'line',
            data: {
                labels: attritionData.slice(-12).map(a => a.year_month),
                datasets: [{
                    label: 'Attrition Rate %',
                    data: attritionData.slice(-12).map(a => a.attrition_rate_percent),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
        
        // Separation Type Chart
        const sepData = <?php echo json_encode($attrition['data']['by_separation_type'] ?? array()); ?>;
        const sepCtx = document.getElementById('separationChart').getContext('2d');
        new Chart(sepCtx, {
            type: 'pie',
            data: {
                labels: sepData.map(s => s.separation_type),
                datasets: [{
                    data: sepData.map(s => s.count),
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
