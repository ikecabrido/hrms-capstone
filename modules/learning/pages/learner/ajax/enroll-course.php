<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include_once dirname(__DIR__, 3) . '/classes/enrollment.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['course_id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Course ID is required']));
    }

    $learnerId = (int) $_SESSION['employee_id'];
    $courseId = (int) $data['course_id'];

    if ($courseId <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid course ID']));
    }

    $enrollment = new Enrollment();
    $result = $enrollment->enroll($learnerId, $courseId);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
