<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$incidentId = isset($_GET['incident_id']) ? (int) $_GET['incident_id'] : 0;

if ($incidentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident ID.']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT id, incident_id, status, incident_type, type, severity, incident_date, incident_time, location, reporter_department, reporter_name, assigned_name, updated_at, created_at, description, title, requires_corrective_action FROM lc_incident_report WHERE id = :id");
    $stmt->execute([':id' => $incidentId]);
    $incident = $stmt->fetch();

    if (!$incident) {
        echo json_encode(['success' => false, 'message' => 'Incident not found.']);
        exit;
    }

    $workflowStmt = $db->prepare("SELECT step, step_status, started_at, completed_at, performed_by, remarks FROM lc_incident_workflow WHERE incident_id = :incident_id ORDER BY started_at ASC");
    $workflowStmt->execute([':incident_id' => $incidentId]);
    $workflowSteps = $workflowStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'incident' => $incident,
        'workflow' => $workflowSteps,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
