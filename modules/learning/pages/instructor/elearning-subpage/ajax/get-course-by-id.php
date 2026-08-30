<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $course = new Course($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Course ID required']);
        exit;
    }

    $courseData = $course->getById($id);

    if (!$courseData) {
        http_response_code(404);
        echo json_encode(['error' => 'Course not found']);
        exit;
    }

    // Get real counts from database
    $moduleCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_module WHERE course_id = {$id}")->fetchColumn();
    $enrollmentCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE course_id = {$id}")->fetchColumn();
    $completionRate = 0;
    if ($enrollmentCount > 0) {
        $completedCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE course_id = {$id} AND status = 'completed'")->fetchColumn();
        $completionRate = round(($completedCount / $enrollmentCount) * 100);
    }

    $courseData['module_count'] = $moduleCount;
    $courseData['enrollment_count'] = $enrollmentCount;
    $courseData['completion_rate'] = $completionRate;

    // Get linked skill IDs
    $skillIds = $pdo->prepare('SELECT skill_id FROM ld_course_skill WHERE course_id = :cid');
    $skillIds->execute([':cid' => $id]);
    $courseData['skill_ids'] = array_map('intval', $skillIds->fetchAll(PDO::FETCH_COLUMN));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $courseData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
