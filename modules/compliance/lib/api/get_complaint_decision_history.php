<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

if (!$db instanceof PDO) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.', 'data' => []]);
    exit;
}

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;

if ($complaintId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.', 'data' => []]);
    exit;
}

try {
    $stmt = $db->prepare(
        "SELECT id, action, old_status, new_status, decision_label,
                performed_by, notes, created_at
           FROM `lc_complaint_decision_history`
          WHERE complaint_id = :complaint_id
          ORDER BY created_at DESC"
    );
    $stmt->execute([':complaint_id' => $complaintId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $performerIds = array_values(array_unique(array_filter(array_map(function ($r) {
        return isset($r['performed_by']) ? (int) $r['performed_by'] : 0;
    }, $rows))));

    $names = [];
    if ($performerIds) {
        $in = implode(',', array_map('intval', $performerIds));
        $stmt2 = $db->query("SELECT employee_id, full_name FROM em_employees WHERE employee_id IN ($in)");
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $emp) {
            $names[(int) $emp['employee_id']] = $emp['full_name'];
        }
    }

    foreach ($rows as &$r) {
        $pid = isset($r['performed_by']) ? (int) $r['performed_by'] : 0;
        $r['performer_name'] = $names[$pid] ?? ('User #' . $pid);
    }
    unset($r);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}

