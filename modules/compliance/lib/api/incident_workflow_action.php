<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$database = new Database();

if ($database->hasConnectionError()) {
    throw new RuntimeException('Database connection unavailable.');
}

$db = $database->getConnection();

$incidentId = isset($_POST['incident_id']) ? (int) $_POST['incident_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 1;

if ($incidentId <= 0 || $action === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT id, status, incident_id FROM lc_incident_report WHERE id = :id");
    $stmt->execute([':id' => $incidentId]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$incident) {
        echo json_encode(['success' => false, 'message' => 'Incident not found.']);
        exit;
    }

    $currentStatus = $incident['status'];
    $newStatus = null;

    $validTransitions = [
        'submitted'     => ['under_review', 'closed'],
        'under_review'  => ['investigation', 'closed'],
        'investigation' => ['escalated', 'closed'],
        'escalated'     => ['resolved', 'investigation', 'closed'],
        'resolved'      => ['closed', 'investigation', 'hazard_open'],
        'hazard_open'   => ['closed', 'investigation'],
        'closed'        => ['investigation'],
    ];

    if ($action === 'advance') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        $nextMap = [
            'submitted'     => 'under_review',
            'under_review'  => 'investigation',
            'investigation' => 'escalated',
            'escalated'     => 'resolved',
            'resolved'      => 'closed',
            'hazard_open'   => 'closed',
        ];
        if (in_array($nextMap[$currentStatus] ?? null, $allowed, true)) {
            $newStatus = $nextMap[$currentStatus];
        }
    } elseif ($action === 'reopen') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        if (in_array('investigation', $allowed, true)) {
            $newStatus = 'investigation';
        }
    } elseif ($action === 'close') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        if (in_array('closed', $allowed, true)) {
            $newStatus = 'closed';
        }
    } elseif ($action === 'hazard_yes' || $action === 'hazard_no') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        if ($action === 'hazard_yes' && in_array('resolved', $allowed, true)) {
            $newStatus = 'resolved';
        } elseif ($action === 'hazard_no' && in_array('closed', $allowed, true)) {
            $newStatus = 'closed';
        }
    } elseif ($action === 'remediated_yes' || $action === 'remediated_no') {
        $allowed = $validTransitions[$currentStatus] ?? [];
        if ($action === 'remediated_yes' && in_array('closed', $allowed, true)) {
            $newStatus = 'closed';
        } elseif ($action === 'remediated_no' && in_array('hazard_open', $allowed, true)) {
            $newStatus = 'hazard_open';
        }
    } elseif ($action === 'assign_investigator') {
        $employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
        if ($employeeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid employee ID.']);
            exit;
        }
        $empCheck = $db->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name FROM em_employees WHERE employee_id = :id LIMIT 1");
        $empCheck->execute([':id' => $employeeId]);
        $employee = $empCheck->fetch(PDO::FETCH_ASSOC);
        if (!$employee) {
            echo json_encode(['success' => false, 'message' => 'Employee not found.']);
            exit;
        }
        $db->beginTransaction();
        $update = $db->prepare("UPDATE lc_incident_report SET assigned_to = :owner_id, assigned_name = :assigned_name, updated_at = NOW() WHERE id = :id");
        $update->execute([':owner_id' => $employeeId, ':assigned_name' => $employee['full_name'], ':id' => $incidentId]);
        $db->commit();
        echo json_encode([
            'success'       => true,
            'message'       => 'Investigator assigned successfully.',
            'employee_name' => $employee['full_name'],
            'incident_id'   => (int) $incidentId,
        ]);
        exit;
    } elseif ($action === 'clear_investigator') {
        $db->beginTransaction();
        $update = $db->prepare("UPDATE lc_incident_report SET assigned_to = NULL, assigned_name = NULL, updated_at = NOW() WHERE id = :id");
        $update->execute([':id' => $incidentId]);
        $db->commit();
        echo json_encode([
            'success'      => true,
            'message'      => 'Investigator cleared.',
            'incident_id' => (int) $incidentId,
        ]);
        exit;
    }

    if (!$newStatus) {
        echo json_encode(['success' => false, 'message' => 'This action is not allowed for the current status.']);
        exit;
    }

    $db->beginTransaction();

    $update = $db->prepare("UPDATE lc_incident_report SET status = :status, updated_at = NOW() WHERE id = :id");
    $update->execute([':status' => $newStatus, ':id' => $incidentId]);

    $insert = $db->prepare("INSERT INTO lc_incident_workflow (id, incident_id, step, step_status, started_at, performed_by) VALUES (0, :incident_id, :step, 'in_progress', NOW(), :performed_by)");
    $insert->execute([
        ':incident_id' => $incidentId,
        ':step' => $newStatus,
        ':performed_by' => $userId,
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully.',
        'new_status' => $newStatus,
        'incident_id' => (int) $incident['id'],
        'incident_no' => $incident['incident_id'],
    ]);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
