<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $course = new Course($pdo);

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $learningRole = strtolower((string) ($_SESSION['learning_role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''));

    $result = $course->create($_POST, $_FILES, $instructorId, $learningRole);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    $statusCode = 401;
    if (strpos($result['message'] ?? '', 'required') !== false) {
        $statusCode = 422;
    }

    http_response_code($statusCode);
    echo json_encode($result);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create course.',
        'error' => $e->getMessage(),
    ]);
    exit;
}
