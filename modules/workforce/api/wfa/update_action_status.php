<?php
/**
 * Update Action Status
 * Changes the status of a performance action and optionally adds notes
 * 
 * POST Parameters:
 * - action_id: ID of the action to update
 * - new_status: New status (pending, ongoing, completed, failed, cancelled)
 * - notes: Optional notes/update text
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../auth/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action_id'])) {
        throw new Exception('Missing required field: action_id');
    }
    
    $action_id = (int)$input['action_id'];
    $new_status = $input['new_status'] ?? null;
    $notes = $input['notes'] ?? null;
    
    // Either status or notes must be provided
    if ($new_status === null && $notes === null) {
        throw new Exception('Either new_status or notes must be provided');
    }
    
    if ($new_status !== null) {
        $valid_statuses = ['pending', 'ongoing', 'completed', 'failed', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            throw new Exception('Invalid status. Must be one of: ' . implode(', ', $valid_statuses));
        }
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Update action status and/or notes
    $updateQuery = "UPDATE wfa_performance_actions SET ";
    $params = [];
    $updates = [];
    
    // Update status if provided
    if ($new_status !== null) {
        $updates[] = "status = ?";
        $params[] = $new_status;
        
        // If completed, set completed_date
        if ($new_status === 'completed') {
            $updates[] = "completed_date = NOW()";
        }
    }
    
    // Update notes if provided
    if ($notes) {
        $updates[] = "notes = CONCAT(IFNULL(notes, ''), '\n[', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i'), '] ', ?)";
        $params[] = $notes;
    }
    
    $updateQuery .= implode(", ", $updates);
    $updateQuery .= " WHERE action_id = ?";
    $params[] = $action_id;
    
    $stmt = $db->prepare($updateQuery);
    $stmt->execute($params);
    
    // Get updated action details
    $getQuery = "SELECT action_id, employee_id, title, status, completed_date, notes FROM wfa_performance_actions WHERE action_id = ?";
    $getStmt = $db->prepare($getQuery);
    $getStmt->execute([$action_id]);
    $action = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $new_status ? "Action status updated to $new_status" : "Progress note added",
        'action' => $action
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
