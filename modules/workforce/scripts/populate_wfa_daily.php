<?php
/**
 * WFA Daily Data Population Script
 * Calculates and populates all WFA metrics daily
 * 
 * Cron schedule: 0 23 * * * /usr/bin/php /path/to/scripts/populate_wfa_daily.php
 * (Runs daily at 11:59 PM)
 */

require_once __DIR__ . '/../../auth/database.php';

// Log file for debugging
$log_file = __DIR__ . '/../../logs/wfa_population.log';
$timestamp = date('Y-m-d H:i:s');

function log_message($message, $level = 'INFO') {
    global $log_file, $timestamp;
    $message = "[$timestamp] [$level] $message\n";
    file_put_contents($log_file, $message, FILE_APPEND);
    echo $message;
}

try {
    log_message("Starting WFA daily population process...");
    
    $metric_date = date('Y-m-d');
    
    // ========================================
    // 1. EMPLOYEE METRICS
    // ========================================
    log_message("Calculating employee metrics...");
    
    $sql = "
        INSERT INTO wfa_employee_metrics 
        (metric_date, total_employees, total_teachers, total_staff, new_hires_this_year, 
         average_salary, average_performance_score, total_departments)
        SELECT 
            ? as metric_date,
            COUNT(DISTINCT e.employee_id) as total_employees,
            SUM(CASE WHEN e.position LIKE '%Teacher%' OR e.position LIKE '%Instructor%' THEN 1 ELSE 0 END) as total_teachers,
            SUM(CASE WHEN e.position NOT LIKE '%Teacher%' AND e.position NOT LIKE '%Instructor%' THEN 1 ELSE 0 END) as total_staff,
            COUNT(CASE WHEN YEAR(e.date_hired) = YEAR(CURDATE()) THEN 1 END) as new_hires_this_year,
            COALESCE(AVG(CAST(REPLACE(e.salary, ',', '') AS DECIMAL(12,2))), 0) as average_salary,
            COALESCE(ROUND(AVG(pr.rating), 2), 0) as average_performance_score,
            COUNT(DISTINCT e.department) as total_departments
        FROM employees e
        LEFT JOIN performance_reviews pr ON e.employee_id = pr.employee_id 
            AND YEAR(pr.review_date) = YEAR(CURDATE())
        WHERE e.employment_status = 'Active'
        ON DUPLICATE KEY UPDATE
            total_employees = VALUES(total_employees),
            total_teachers = VALUES(total_teachers),
            total_staff = VALUES(total_staff),
            new_hires_this_year = VALUES(new_hires_this_year),
            average_salary = VALUES(average_salary),
            average_performance_score = VALUES(average_performance_score),
            total_departments = VALUES(total_departments),
            updated_at = NOW()
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $metric_date);
    if (!$stmt->execute()) {
        throw new Exception("Error calculating employee metrics: " . $stmt->error);
    }
    log_message("✓ Employee metrics updated");
    
    // ========================================
    // 2. DEPARTMENT ANALYTICS
    // ========================================
    log_message("Calculating department analytics...");
    
    $sql = "
        INSERT INTO wfa_department_analytics 
        (metric_date, department, employee_count, average_salary, average_performance_score, 
         vacancy_count, average_tenure_years)
        SELECT 
            ? as metric_date,
            e.department,
            COUNT(DISTINCT e.employee_id) as employee_count,
            COALESCE(ROUND(AVG(CAST(REPLACE(COALESCE(e.salary, '0'), ',', '') AS DECIMAL(12,2))), 2), 0) as average_salary,
            COALESCE(ROUND(AVG(pr.rating), 2), 0) as average_performance_score,
            0 as vacancy_count,
            COALESCE(ROUND(AVG(YEAR(CURDATE()) - YEAR(e.date_hired)), 1), 0) as average_tenure_years
        FROM employees e
        LEFT JOIN performance_reviews pr ON e.employee_id = pr.employee_id 
            AND YEAR(pr.review_date) = YEAR(CURDATE())
        WHERE e.employment_status = 'Active' AND e.department IS NOT NULL
        GROUP BY e.department
        ON DUPLICATE KEY UPDATE
            employee_count = VALUES(employee_count),
            average_salary = VALUES(average_salary),
            average_performance_score = VALUES(average_performance_score),
            average_tenure_years = VALUES(average_tenure_years),
            updated_at = NOW()
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $metric_date);
    if (!$stmt->execute()) {
        throw new Exception("Error calculating department analytics: " . $stmt->error);
    }
    log_message("✓ Department analytics updated");
    
    // ========================================
    // 3. RISK ASSESSMENT SCORING
    // ========================================
    log_message("Calculating risk assessment scores...");
    
    // Get all active employees
    $sql = "SELECT employee_id FROM employees WHERE employment_status = 'Active'";
    $result = $conn->query($sql);
    
    $insert_count = 0;
    while ($emp_row = $result->fetch_assoc()) {
        $emp_id = $emp_row['employee_id'];
        
        // Get employee data
        $stmt = $conn->prepare("
            SELECT 
                e.employee_id,
                COALESCE(pr.rating, 0) as performance_score,
                COALESCE(COUNT(DISTINCT al.log_id), 0) as absence_days,
                YEAR(CURDATE()) - YEAR(e.date_hired) as tenure_years
            FROM employees e
            LEFT JOIN performance_reviews pr ON e.employee_id = pr.employee_id
            LEFT JOIN activity_logs al ON e.employee_id = al.user_id 
                AND al.action LIKE '%absence%' 
                AND YEAR(al.created_at) = YEAR(CURDATE())
            WHERE e.employee_id = ?
            GROUP BY e.employee_id
        ");
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $emp_data = $stmt->get_result()->fetch_assoc();
        
        if (!$emp_data) continue;
        
        // Calculate risk factors
        $risk_factors = array();
        $risk_score = 0;
        
        // Low performance: rating < 3.0
        if ($emp_data['performance_score'] < 3.0) {
            $risk_factors[] = 'low_performance';
            $risk_score += 30;
        }
        
        // High absence: > 15 days
        if ($emp_data['absence_days'] > 15) {
            $risk_factors[] = 'high_absence';
            $risk_score += 25;
        }
        
        // Low tenure: < 2 years
        if ($emp_data['tenure_years'] < 2) {
            $risk_factors[] = 'low_tenure';
            $risk_score += 15;
        }
        
        // Determine risk level
        $risk_level = ($risk_score >= 60) ? 'high' : (($risk_score >= 40) ? 'medium' : 'low');
        
        // Insert or update risk assessment
        $risk_factors_json = json_encode($risk_factors);
        $tenure_months = (int)($emp_data['tenure_years'] * 12);
        
        $stmt = $conn->prepare("
            INSERT INTO wfa_risk_assessment 
            (employee_id, risk_level, risk_score, risk_factors, low_performance_flag,
             high_absence_flag, performance_score, absence_days, tenure_months)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                risk_level = VALUES(risk_level),
                risk_score = VALUES(risk_score),
                risk_factors = VALUES(risk_factors),
                low_performance_flag = VALUES(low_performance_flag),
                high_absence_flag = VALUES(high_absence_flag),
                performance_score = VALUES(performance_score),
                absence_days = VALUES(absence_days),
                updated_at = NOW()
        ");
        
        $low_perf = ($emp_data['performance_score'] < 3.0) ? 1 : 0;
        $high_abs = ($emp_data['absence_days'] > 15) ? 1 : 0;
        
        $stmt->bind_param("ssisiiiii", 
            $emp_id, $risk_level, $risk_score, $risk_factors_json,
            $low_perf, $high_abs, $emp_data['performance_score'],
            $emp_data['absence_days'], $tenure_months
        );
        
        if (!$stmt->execute()) {
            log_message("Warning: Could not update risk assessment for $emp_id", 'WARN');
        } else {
            $insert_count++;
        }
    }
    log_message("✓ Risk assessments updated ($insert_count employees)");
    
    // ========================================
    // 4. MONTHLY ATTRITION SUMMARY
    // ========================================
    log_message("Calculating monthly attrition summary...");
    
    $sql = "
        INSERT INTO wfa_monthly_attrition 
        (year_month, total_separations, voluntary_separations, involuntary_separations, attrition_rate_percent)
        SELECT 
            CONCAT(YEAR(separation_date), '-', LPAD(MONTH(separation_date), 2, '0'), '-01') as year_month,
            COUNT(*) as total_separations,
            SUM(CASE WHEN separation_type = 'resigned' THEN 1 ELSE 0 END) as voluntary_separations,
            SUM(CASE WHEN separation_type IN ('terminated', 'retired') THEN 1 ELSE 0 END) as involuntary_separations,
            ROUND((COUNT(*) / (SELECT COUNT(*) FROM employees WHERE employment_status = 'Active')) * 100, 2) as attrition_rate_percent
        FROM wfa_attrition_tracking
        WHERE MONTH(separation_date) = MONTH(?) AND YEAR(separation_date) = YEAR(?)
        GROUP BY YEAR(separation_date), MONTH(separation_date)
        ON DUPLICATE KEY UPDATE
            total_separations = VALUES(total_separations),
            voluntary_separations = VALUES(voluntary_separations),
            involuntary_separations = VALUES(involuntary_separations),
            attrition_rate_percent = VALUES(attrition_rate_percent),
            updated_at = NOW()
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $metric_date, $metric_date);
    if (!$stmt->execute()) {
        throw new Exception("Error calculating monthly attrition: " . $stmt->error);
    }
    log_message("✓ Monthly attrition summary updated");
    
    log_message("✅ WFA daily population completed successfully!");
    
} catch (Exception $e) {
    log_message("❌ Error: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
