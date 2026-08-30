<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/progress.php';
require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $courseId = (int) ($_GET['course_id'] ?? 0);

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Course ID is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $enrollmentRow = $enrollment->getByLearnerAndCourse($learnerId, $courseId);

    if (!$enrollmentRow) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course.']);
        exit;
    }

    $progress = new Progress($pdo);
    $enrollmentId = (int) $enrollmentRow['id'];

    $percentComplete = $progress->getPercentComplete($enrollmentId, $courseId);
    $items = $progress->getByEnrollment($enrollmentId);

    echo json_encode([
        'success' => true,
        'enrollment_status' => $enrollmentRow['status'],
        'percent_complete' => $percentComplete,
        'items' => array_map(function ($item) {
            return [
                'item_type' => $item['item_type'],
                'reference_id' => (int) $item['reference_id'],
                'status' => $item['status'],
                'completed_at' => $item['completed_at'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load progress.',
        'error' => $e->getMessage(),
    ]);
}