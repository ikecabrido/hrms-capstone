<?php

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

if (!($db instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$complaintId = isset($_GET['complaint_id']) ? (int) $_GET['complaint_id'] : 0;

if ($complaintId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid complaint ID.']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT c.id, c.employee_id, c.respondent_employee_id, c.status, c.termination_recommended_at, c.employee_response, c.employee_response_date
        FROM lc_complaints c
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $complaintId]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
        exit;
    }

    if ($complaint['status'] !== 'closed_termination_recommended') {
        echo json_encode(['success' => false, 'message' => 'Complaint is not in termination recommended status.']);
        exit;
    }

    if (empty($complaint['termination_recommended_at'])) {
        echo json_encode(['success' => false, 'message' => 'Termination recommendation date not set.']);
        exit;
    }

    $recommendedDate = new DateTime($complaint['termination_recommended_at']);
    $currentDate = new DateTime();
    $interval = $recommendedDate->diff($currentDate);
    $calendarDays = (int)$interval->format('%a');

    $businessDays = 0;
    $checkDate = clone $recommendedDate;
    while ($businessDays < 5) {
        $checkDate->modify('+1 day');
        $dayOfWeek = (int)$checkDate->format('N');
        if ($dayOfWeek < 6) {
            $businessDays++;
        }
    }

    $deadline = clone $recommendedDate;
    $deadline->modify('+' . $calendarDays . ' days');

    if ($currentDate < $deadline) {
        echo json_encode([
            'success' => true,
            'message' => 'Within 5 business days. Deadline: ' . $deadline->format('M d, Y'),
            'days_remaining' => (int)$deadline->diff($currentDate)->format('%a'),
            'deadline' => $deadline->format('Y-m-d')
        ]);
        exit;
    }

    $hasResponse = !empty($complaint['employee_response']) || !empty($complaint['termination_reply']);
    if ($hasResponse) {
        echo json_encode([
            'success' => true,
            'message' => 'Letter of intent already submitted.',
            'response_date' => $complaint['employee_response_date'] ?? null
        ]);
        exit;
    }

    $employeeId = !empty($complaint['respondent_employee_id']) ? (int)$complaint['respondent_employee_id'] : (int)$complaint['employee_id'];
    $userId = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;

    $checkStmt = $db->prepare("SELECT id FROM exit_terminations WHERE employee_id = :eid AND comments LIKE :ref LIMIT 1");
    $checkStmt->execute([
        ':eid' => $employeeId,
        ':ref' => '%Complaint#' . $complaintId . '%'
    ]);
    if ($checkStmt->fetchColumn()) {
        echo json_encode([
            'success' => true,
            'message' => 'Exit termination already created for this case.',
            'employee_id' => $employeeId
        ]);
        exit;
    }

    $db->beginTransaction();

    $terminationReason = "Termination recommendation from Complaint #" . $complaintId . " (" . ($complaint['type'] ?? 'Administrative Case') . "). No letter of intent submitted within 5 business days from " . $recommendedDate->format('M d, Y') . ".";

    $effectiveDate = clone $recommendedDate;
    $effectiveDate->modify('+1 day');
    while ((int)$effectiveDate->format('N') > 5) {
        $effectiveDate->modify('+1 day');
    }

    $insert = $db->prepare("
        INSERT INTO exit_terminations
            (employee_id, termination_reason, effective_date, comments, submitted_by, status, created_at, updated_at)
        VALUES
            (:employee_id, :termination_reason, :effective_date, :comments, :submitted_by, 'pending_review', NOW(), NOW())
    ");

    $insert->execute([
        ':employee_id' => $employeeId,
        ':termination_reason' => $terminationReason,
        ':effective_date' => $effectiveDate->format('Y-m-d'),
        ':comments' => "Auto-generated from Complaint #" . $complaintId . ". No letter of intent submitted within 5 business days.",
        ':submitted_by' => $userId,
    ]);

    $exitId = (int)$db->lastInsertId();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Exit termination record created automatically.',
        'exit_termination_id' => $exitId,
        'employee_id' => $employeeId,
        'effective_date' => $effectiveDate->format('Y-m-d'),
        'complaint_id' => $complaintId
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create exit termination: ' . $e->getMessage()
    ]);
    exit;
}
