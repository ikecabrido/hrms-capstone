<?php
/**
 * Get Action Progress & Timeline
 * Returns detailed progress information for an action including notes history
 * 
 * GET Parameters:
 * - action_id: ID of the action to fetch
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../auth/database.php';

try {
    if (!isset($_GET['action_id'])) {
        throw new Exception('action_id parameter required');
    }
    
    $action_id = (int)$_GET['action_id'];
    $db = Database::getInstance()->getConnection();
    
    // Get action details with employee info
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
            e.full_name as employee_name,
            e.department,
            e.position,
            u.full_name as created_by_name
        FROM wfa_performance_actions a
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        LEFT JOIN employees u ON a.created_by = u.employee_id
        WHERE a.action_id = ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$action_id]);
    $action = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$action) {
        throw new Exception('Action not found');
    }
    
    // Parse notes history (split by timestamps)
    $notesHistory = [];
    if ($action['notes']) {
        $lines = array_filter(explode("\n", $action['notes']));
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2})\]\s(.+)$/', $line, $matches)) {
                $notesHistory[] = [
                    'timestamp' => $matches[1],
                    'note' => $matches[2]
                ];
            } else if ($line) {
                $notesHistory[] = [
                    'timestamp' => 'Initial',
                    'note' => $line
                ];
            }
        }
    }
    
    // Calculate days elapsed
    $createdDate = new DateTime($action['created_date']);
    $today = new DateTime();
    $daysElapsed = $createdDate->diff($today)->days;
    
    // Calculate duration if completed
    $durationDays = null;
    if ($action['completed_date']) {
        $completedDate = new DateTime($action['completed_date']);
        $durationDays = $createdDate->diff($completedDate)->days;
    }
    
    echo json_encode([
        'success' => true,
        'action' => [
            'action_id' => (int)$action['action_id'],
            'employee_id' => (int)$action['employee_id'],
            'employee_name' => $action['employee_name'],
            'department' => $action['department'],
            'position' => $action['position'],
            'action_type' => $action['action_type'],
            'title' => $action['title'],
            'description' => $action['description'],
            'reason' => $action['reason'],
            'priority' => $action['priority'],
            'status' => $action['status'],
            'progress_percentage' => (int)$action['progress_percentage'],
            'days_remaining' => (int)$action['days_remaining'],
            'days_elapsed' => $daysElapsed,
            'duration_days' => $durationDays,
            'dates' => [
                'created' => $action['created_date'],
                'start' => $action['start_date'],
                'target' => $action['target_date'],
                'completed' => $action['completed_date']
            ],
            'created_by' => $action['created_by_name'],
            'notes_history' => $notesHistory
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
