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

    // No dedicated class method exists for filtering by course_id yet,
    // so this queries ld_video_conference directly.
    $stmt = $pdo->prepare(
        "SELECT * FROM ld_video_conference WHERE course_id = :course_id AND status != 'archived' ORDER BY scheduled_at ASC"
    );
    $stmt->execute([':course_id' => $courseId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'title' => $item['title'],
                'platform' => $item['platform'],
                'meeting_link' => $item['meeting_link'],
                'scheduled_at' => $item['scheduled_at'],
                'duration_minutes' => $item['duration_minutes'] !== null ? (int) $item['duration_minutes'] : null,
                'status' => $item['status'],
            ];
        }, $items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load video conferences.',
        'error' => $e->getMessage(),
    ]);
}
