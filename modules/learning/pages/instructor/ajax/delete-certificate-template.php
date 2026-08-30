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

if ($templateId <= 0) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid template ID']));
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

    $stmt = $pdo->prepare("DELETE FROM ld_certificate_template WHERE id = :id AND instructor_id = :iid");
    $stmt->execute([':id' => $templateId, ':iid' => $employeeId]);

    echo json_encode([
        'success' => true,
        'message' => 'Template deleted successfully',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete template: ' . $e->getMessage()]);
}
