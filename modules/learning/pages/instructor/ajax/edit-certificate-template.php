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

$templateId = (int)($_POST['template_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$courseId    = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;

if ($templateId <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid template ID']));
}

if ($title === '') {
    http_response_code(400);
    die(json_encode(['error' => 'Title is required']));
}

try {
    $pdo = (new Database())->getConnection();

    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM ld_certificate_template WHERE id = :id AND instructor_id = :iid");
    $stmt->execute([':id' => $templateId, ':iid' => $employeeId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        die(json_encode(['error' => 'Template not found or access denied']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_certificate_template
        SET title = :title, description = :desc, course_id = :cid, updated_at = NOW()
        WHERE id = :id AND instructor_id = :iid
    ");
    $stmt->execute([
        ':id'   => $templateId,
        ':iid'  => $employeeId,
        ':title' => $title,
        ':desc'  => $description,
        ':cid'   => $courseId,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Template updated successfully',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update template: ' . $e->getMessage()]);
}
