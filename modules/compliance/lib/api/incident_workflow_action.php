<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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
        'resolved'      => ['closed', 'investigation'],
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
    }

    if (!$newStatus) {
        echo json_encode(['success' => false, 'message' => 'This action is not allowed for the current status.']);
        exit;
    }

    $db->beginTransaction();

    $update = $db->prepare("UPDATE lc_incident_report SET status = :status, updated_at = NOW() WHERE id = :id");
    $update->execute([':status' => $newStatus, ':id' => $incidentId]);

    $insert = $db->prepare("INSERT INTO lc_incident_workflow (incident_id, step, step_status, started_at, performed_by) VALUES (:incident_id, :step, 'in_progress', NOW(), :performed_by)");
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
