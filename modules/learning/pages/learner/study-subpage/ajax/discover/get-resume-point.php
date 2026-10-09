<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

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

    $enrollmentId = (int) $enrollmentRow['id'];

    // Find the first Lesson (in module/lesson order) that has no 'completed'
    // progress row for this enrollment — that's the resume point.
    $stmt = $pdo->prepare(
        "SELECT l.id AS lesson_id, l.module_id, m.id AS module_id_check, l.title AS lesson_title, m.title AS module_title
         FROM ld_lesson l
         JOIN ld_module m ON m.id = l.module_id
         WHERE m.course_id = :course_id AND l.status = 'active' AND m.status = 'active'
           AND l.id NOT IN (
               SELECT reference_id FROM ld_progress
               WHERE enrollment_id = :enrollment_id AND item_type = 'lesson' AND status = 'completed'
           )
         ORDER BY m.order_index ASC, l.order_index ASC
         LIMIT 1"
    );
    $stmt->execute([':course_id' => $courseId, ':enrollment_id' => $enrollmentId]);
    $next = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$next) {
        // Every lesson is complete — nothing left to resume.
        echo json_encode([
            'success' => true,
            'resume_point' => null,
            'message' => 'All lessons in this course have been completed.',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'resume_point' => [
            'module_id' => (int) $next['module_id'],
            'module_title' => $next['module_title'],
            'lesson_id' => (int) $next['lesson_id'],
            'lesson_title' => $next['lesson_title'],
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to determine resume point.',
        'error' => $e->getMessage(),
    ]);
}
