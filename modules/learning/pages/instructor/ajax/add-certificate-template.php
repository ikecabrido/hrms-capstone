<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

if (session_status() === PHP_SESSION_NONE) session_start();
$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) {
    http_response_code(401);
    die(json_encode(['error' => 'Not authenticated']));
}

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$courseId    = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;

if ($title === '') {
    http_response_code(400);
    die(json_encode(['error' => 'Title is required']));
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("
        INSERT INTO ld_certificate_template
            (instructor_id, course_id, title, description, is_active, created_at, updated_at)
        VALUES
            (:iid, :cid, :title, :desc, 1, NOW(), NOW())
    ");
    $stmt->execute([
        ':iid'   => $employeeId,
        ':cid'   => $courseId,
        ':title' => $title,
        ':desc'  => $description,
    ]);

    $newId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id'      => (int)$newId,
        'message' => 'Template created successfully',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create template: ' . $e->getMessage()]);
}
