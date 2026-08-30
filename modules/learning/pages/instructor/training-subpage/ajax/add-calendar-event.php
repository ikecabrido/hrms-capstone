<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 7) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $instructorId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
    $type = trim((string) ($_POST['type'] ?? 'program'));
    $referenceId = isset($_POST['reference_id']) ? (int) $_POST['reference_id'] : 0;
    $eventDate = trim((string) ($_POST['event_date'] ?? ''));
    $eventTime = trim((string) ($_POST['event_time'] ?? ''));
    $durationMinutes = isset($_POST['duration_minutes']) && $_POST['duration_minutes'] !== '' ? (int) $_POST['duration_minutes'] : null;
    $status = trim((string) ($_POST['status'] ?? 'active'));

    if ($instructorId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    if (!in_array($type, ['program', 'training', 'video-conference'], true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Event type is invalid.']);
        exit;
    }

    if ($referenceId <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Reference ID is required.']);
        exit;
    }

    if ($eventDate === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Event date is required.']);
        exit;
    }

    if (!in_array($status, ['active', 'archived'], true)) {
        $status = 'active';
    }

    $sql = 'INSERT INTO ld_calendar_event (instructor_id, type, reference_id, event_date, event_time, duration_minutes, status) VALUES (:instructor_id, :type, :reference_id, :event_date, :event_time, :duration_minutes, :status)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':instructor_id' => $instructorId,
        ':type' => $type,
        ':reference_id' => $referenceId,
        ':event_date' => $eventDate,
        ':event_time' => $eventTime !== '' ? $eventTime : null,
        ':duration_minutes' => $durationMinutes,
        ':status' => $status,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Calendar event created successfully.',
        'id' => (int) $pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create calendar event.',
        'error' => $e->getMessage(),
    ]);
}
