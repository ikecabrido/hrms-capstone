<?php
/**
 * Save Performance Action Assignment
 * Saves recommended actions to wfa_performance_actions table
 * 
 * POST Parameters:
 * - employee_id: Employee ID
 * - action_type: Type of action (PIP, TRAINING, COACHING, etc)
 * - title: Action title
 * - description: Detailed description
 * - priority: LOW, MEDIUM, HIGH, CRITICAL
 * - start_date: When action starts (YYYY-MM-DD)
 * - target_date: Target completion date (YYYY-MM-DD)
 * - reason: Why this action was assigned
 * - notes: Additional notes
 */

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../../auth/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Validate POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($input)) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields - check for actual existence, not just empty values
    if (!isset($input['employee_id']) || $input['employee_id'] === '' || $input['employee_id'] === null) {
        throw new Exception('Missing required field: employee_id');
    }
    if (!isset($input['action_type']) || $input['action_type'] === '' || $input['action_type'] === null) {
        throw new Exception('Missing required field: action_type');
    }
    if (!isset($input['title']) || $input['title'] === '' || $input['title'] === null) {
        throw new Exception('Missing required field: title');
    }
    
    // Validate action type - if empty, default to COACHING
    $action_type = $input['action_type'] ?? 'COACHING';
    $valid_types = ['PIP', 'TRAINING', 'COACHING', 'MENTORING', 'FEEDBACK', 'WARNING', 'ESCALATION'];
    if (!in_array(strtoupper($action_type), $valid_types)) {
        $action_type = 'COACHING'; // Default to coaching if invalid
    }
    $action_type = strtoupper($action_type);
    
    // Validate priority
    $priority = strtoupper($input['priority'] ?? 'MEDIUM');
    $valid_priorities = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
    if (!in_array($priority, $valid_priorities)) {
        $priority = 'MEDIUM';
    }
    
    // Validate dates
    $start_date = $input['start_date'] ?? date('Y-m-d');
    $target_date = $input['target_date'] ?? date('Y-m-d', strtotime('+30 days'));
    
    // Validate employee exists
    $check_emp = $db->prepare("SELECT employee_id FROM employees WHERE employee_id = ?");
    $check_emp->execute([$input['employee_id']]);
    if (!$check_emp->fetch()) {
        throw new Exception('Employee not found');
    }
    
    // Insert action
    $stmt = $db->prepare("
        INSERT INTO wfa_performance_actions 
        (employee_id, action_type, title, description, reason, priority, 
         created_date, created_by, start_date, target_date, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'pending')
    ");
    
    $created_by = isset($_SESSION['user']['employee_id']) ? $_SESSION['user']['employee_id'] : 1; // Default to user ID 1 if no session
    
    $stmt->execute([
        (int)$input['employee_id'],
        $action_type,
        $input['title'],
        $input['description'] ?? null,
        $input['reason'] ?? null,
        $priority,
        $created_by,
        $start_date,
        $target_date,
        $input['notes'] ?? null
    ]);
    
    $action_id = $db->lastInsertId();
    
    // If action type is PIP, create accompanying PIP record
    if ($action_type === 'PIP') {
        $pip_stmt = $db->prepare("
            INSERT INTO wfa_performance_improvement_plans
            (action_id, employee_id, start_date, end_date, duration_days, 
             target_performance_score, target_attendance_percentage, target_feedback_score,
             created_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
        ");
        
        $duration = max(30, (int)($input['duration_days'] ?? 30));
        $end_date = date('Y-m-d', strtotime($target_date));
        
        $pip_stmt->execute([
            $action_id,
            $input['employee_id'],
            $start_date,
            $end_date,
            $duration,
            $input['target_performance_score'] ?? 3.0,
            $input['target_attendance_percentage'] ?? 95,
            $input['target_feedback_score'] ?? 3.0
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Action assigned successfully',
        'action_id' => $action_id,
        'data' => [
            'action_id' => $action_id,
            'employee_id' => $input['employee_id'],
            'action_type' => $input['action_type'],
            'title' => $input['title'],
            'priority' => $priority,
            'status' => 'pending',
            'start_date' => $start_date,
            'target_date' => $target_date
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
