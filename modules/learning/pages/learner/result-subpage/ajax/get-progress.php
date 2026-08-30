<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/progress.php';
require_once dirname(__FILE__, 5) . '/classes/enrollment.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $enrollment = new Enrollment($pdo);
    $progress = new Progress($pdo);

    $enrollments = $enrollment->getByLearner($learnerId);

    $items = array_map(function ($e) use ($progress) {
        return [
            'enrollment_id' => (int) $e['id'],
            'course_id' => (int) $e['course_id'],
            'course_title' => $e['course_title'],
            'status' => $e['status'],
            'percent_complete' => $progress->getPercentComplete((int) $e['id'], (int) $e['course_id']),
        ];
    }, $enrollments);

    echo json_encode([
        'success' => true,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load progress.',
        'error' => $e->getMessage(),
    ]);
}
