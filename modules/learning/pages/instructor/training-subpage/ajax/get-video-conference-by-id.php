<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/videoconference.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $conference = new VideoConference($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Video conference ID required']);
        exit;
    }

    $conferenceData = $conference->getById($id);

    if (!$conferenceData) {
        http_response_code(404);
        echo json_encode(['error' => 'Video conference not found']);
        exit;
    }
    // Get real attendance count
    $attendanceCount = (int) $pdo->query("SELECT COUNT(DISTINCT learner_id) FROM ld_grade WHERE video_conference_id = {$id}")->fetchColumn();
    $videoConferenceData['attendance_count'] = $attendanceCount;
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $conferenceData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

