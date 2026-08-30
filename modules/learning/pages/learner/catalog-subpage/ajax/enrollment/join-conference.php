<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['video_conference_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Video conference ID is required']);
        exit;
    }
    
    $learnerId = (int) $_SESSION['employee_id'];
    $videoConferenceId = (int) $data['video_conference_id'];
    
    if ($videoConferenceId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid video conference ID']);
        exit;
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if conference exists and is scheduled
    $stmt = $pdo->prepare("SELECT id, scheduled_at, status FROM ld_video_conference WHERE id = :id");
    $stmt->execute([':id' => $videoConferenceId]);
    $conference = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conference) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Video conference not found']);
        exit;
    }
    
    if ($conference['status'] !== 'scheduled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This session is no longer available']);
        exit;
    }
    
    // Check if session has already passed
    $scheduledAt = strtotime($conference['scheduled_at']);
    if ($scheduledAt < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This session has already ended']);
        exit;
    }
    
    // Check if already attended
    $stmt = $pdo->prepare("SELECT id, attended FROM ld_conference_attendance WHERE video_conference_id = :vcid AND learner_id = :lid LIMIT 1");
    $stmt->execute([':vcid' => $videoConferenceId, ':lid' => $learnerId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && $existing['attended']) {
        // Already attended, just return success with the meeting link
        $stmt = $pdo->prepare("SELECT meeting_link FROM ld_video_conference WHERE id = :id");
        $stmt->execute([':id' => $videoConferenceId]);
        $link = $stmt->fetchColumn();
        echo json_encode(['success' => true, 'message' => 'You have already joined this session', 'meeting_link' => $link]);
        exit;
    }
    
    // Insert or update attendance record
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE ld_conference_attendance SET attended = 1, joined_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO ld_conference_attendance (video_conference_id, learner_id, attended, joined_at) VALUES (:vcid, :lid, 1, NOW())");
        $stmt->execute([':vcid' => $videoConferenceId, ':lid' => $learnerId]);
    }
    
    // Get meeting link
    $stmt = $pdo->prepare("SELECT meeting_link FROM ld_video_conference WHERE id = :id");
    $stmt->execute([':id' => $videoConferenceId]);
    $meetingLink = $stmt->fetchColumn();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Attendance recorded successfully',
        'meeting_link' => $meetingLink
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to join session: ' . $e->getMessage()]);
}
?>
