<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 4) . '/database/db.php';
require_once dirname(__DIR__, 4) . '/includes/Notification.php';

try {
    $pdo = (new Database())->getConnection();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid data.']);
        exit;
    }

    $employeeId = isset($input['employee_id']) ? (int) $input['employee_id'] : 0;
    $successorId = !empty($input['successor_id']) ? (int) $input['successor_id'] : null;
    $startDate = $input['start_date'] ?? '';
    $endDate = $input['end_date'] ?? '';
    $status = $input['status'] ?? 'active';
    $id = !empty($input['id']) ? (int) $input['id'] : 0;

    if ($employeeId <= 0 || empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'error' => 'Employee, start date, and end date are required.']);
        exit;
    }

    $validStatuses = ['active', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        $status = 'active';
    }

    session_start();
    $createdBy = $_SESSION['employee_id'] ?? null;

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE exit_knowledge_transfer_plans 
            SET employee_id = :employee_id,
                successor_id = :successor_id,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':id' => $id
        ]);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Plan updated.']);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO exit_knowledge_transfer_plans 
                (employee_id, successor_id, start_date, end_date, status, created_by)
            VALUES 
                (:employee_id, :successor_id, :start_date, :end_date, :status, :created_by)
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':successor_id' => $successorId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':status' => $status,
            ':created_by' => $createdBy
        ]);
        $newId = (int) $pdo->lastInsertId();

        // Whoever now has work to do gets told: the successor acts on the handover, and
        // with nobody named the plan is still about the exiting employee.
        try {
            (new Notification($pdo))->push(
                $successorId ?? $employeeId,
                'knowledge_transfer',
                $successorId
                    ? 'Knowledge transfer plan assigned to you'
                    : 'Knowledge transfer plan created for you',
                sprintf('Handover runs %s to %s.', $startDate, $endDate),
                'knowledge_transfer_plan',
                $newId
            );
        } catch (Throwable $e) {
            // The plan is saved either way — never fail the user's work over a notification.
            error_log('save-knowledge-transfer.php notification failed: ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Plan created.']);
    }
} catch (Throwable $e) {
    error_log('save-knowledge-transfer.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
