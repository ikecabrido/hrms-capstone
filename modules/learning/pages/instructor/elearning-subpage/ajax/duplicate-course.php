<?php
/**
 * Duplicate a course: clones course + modules + lessons + quizzes with status = draft.
 * POST /api/instructor/elearning-subpage/ajax/duplicate-course.php
 * Body: { course_id: int }
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $courseId = (int)($input['course_id'] ?? 0);
    $instructorId = (int)($_SESSION['employee_id'] ?? 0);

    if ($courseId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing course_id']);
        exit;
    }

    $course = new Course($pdo);
    $result = $course->duplicate($courseId, $instructorId);

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
