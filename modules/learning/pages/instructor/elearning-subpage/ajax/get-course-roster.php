<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/enrollment.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($instructorId <= 0) {
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

    $status = isset($_GET['status']) ? trim((string) $_GET['status']) : null;

    $database = new Database();
    $pdo = $database->getConnection();
    $enrollment = new Enrollment($pdo);

    // NOTE: verify course ownership (course_id belongs to this instructor, or
    // instructor is admin / co-instructor via ld_course_instructor) before returning
    // roster data — add that check here once Course::isOwnedBy() or similar exists.

    $items = $enrollment->getByCourse($courseId, $status);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'learner_id' => (int) $item['learner_id'],
                'name' => trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')),
                'email' => $item['email'] ?? null,
                'status' => $item['status'],
                'enrolled_at' => $item['enrolled_at'],
                'completed_at' => $item['completed_at'],
                'last_accessed_at' => $item['last_accessed_at'],
            ];
        }, $items),
        'total' => count($items),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load course roster.',
        'error' => $e->getMessage(),
    ]);
}