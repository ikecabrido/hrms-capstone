<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
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

    $status = isset($_GET['status'])
        ? trim((string) $_GET['status'])
        : null;

    $courseId = isset($_GET['course_id'])
        ? (int) $_GET['course_id']
        : 0;

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);

    /*
     * Single course lookup.
     */
    if ($courseId > 0) {
        $item = $enrollment->getByLearnerAndCourse(
            $learnerId,
            $courseId
        );

        echo json_encode([
            'success' => true,
            'item' => $item ?: null,
            'enrolled' => $item !== null
        ]);

        exit;
    }

    /*
     * Learner's enrollment list.
     */
    $items = $enrollment->getByLearner(
        $learnerId,
        $status
    );

    echo json_encode([
        'success' => true,
        'items' => array_map(
            static function (array $item): array {
                return [
                    'id' => (int) $item['id'],
                    'course_id' => (int) $item['course_id'],
                    'course_title' => trim(
                        (string) $item['course_title']
                    ),
                    'thumbnail_path' => $item['thumbnail_path'],
                    'status' => $item['status'],
                    'enrolled_at' => $item['enrolled_at'],
                    'completed_at' => $item['completed_at'],
                    'last_accessed_at' => $item['last_accessed_at']
                ];
            },
            $items
        ),
        'count' => count($items)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load enrollments.'
    ]);
}