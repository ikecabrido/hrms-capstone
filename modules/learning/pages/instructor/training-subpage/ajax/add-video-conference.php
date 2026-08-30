<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/videoconference.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $conference = new VideoConference($pdo);

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $learningRole = strtolower((string) ($_SESSION['learning_role'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? ''));
    $result = $conference->create([
        'instructor_id' => $instructorId,
        'course_id' => $_POST['course_id'] ?? '',
        'program_id' => $_POST['program_id'] ?? '',
        'title' => $_POST['title'] ?? '',
        'platform' => $_POST['platform'] ?? 'google_meet',
        'meeting_link' => $_POST['meeting_link'] ?? '',
        'scheduled_at' => $_POST['scheduled_at'] ?? '',
        'duration_minutes' => $_POST['duration_minutes'] ?? '',
        'status' => $_POST['status'] ?? 'scheduled',
        'learning_role' => $learningRole,
        'is_admin' => !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_access']),
    ], $instructorId);

    if (!empty($result['success'])) {
        // Save skill associations via course if linked
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
        'message' => 'Failed to create video conference.',
        'error' => $e->getMessage(),
    ]);
    exit;
}
