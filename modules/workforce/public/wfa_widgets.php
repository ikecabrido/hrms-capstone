<?php
/**
 * WFA Widget Component
 * Integrates WFA metrics into main dashboard
 * 
 * Usage: <?php include 'wfa_widgets.php'; ?>
 */

// Set error reporting to not display errors inline
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Fetch WFA metrics with error handling
function get_wfa_metrics() {
    $date = date('Y-m-d');
    $api_endpoints = array(
        'metrics' => "api/wfa/dashboard_metrics.php?date=$date",
        'at_risk' => "api/wfa/at_risk_employees.php?limit=5&risk_level=high"
    );
    
    $data = array();
    
    foreach ($api_endpoints as $name => $endpoint) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($endpoint, false, $context);
            if ($response) {
                $data[$name] = json_decode($response, true);
            }
        } catch (Exception $e) {
            // Silently fail and continue
        }
    }
    
    return $data;
}

$wfa_data = get_wfa_metrics();
$metrics = $wfa_data['metrics']['data'] ?? null;
$at_risk = $wfa_data['at_risk']['data'] ?? null;

if ($metrics):
?>

<!-- WFA Analytics Section -->
<div class="wfa-section">
    <div class="section-header">
        <h2>Workforce Analytics</h2>
        <a href="workforce/analytics.php" class="btn btn-sm btn-primary">View Full Analytics</a>
    </div>
    
    <!-- WFA Key Metrics -->
    <div class="wfa-metrics-grid">
        <div class="wfa-metric-card danger">
            <div class="wfa-metric-label">At-Risk Employees</div>
            <div class="wfa-metric-value"><?php echo $metrics['at_risk_count'] ?? 0; ?></div>
            <div class="wfa-metric-change">Requiring attention</div>
        </div>
        
        <div class="wfa-metric-card warning">
            <div class="wfa-metric-label">Attrition This Year</div>
            <div class="wfa-metric-value">
                <?php 
                echo isset($metrics['attrition_data']['total_this_year']) 
                    ? $metrics['attrition_data']['total_this_year'] 
                    : '0'; 
                ?>
            </div>
            <div class="wfa-metric-change">Separations</div>
        </div>
        
        <div class="wfa-metric-card info">
            <div class="wfa-metric-label">Avg. Tenure</div>
            <div class="wfa-metric-value">
                <?php 
                echo isset($metrics['department_stats'][0]['average_tenure_years'])
                    ? number_format($metrics['department_stats'][0]['average_tenure_years'], 1)
                    : '-';
                ?>
            </div>
            <div class="wfa-metric-change">Years</div>
        </div>
    </div>
    
    <!-- At-Risk Employees Quick View -->
    <?php if ($at_risk && !empty($at_risk['employees'])): ?>
    <div class="wfa-table-container">
        <h3>High-Risk Employees (Quick View)</h3>
        <div class="wfa-quick-table">
            <div class="wfa-table-header">
                <div class="wfa-col-name">Name</div>
                <div class="wfa-col-dept">Department</div>
                <div class="wfa-col-risk">Risk Level</div>
                <div class="wfa-col-score">Score</div>
            </div>
            
            <?php foreach (array_slice($at_risk['employees'], 0, 5) as $emp): ?>
            <div class="wfa-table-row">
                <div class="wfa-col-name"><?php echo htmlspecialchars($emp['employee_name']); ?></div>
                <div class="wfa-col-dept"><?php echo htmlspecialchars($emp['department']); ?></div>
                <div class="wfa-col-risk">
                    <span class="wfa-risk-badge risk-<?php echo strtolower($emp['risk_level']); ?>">
                        <?php echo $emp['risk_level']; ?>
                    </span>
                </div>
                <div class="wfa-col-score"><?php echo $emp['risk_score']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="workforce/analytics.php" class="wfa-view-all">View All At-Risk Employees →</a>
    </div>
    <?php endif; ?>
</div>

<style>
.wfa-section {
    margin-top: 40px;
    background: #ffffff;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.section-header h2 {
    margin: 0;
    color: #212529;
    font-size: 1.5rem;
    font-weight: 600;
}

.section-header .btn {
    text-decoration: none;
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    border-radius: 4px;
    font-size: 0.9rem;
    transition: background-color 0.2s;
}

.section-header .btn:hover {
    background-color: #0056b3;
}

.wfa-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wfa-metric-card {
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    background: #f8f9fa;
}

.wfa-metric-card.danger {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.wfa-metric-card.warning {
    border-left-color: #ffc107;
    background: #fffbf0;
}

.wfa-metric-card.info {
    border-left-color: #17a2b8;
    background: #f0f7fa;
}

.wfa-metric-label {
    font-size: 0.85rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.wfa-metric-value {
    font-size: 1.75rem;
    font-weight: bold;
    color: #212529;
    margin-bottom: 5px;
}

.wfa-metric-change {
    font-size: 0.85rem;
    color: #6c757d;
}

.wfa-table-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.wfa-table-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #212529;
    font-size: 1.1rem;
}

.wfa-quick-table {
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    overflow: hidden;
}

.wfa-table-header {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 0.7fr;
    gap: 15px;
    padding: 12px 15px;
    background: #f0f0f0;
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    border-bottom: 1px solid #dee2e6;
}

.wfa-table-row {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 0.7fr;
    gap: 15px;
    padding: 12px 15px;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.9rem;
}

.wfa-table-row:last-child {
    border-bottom: none;
}

.wfa-table-row:hover {
    background-color: #f8f9fa;
}

.wfa-col-name {
    font-weight: 500;
    color: #212529;
}

.wfa-col-dept {
    color: #6c757d;
}

.wfa-col-risk {
    text-align: center;
}

.wfa-col-score {
    text-align: center;
    font-weight: 600;
    color: #212529;
}

.wfa-risk-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.wfa-risk-badge.risk-high {
    background-color: #f8d7da;
    color: #721c24;
}

.wfa-risk-badge.risk-medium {
    background-color: #fff3cd;
    color: #856404;
}

.wfa-risk-badge.risk-low {
    background-color: #d4edda;
    color: #155724;
}

.wfa-view-all {
    display: block;
    margin-top: 15px;
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.wfa-view-all:hover {
    color: #0056b3;
}

@media (max-width: 768px) {
    .wfa-metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .wfa-table-header,
    .wfa-table-row {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
</style>

<?php endif; ?>
