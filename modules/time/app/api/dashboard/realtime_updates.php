<?php
/**
 * Real-time Updates API
 * Returns recent login events and time in/out activities
 * Simplified version that connects to hr_management for activity logs
 */

// Set JSON header FIRST before any other output
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Simple authentication check
    $isAuthenticated = !empty($_SESSION['user']) || !empty($_SESSION['user_id']);
    
    if (!$isAuthenticated) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $limit = (int)($_GET['limit'] ?? 50);
    $limit = min($limit, 500);
    
    $all_events = [];
    
    // Connect to hr_management for activity logs (login events)
    try {
        $hr_db = new PDO(
            "mysql:host=localhost;dbname=hr_management;charset=utf8",
            'root',
            ''
        );
        $hr_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $login_query = "
            SELECT 
                'LOGIN' as event_type,
                username as user_name,
                timestamp as event_time,
                details
            FROM activity_logs 
            WHERE action = 'LOGIN' 
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY timestamp DESC
            LIMIT ?
        ";
        
        $stmt_login = $hr_db->prepare($login_query);
        $stmt_login->execute([$limit]);
        $login_events = $stmt_login->fetchAll(PDO::FETCH_ASSOC);
        $all_events = array_merge($all_events, $login_events);
    } catch (Exception $e) {
        error_log("Login query failed: " . $e->getMessage());
    }
    
    // Connect to time_and_attendance for time tracking (if needed)
    try {
        require_once __DIR__ . '/../../../../database/db.php';
        $db_obj = TimeDatabase::getInstance();
        $db = $db_obj->getConnection();
        
        $attendance_query = "
            SELECT 
                CASE 
                    WHEN time_out IS NOT NULL THEN 'TIME_OUT'
                    ELSE 'TIME_IN'
                END as event_type,
                e.full_name as user_name,
                COALESCE(a.time_out, a.time_in) as event_time
            FROM ta_attendance a
            JOIN employees e ON a.employee_id = e.employee_id
            WHERE (a.time_in >= DATE_SUB(NOW(), INTERVAL 1 HOUR) OR a.time_out >= DATE_SUB(NOW(), INTERVAL 1 HOUR))
            ORDER BY event_time DESC
            LIMIT ?
        ";
        
        $stmt = $db->prepare($attendance_query);
        $stmt->execute([$limit]);
        $attendance_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $all_events = array_merge($all_events, $attendance_events);
    } catch (Exception $e) {
        error_log("Attendance query failed: " . $e->getMessage());
    }
    
    // Sort by event_time descending
    usort($all_events, function($a, $b) {
        $time_a = strtotime($a['event_time'] ?? '0');
        $time_b = strtotime($b['event_time'] ?? '0');
        return $time_b - $time_a;
    });
    
    // Limit final results
    $all_events = array_slice($all_events, 0, $limit);
    
    // Format response
    $formatted_events = [];
    foreach ($all_events as $event) {
        $formatted_events[] = [
            'type' => $event['event_type'],
            'name' => $event['user_name'] ?? 'Unknown User',
            'time' => $event['event_time'] ?? date('Y-m-d H:i:s'),
            'details' => $event['details'] ?? ''
        ];
    }
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'count' => count($formatted_events),
        'events' => $formatted_events
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Realtime API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
