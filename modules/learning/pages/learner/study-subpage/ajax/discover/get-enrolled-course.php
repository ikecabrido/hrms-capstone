<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/classes/progress.php';
require_once dirname(__FILE__, 6) . '/classes/course.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id'])
        ? (int) $_SESSION['employee_id']
        : 0;

    if ($learnerId <= 0) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);

        exit;
    }

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $status = isset($_GET['status'])
        ? trim((string) $_GET['status'])
        : '';

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $progress = new Progress($pdo);

    /*
     * Single enrolled course.
     */
    if ($courseId > 0) {
        $enrollmentRow = $enrollment->getByLearnerAndCourse(
            $learnerId,
            $courseId
        );

        if (!$enrollmentRow) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'You are not enrolled in this course.'
            ]);

            exit;
        }

        if ($enrollmentRow['status'] === 'withdrawn') {
            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'This enrollment is no longer active.'
            ]);

            exit;
        }

        $courseStmt = $pdo->prepare("
            SELECT
                id,
                title,
                description,
                thumbnail_path,
                category,
                status,
                start_date,
                enrollment_deadline,
                created_at,
                updated_at
            FROM ld_course
            WHERE id = :course_id
            LIMIT 1
        ");

        $courseStmt->execute([
            ':course_id' => $courseId
        ]);

        $course = $courseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' => 'Course not found.'
            ]);

            exit;
        }

        $percentage = $progress->getPercentComplete(
            (int) $enrollmentRow['id'],
            $courseId
        );

        $enrollmentRow['progress_percent'] = $percentage;

        echo json_encode([
            'success' => true,
            'item' => $course,
            'enrollment' => $enrollmentRow
        ]);

        exit;
    }

    /*
     * Learner's enrolled courses.
     */
    $items = $enrollment->getByLearner(
        $learnerId,
        $status !== '' ? $status : null
    );

    /*
     * Withdrawn enrollments do not belong in Study.
     */
    $items = array_values(array_filter(
        $items,
        static function (array $item): bool {
            return ($item['status'] ?? '') !== 'withdrawn';
        }
    ));

    foreach ($items as &$item) {
        $item['progress_percent'] = $progress->getPercentComplete(
            (int) $item['id'],
            (int) $item['course_id']
        );
    }

    unset($item);

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load enrolled courses.'
    ]);
}