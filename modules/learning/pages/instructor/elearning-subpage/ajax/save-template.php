<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__FILE__, 7) . '/database/db.php';
require_once dirname(__FILE__, 5) . '/classes/CourseTemplateSnapshot.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $employeeId = (int) $_SESSION['employee_id'];

    $input = json_decode(file_get_contents('php://input'), true);
    $courseId = isset($input['course_id']) ? (int) $input['course_id'] : 0;
    $templateTitle = trim((string) ($input['title'] ?? ''));
    $templateDesc = trim((string) ($input['description'] ?? ''));

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'course_id is required.']);
        exit;
    }

    $snapshot = CourseTemplateSnapshot::fromCourse($pdo, $courseId);
    if (!$snapshot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found.']);
        exit;
    }

    $course = $snapshot['course'];
    $structure = $snapshot['structure'];
    $moduleCount = $snapshot['module_count'];
    $lessonCount = $snapshot['lesson_count'];
    $quizCount = $snapshot['quiz_count'];

    if ($templateTitle === '') {
        $templateTitle = ($course['title'] ?? 'Untitled') . ' — Template';
    }

    $stmt = $pdo->prepare("INSERT INTO ld_course_template (title, description, category, created_by, structure_json, module_count, lesson_count, quiz_count) VALUES (:title, :desc, :cat, :creator, :json, :mc, :lc, :qc)");
    $stmt->execute([
        ':title' => $templateTitle,
        ':desc' => $templateDesc !== '' ? $templateDesc : ($course['description'] ?? ''),
        ':cat' => $course['category'] ?? null,
        ':creator' => $employeeId,
        ':json' => json_encode($structure),
        ':mc' => $moduleCount,
        ':lc' => $lessonCount,
        ':qc' => $quizCount,
    ]);

    $templateId = (int) $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id' => $templateId,
        'message' => 'Template saved with ' . $moduleCount . ' modules, ' . $lessonCount . ' lessons, ' . $quizCount . ' quizzes.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
