<?php
session_start();

require_once dirname(__FILE__, 6) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/classes/lesson.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $fileId = (int) ($_GET['file_id'] ?? 0);
    $courseId = (int) ($_GET['course_id'] ?? 0);

    if ($fileId <= 0 || $courseId <= 0) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'File ID and Course ID are required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    // Confirm the learner is enrolled in the course this material belongs to
    // before serving anything.
    $enrollment = new Enrollment($pdo);
    $enrollmentRow = $enrollment->getByLearnerAndCourse($learnerId, $courseId);

    if (!$enrollmentRow) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course.']);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT lf.*, l.module_id
         FROM ld_lesson_file lf
         JOIN ld_lesson l ON l.id = lf.lesson_id
         WHERE lf.id = :file_id
         LIMIT 1'
    );
    $stmt->execute([':file_id' => $fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        exit;
    }

    // Confirm the lesson's module actually belongs to the course the learner is enrolled in.
    $moduleCheck = $pdo->prepare('SELECT course_id FROM ld_module WHERE id = :module_id LIMIT 1');
    $moduleCheck->execute([':module_id' => $file['module_id']]);
    $module = $moduleCheck->fetch(PDO::FETCH_ASSOC);

    if (!$module || (int) $module['course_id'] !== $courseId) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'This file does not belong to the specified course.']);
        exit;
    }

    $absolutePath = dirname(__FILE__, 6) . '/assets/uploads/lesson-materials/' . basename($file['file_path']);

    if (!file_exists($absolutePath)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'File is missing from storage.']);
        exit;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['title']) . '"');
    header('Content-Length: ' . filesize($absolutePath));
    readfile($absolutePath);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to download material.',
        'error' => $e->getMessage(),
    ]);
}
