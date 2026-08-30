<?php
header('Content-Type: application/json');
session_start();

include_once dirname(__DIR__, 3) . '/classes/employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
require_once dirname(__DIR__, 3) . '/classes/enrollment.php';

try {
    $employeeClass = new Employee();
    $instructorId = (int) ($employeeClass->getEmployeeId() ?? 0);

    if ($instructorId <= 0) {
        http_response_code(401);
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $learnerIds = $input['learner_ids'] ?? [];
    $courseId = (int) ($input['course_id'] ?? 0);
    $mode = $input['mode'] ?? 'invite';

    if (empty($learnerIds) || $courseId <= 0) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'learner_ids and course_id are required']));
    }

    $pdo = (new Database())->getConnection();
    $enrollment = new Enrollment($pdo);

    // Verify course exists
    $stmt = $pdo->prepare("SELECT id FROM ld_course WHERE id = :cid");
    $stmt->execute([':cid' => $courseId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Course not found']));
    }

    $results = ['enrolled' => 0, 'skipped' => 0, 'failed' => 0, 'details' => []];

    foreach ($learnerIds as $learnerId) {
        $lid = (int) $learnerId;
        if ($lid <= 0) continue;

        try {
            if ($mode === 'direct') {
                $result = $enrollment->enroll($lid, $courseId);
            } else {
                $result = $enrollment->invite($lid, $courseId, $instructorId);
            }

            if (!empty($result['success'])) {
                $results['enrolled']++;
                $results['details'][] = ['learner_id' => $lid, 'status' => 'ok', 'message' => $result['message']];
            } else {
                $results['skipped']++;
                $results['details'][] = ['learner_id' => $lid, 'status' => 'skipped', 'message' => $result['message']];
            }
        } catch (Throwable $e) {
            $results['failed']++;
            $results['details'][] = ['learner_id' => $lid, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => "Processed: {$results['enrolled']} enrolled, {$results['skipped']} skipped, {$results['failed']} failed", 'results' => $results]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
