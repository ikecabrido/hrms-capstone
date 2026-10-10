<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;

if ($complaintId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=hrms;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT id, status, current_step_key, current_state, version, employee_id, assigned_to, assigned_name, updated_at, created_at FROM lc_complaints WHERE id = :id");
    $stmt->execute([':id' => $complaintId]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
        exit;
    }

    $historyStmt = $db->prepare("SELECT action, old_status, new_status, decision_label, performed_by, notes, created_at FROM lc_complaint_decision_history WHERE complaint_id = :cid ORDER BY created_at ASC");
    $historyStmt->execute([':cid' => $complaintId]);
    $history = $historyStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'complaint' => $complaint,
        'history' => $history,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
