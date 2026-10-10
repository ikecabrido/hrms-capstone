<?php

require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;

if ($employeeId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID.', 'history' => []]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    if (!($db instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable.', 'history' => []]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT DISTINCT d.new_status AS status
        FROM lc_complaints c
        INNER JOIN lc_complaint_decision_history d ON d.complaint_id = c.id
        WHERE (c.employee_id = :employee_id OR c.respondent_employee_id = :employee_id)
          AND d.new_status IN (
              'closed_warning_issued',
              'closed_second_written_warning',
              'closed_final_written_warning',
              'closed_termination_recommended',
              'closed_suspension',
              'closed_resolved',
              'closed_no_violation',
              'closed'
          )
    ");
    $stmt->execute([':employee_id' => $employeeId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = array_map(function ($row) {
        return [
            'status' => (string) $row['status'],
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'employee_id' => $employeeId,
        'history' => $history,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage(), 'history' => []]);
    exit;
}
