<?php
/**
 * Get Assigned Performance Actions for Employee
 * Returns all assigned actions with status tracking
 * 
 * Query Parameters:
 * - employee_id: Employee ID (required)
 * - status: Filter by status (pending, ongoing, completed, failed, cancelled)
 * - include_pips: Include PIP details (true/false)
 */

header('Content-Type: application/json');
session_start();

// Include database class
require_once __DIR__ . '/../../../auth/database.php';

try {
    // Validate employee_id parameter
    if (!isset($_GET['employee_id'])) {
        throw new Exception('employee_id parameter required');
    }
    
    $employee_id = (int)$_GET['employee_id'];
    
    if ($employee_id <= 0) {
        throw new Exception('employee_id must be a positive integer');
    }
    
    // Get database connection
    try {
        $db = Database::getInstance()->getConnection();
    } catch (Exception $e) {
        throw new Exception('Database connection error: ' . $e->getMessage());
    }
    
    if (!$db) {
        throw new Exception('Database connection is null');
    }
    
    // Get assigned actions
    $query = "
        SELECT 
            a.action_id,
            a.employee_id,
            a.action_type,
            a.title,
            a.description,
            a.reason,
            a.priority,
            a.status,
            a.created_date,
            a.start_date,
            a.target_date,
            a.completed_date,
            a.notes,
            DATEDIFF(a.target_date, CURDATE()) as days_remaining,
            CASE 
                WHEN a.status = 'completed' THEN 100
                WHEN a.status = 'pending' THEN 0
                WHEN CURDATE() >= a.target_date THEN 100
                WHEN DATEDIFF(a.target_date, a.start_date) <= 0 THEN 0
                ELSE LEAST(100, ROUND((DATEDIFF(CURDATE(), a.start_date) / DATEDIFF(a.target_date, a.start_date)) * 100))
            END as progress_percentage,
            e.full_name as assigned_by_name,
            emp.full_name as employee_name
        FROM wfa_performance_actions a
        LEFT JOIN employees e ON a.created_by = e.employee_id
        LEFT JOIN employees emp ON a.employee_id = emp.employee_id
        WHERE a.employee_id = ?
    ";
    
    // Apply status filter if provided
    if (!empty($_GET['status'])) {
        $query .= " AND a.status = '" . $db->quote($_GET['status']) . "'";
    }
    
    $query .= " ORDER BY a.priority DESC, a.target_date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$employee_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If query failed, throw exception with debug info
    if ($stmt->errorCode() !== '00000') {
        $error = $stmt->errorInfo();
        throw new Exception('SQL Error: ' . $error[2]);
    }
    
    // Format response
    $formatted_actions = [];
    foreach ($actions as $action) {
        $formatted_action = [
            'action_id' => (int)$action['action_id'],
            'action_type' => $action['action_type'],
            'title' => $action['title'],
            'description' => $action['description'],
            'reason' => $action['reason'],
            'priority' => $action['priority'],
            'status' => $action['status'],
            'progress_percentage' => (int)$action['progress_percentage'],
            'days_remaining' => (int)$action['days_remaining'],
            'dates' => [
                'created' => $action['created_date'],
                'start' => $action['start_date'],
                'target' => $action['target_date'],
                'completed' => $action['completed_date']
            ],
            'assigned_by' => $action['assigned_by_name'],
            'notes' => $action['notes']
        ];
        
        // Include PIP details if requested
        if (!empty($_GET['include_pips']) && $_GET['include_pips'] === 'true') {
            $pip_stmt = $db->prepare("
                SELECT 
                    pip_id,
                    start_date,
                    end_date,
                    duration_days,
                    target_performance_score,
                    target_attendance_percentage,
                    target_feedback_score,
                    current_progress_percentage,
                    status as pip_status
                FROM wfa_performance_improvement_plans
                WHERE action_id = ?
            ");
            $pip_stmt->execute([$action['action_id']]);
            $pip = $pip_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pip) {
                $formatted_action['pip'] = [
                    'pip_id' => (int)$pip['pip_id'],
                    'duration_days' => (int)$pip['duration_days'],
                    'progress_percentage' => (int)$pip['current_progress_percentage'],
                    'status' => $pip['pip_status'],
                    'targets' => [
                        'performance_score' => (float)$pip['target_performance_score'],
                        'attendance_percentage' => (int)$pip['target_attendance_percentage'],
                        'feedback_score' => (float)$pip['target_feedback_score']
                    ],
                    'dates' => [
                        'start' => $pip['start_date'],
                        'end' => $pip['end_date']
                    ]
                ];
            }
        }
        
        $formatted_actions[] = $formatted_action;
    }
    
    // Calculate summary stats
    $summary = [
        'total_actions' => count($formatted_actions),
        'pending' => count(array_filter($formatted_actions, function($a) { return $a['status'] === 'pending'; })),
        'ongoing' => count(array_filter($formatted_actions, function($a) { return $a['status'] === 'ongoing'; })),
        'completed' => count(array_filter($formatted_actions, function($a) { return $a['status'] === 'completed'; })),
        'high_priority' => count(array_filter($formatted_actions, function($a) { return $a['priority'] === 'HIGH' || $a['priority'] === 'CRITICAL'; })),
        'overdue' => count(array_filter($formatted_actions, function($a) { return $a['days_remaining'] < 0 && $a['status'] !== 'completed'; }))
    ];
    
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'summary' => $summary,
        'actions' => $formatted_actions
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    
    // Log error details for debugging
    $error_log = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Write to error log file
    error_log('WFA API Error: ' . json_encode($error_log));
    
    echo json_encode($error_log);
}
