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

    // Skills are tags, not their own enrollable entity — derived here via
    // ld_course_skill (skills tagged directly on the course) UNION
    // ld_module_skill (skills tagged on the course's modules).
    $stmt = $pdo->prepare(
        "SELECT DISTINCT s.id, s.name, s.description
         FROM ld_skill s
         WHERE s.id IN (
             SELECT skill_id FROM ld_course_skill WHERE course_id = :course_id_1
             UNION
             SELECT ms.skill_id FROM ld_module_skill ms
             JOIN ld_module m ON m.id = ms.module_id
             WHERE m.course_id = :course_id_2
         )
         AND s.status = 'active'"
    );
    $stmt->execute([':course_id_1' => $courseId, ':course_id_2' => $courseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load skills.',
        'error' => $e->getMessage(),
    ]);
}
