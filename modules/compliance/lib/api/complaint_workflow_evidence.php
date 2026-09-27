<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

if ($db === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.', 'evidence' => []]);
    exit;
}

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;
$stepKey = isset($_GET['workflow_step_key']) ? trim($_GET['workflow_step_key']) : '';

if ($complaintId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.', 'evidence' => []]);
    exit;
}

try {
    $sql = "SELECT id, status, title, description, assigned_to, assigned_name, created_at, updated_at, workflow_progress, evidence_path, evidence_notes, evidence_status, evidence_uploaded_at, evidence_uploaded_by FROM lc_complaints WHERE id = :complaint_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':complaint_id' => $complaintId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $evidence = [];

    if ($row) {
        $item = $row['evidence_item'] ?? $row['evidence_path'] ?? $row['image_path'] ?? '';
        $notes = $row['evidence_notes'] ?? $row['notes'] ?? ($row['workflow_progress'] ?? '');
        $status = $row['evidence_status'] ?? ($row['status'] ?? 'Pending');
        $uploadedAt = $row['evidence_uploaded_at'] ?? ($row['created_at'] ?? '');
        $uploadedBy = !empty($row['evidence_uploaded_by']) ? (int) $row['evidence_uploaded_by'] : (!empty($row['assigned_to']) ? (int) $row['assigned_to'] : null);
        $imagePath = $row['evidence_image_path'] ?? $row['image_path'] ?? $row['evidence_path'] ?? '';
        if (!$item && $imagePath) {
            $item = basename($imagePath);
        }

        if ($item || $imagePath || $notes) {
            $evidence[] = [
                'id' => (int) $row['id'],
                'workflow_step_key' => (string) $stepKey,
                'evidence_item' => (string) $item,
                'required' => true,
                'status' => (string) $status,
                'notes' => (string) $notes,
                'image_path' => $imagePath !== '' ? (string) $imagePath : null,
                'uploaded_by' => $uploadedBy,
                'uploaded_at' => $uploadedAt !== '' ? (string) $uploadedAt : null,
                'created_at' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
                'updated_at' => (string) ($row['updated_at'] ?? date('Y-m-d H:i:s')),
            ];
        }
    }

    echo json_encode(['success' => true, 'evidence' => $evidence]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'evidence' => []]);
}
