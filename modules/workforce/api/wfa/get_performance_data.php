<?php
/**
 * Performance Data API
 * Returns recent performance/appraisal records for dashboard
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get limit from query parameter (default 50)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Get recent appraisals/performance reviews
    $query = "
        SELECT 
            pa.appraisal_id,
            pa.employee_id,
            e.full_name,
            e.department,
            pa.review_period as appraisal_period,
            pa.overall_score as overall_rating,
            pa.overall_score as performance_rating,
            pa.comments,
            pa.review_date as created_date,
            pa.review_period as review_type
        FROM pm_appraisals pa
        JOIN employees e ON pa.employee_id = e.employee_id
        ORDER BY pa.review_date DESC
        LIMIT ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $performance_records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // If no records from pm_appraisals, try performance_reviews
    if (empty($performance_records)) {
        $query = "
            SELECT 
                pr.review_id as appraisal_id,
                pr.employee_id,
                e.full_name,
                e.department,
                DATE_FORMAT(pr.review_date, '%Y-%m') as appraisal_period,
                pr.rating as overall_rating,
                pr.rating as performance_rating,
                pr.comments,
                pr.review_date as created_date,
                'Performance Review' as review_type
            FROM performance_reviews pr
            JOIN employees e ON pr.employee_id = e.employee_id
            ORDER BY pr.review_date DESC
            LIMIT ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $performance_records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    // Get performance summary statistics from pm_appraisals
    $summary_query = "
        SELECT 
            COUNT(DISTINCT employee_id) as employees_reviewed,
            COUNT(*) as total_reviews,
            ROUND(AVG(overall_score), 2) as avg_rating,
            MAX(overall_score) as highest_rating,
            MIN(overall_score) as lowest_rating,
            SUM(CASE WHEN overall_score >= 4 THEN 1 ELSE 0 END) as high_performers,
            SUM(CASE WHEN overall_score < 3 THEN 1 ELSE 0 END) as low_performers,
            COUNT(DISTINCT CASE WHEN DATE(review_date) = CURDATE() THEN employee_id END) as daily_reviews_today
        FROM pm_appraisals
        WHERE review_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    ";

    $stmt = $db->prepare($summary_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc() ?: [];
    $stmt->close();

    if (empty($summary['total_reviews'])) {
        $summary_query = "
            SELECT 
                COUNT(DISTINCT employee_id) as employees_reviewed,
                COUNT(*) as total_reviews,
                ROUND(AVG(rating), 2) as avg_rating,
                MAX(rating) as highest_rating,
                MIN(rating) as lowest_rating,
                SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high_performers,
                SUM(CASE WHEN rating < 3 THEN 1 ELSE 0 END) as low_performers,
                COUNT(DISTINCT CASE WHEN DATE(review_date) = CURDATE() THEN employee_id END) as daily_reviews_today
            FROM performance_reviews
            WHERE review_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ";

        $stmt = $db->prepare($summary_query);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc() ?: [];
        $stmt->close();
    }

    $active_query = "SELECT COUNT(*) as active_count FROM employees WHERE employment_status = 'Active'";
    $active_result = $db->query($active_query);
    $active_count = (int)($active_result->fetch_assoc()['active_count'] ?? 0);
    $summary['daily_rating_percent'] = $active_count > 0 ? round(($summary['daily_reviews_today'] / $active_count) * 100, 2) : 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'records' => $performance_records,
            'total_records' => count($performance_records),
            'summary' => $summary
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
