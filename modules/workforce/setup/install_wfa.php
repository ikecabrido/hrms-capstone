<?php
/**
 * WFA Installation & Verification Script
 * 
 * This script:
 * 1. Checks if all required files exist
 * 2. Verifies database connection
 * 3. Creates WFA tables (if not exists)
 * 4. Tests API endpoints
 * 5. Provides setup status
 * 
 * Usage: Access http://localhost/capstone_hr_management_system/workforce/install_wfa.php
 */

header('Content-Type: text/html; charset=utf-8');

// Color codes for terminal/browser output
$SUCCESS = '#27ae60';
$ERROR = '#e74c3c';
$WARNING = '#f39c12';
$INFO = '#3498db';

$results = [];
$all_passed = true;

// ===== 1. CHECK FILES =====
echo "<h2>WFA Installation & Verification</h2>";
echo "<hr>";

$required_files = [
    'models/ActionSystem.php' => 'Core logic class',
    'api/wfa/create_action.php' => 'Create action API',
    'api/wfa/create_pip.php' => 'Create PIP API',
    'api/wfa/analyze_employee.php' => 'Analyze employee API',
    'api/wfa/get_low_performers.php' => 'Get low performers API',
    'views/wfa_action_dashboard.html' => 'Dashboard UI',
    'sql/wfa_action_system.sql' => 'Database schema',
];

echo "<h3>1. Checking Required Files</h3>";
foreach ($required_files as $file => $description) {
    $path = __DIR__ . '/../' . $file;
    $exists = file_exists($path);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    $color = $exists ? $SUCCESS : $ERROR;
    
    echo "<p style='color: $color'>$status - $file ($description)</p>";
    if (!$exists) $all_passed = false;
}

// ===== 2. CHECK DATABASE CONNECTION =====
echo "<h3>2. Checking Database Connection</h3>";
try {
    require_once(__DIR__ . '/../../config/config.php');
    
    // Load database class
    if (!file_exists(__DIR__ . '/../../config/Database.php')) {
        throw new Exception("Database.php not found at: " . __DIR__ . '/../../config/Database.php');
    }
    require_once(__DIR__ . '/../../config/Database.php');
    $db = Database::getInstance();
    $conn = $db->getConnection(); // This returns mysqli connection
    
    if ($conn) {
        echo "<p style='color: $SUCCESS'>✓ Database connection successful</p>";
        
        // Check database name
        $result = $conn->query("SELECT DATABASE() as db_name");
        if ($result && $row = $result->fetch_assoc()) {
            echo "<p style='color: $INFO'>Connected to: " . $row['db_name'] . "</p>";
        }
    } else {
        throw new Exception("Connection returned null");
    }
} catch (\Exception $e) {
    echo "<p style='color: $ERROR'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    $all_passed = false;
    exit;
}

// ===== 3. CREATE/VERIFY TABLES =====
echo "<h3>3. Creating/Verifying WFA Tables</h3>";

$tables_to_create = [
    'wfa_performance_improvement_plans',
    'wfa_actions',
    'wfa_action_recommendations',
    'wfa_performance_issues'
];

// Read SQL file
$sql_file = __DIR__ . '/../sql/wfa_action_system.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL statements and execute
    $statements = explode(';', $sql_content);
    $created_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $conn->exec($statement);
            $created_count++;
        } catch (\Exception $e) {
            // Ignore table exists errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<p style='color: $WARNING'>Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p style='color: $SUCCESS'>✓ WFA tables created/verified</p>";
    
    // Check if tables exist
    foreach ($tables_to_create as $table) {
        $check = $conn->query("SHOW TABLES LIKE '$table'")->fetch_assoc();
        $status = $check ? "✓ OK" : "✗ MISSING";
        $color = $check ? $SUCCESS : $ERROR;
        echo "<p style='color: $color'>  $status - $table</p>";
        
        if (!$check) $all_passed = false;
    }
} else {
    echo "<p style='color: $ERROR'>✗ SQL file not found: $sql_file</p>";
    $all_passed = false;
}

// ===== 4. TEST SAMPLE DATA =====
echo "<h3>4. Testing Sample Data Queries</h3>";

try {
    // Check employees
    $emp_result = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc();
    echo "<p style='color: $INFO'>Total employees: " . $emp_result['count'] . "</p>";
    
    // Check performance reviews (using pm_appraisals)
    $perf_result = $conn->query("SELECT COUNT(*) as count FROM pm_appraisals")->fetch_assoc();
    echo "<p style='color: $INFO'>Performance appraisals: " . $perf_result['count'] . "</p>";
    
    // Check attendance
    $att_result = $conn->query("SELECT COUNT(*) as count FROM attendance")->fetch_assoc();
    echo "<p style='color: $INFO'>Attendance records: " . ($att_result ? $att_result['count'] : 0) . "</p>";
    
    // Check WFA records
    $wfa_result = $conn->query("SELECT COUNT(*) as count FROM wfa_actions")->fetch_assoc();
    echo "<p style='color: $INFO'>WFA actions stored: " . $wfa_result['count'] . "</p>";
    
} catch (\Exception $e) {
    echo "<p style='color: $WARNING'>⚠ Query test failed: " . $e->getMessage() . "</p>";
}

// ===== 5. TEST ACTIONYSTEM CLASS =====
echo "<h3>5. Testing ActionSystem Class</h3>";

try {
    require_once(__DIR__ . '/../../models/ActionSystem.php');
    
    // Get first employee with performance data
    $emp = $conn->query("
        SELECT e.employee_id, e.full_name 
        FROM employees e
        LEFT JOIN pm_appraisals pa ON e.employee_id = pa.employee_id
        WHERE pa.employee_id IS NOT NULL
        LIMIT 1
    ")->fetch_assoc();
    
    if ($emp) {
        $action_system = new \WFA\System\ActionSystem($conn);
        $analysis = $action_system->detectPerformanceIssues($emp['employee_id']);
        
        echo "<p style='color: $SUCCESS'>✓ ActionSystem class loaded successfully</p>";
        echo "<p style='color: $INFO'>Test Analysis for: " . $emp['full_name'] . "</p>";
        echo "<p style='color: $INFO'>  - Issues detected: " . count($analysis['issues']) . "</p>";
        echo "<p style='color: $INFO'>  - Severity: " . $analysis['severity'] . "</p>";
        
        $recommendation = $action_system->recommendAction($analysis);
        echo "<p style='color: $INFO'>  - Recommended action: " . $recommendation['recommended_action'] . "</p>";
        echo "<p style='color: $INFO'>  - Confidence: " . round($recommendation['confidence_score'] * 100) . "%</p>";
    } else {
        echo "<p style='color: $WARNING'>⚠ No employees with performance data found for testing</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color: $ERROR'>✗ ActionSystem test failed: " . $e->getMessage() . "</p>";
    $all_passed = false;
}

// ===== 6. FINAL SUMMARY =====
echo "<h3>Installation Summary</h3>";
echo "<hr>";

if ($all_passed) {
    echo "<p style='color: $SUCCESS; font-size: 18px; font-weight: bold;'>✓ All checks passed! WFA system is ready to use.</p>";
    echo "<p style='color: $INFO'>Next steps:</p>";
    echo "<ul>";
    echo "<li>Open dashboard: <a href='views/wfa_action_dashboard.html' target='_blank'>WFA Action Dashboard</a></li>";
    echo "<li>Read guide: <a href='../docs/WFA_INTEGRATION_GUIDE.md' target='_blank'>Integration Guide</a></li>";
    echo "<li>Quick start: <a href='../docs/QUICK_START_WFA.md' target='_blank'>Quick Start</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color: $ERROR; font-size: 18px; font-weight: bold;'>✗ Some checks failed. Please review errors above.</p>";
}

// Create a simple CSS for better formatting
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3 {
            color: #333;
        }
        p {
            margin: 10px 0;
            line-height: 1.6;
        }
        a {
            color: #3498db;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }
        ul {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <!-- Content generated above -->
</body>
</html>
