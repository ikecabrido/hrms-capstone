<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['employee_id'])) {
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

$input = json_decode(file_get_contents('php://input'), true);
$templateId = (int) ($input['id'] ?? 0);
$title = trim((string) ($input['title'] ?? ''));
$description = trim((string) ($input['description'] ?? ''));
$courseId = (int) ($input['course_id'] ?? 0);

if ($templateId <= 0 || $title === '' || mb_strlen($title) > 255 || $courseId < 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A valid template ID, title (up to 255 characters), and source course are required.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $exists = $pdo->prepare('SELECT id FROM ld_course_template WHERE id = :id LIMIT 1');
    $exists->execute([':id' => $templateId]);
    if (!$exists->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Template not found.']);
        exit;
    }

    $snapshot = $courseId > 0 ? CourseTemplateSnapshot::fromCourse($pdo, $courseId) : null;
    if ($courseId > 0 && !$snapshot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Source course not found.']);
        exit;
    }

    if ($snapshot) {
        $update = $pdo->prepare('UPDATE ld_course_template SET title = :title, description = :description, category = :category, structure_json = :structure, module_count = :module_count, lesson_count = :lesson_count, quiz_count = :quiz_count WHERE id = :id');
        $update->execute([
            ':title' => $title,
            ':description' => $description,
            ':category' => $snapshot['course']['category'],
            ':structure' => json_encode($snapshot['structure']),
            ':module_count' => $snapshot['module_count'],
            ':lesson_count' => $snapshot['lesson_count'],
            ':quiz_count' => $snapshot['quiz_count'],
            ':id' => $templateId,
        ]);
    } else {
        $update = $pdo->prepare('UPDATE ld_course_template SET title = :title, description = :description WHERE id = :id');
        $update->execute([
            ':title' => $title,
            ':description' => $description,
            ':id' => $templateId,
        ]);
    }

    echo json_encode([
        'success' => true,
        'id' => $templateId,
        'message' => $snapshot ? 'Template details and structure updated.' : 'Template details updated.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update template.']);
    error_log('edit-template.php failed: ' . $e->getMessage());
}
