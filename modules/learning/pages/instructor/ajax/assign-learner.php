<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/enrollment.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $enrollment = new Enrollment($pdo);

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($instructorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $learnerId = (int) ($_POST['learner_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($learnerId <= 0 || $courseId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Learner and Course are required.']);
        exit;
    }

    $result = $enrollment->invite($learnerId, $courseId, $instructorId);

    if (!empty($result['success'])) {
        echo json_encode($result);
        exit;
    }

    http_response_code(422);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send invitation.',
        'error' => $e->getMessage(),
    ]);
}
exit;