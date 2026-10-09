<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/classes/CSRF.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

CSRF::requireValid();
$learnerId = (int) ($_SESSION['employee_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$courseId = (int) ($input['course_id'] ?? 0);
$sessionToken = (string) ($input['session_token'] ?? '');
$sequence = (int) ($input['sequence'] ?? 0);
$seconds = min(60, max(0, (int) ($input['seconds'] ?? 0)));

if ($learnerId <= 0 || $courseId <= 0 || !preg_match('/^[a-f0-9-]{36}$/i', $sessionToken) || $sequence <= 0 || $seconds <= 0) {
    http_response_code($learnerId > 0 ? 422 : 401);
    echo json_encode(['success' => false, 'message' => 'Invalid study-time record.']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();
    $enrollmentStmt = $pdo->prepare("SELECT id FROM ld_enrollment
                                     WHERE learner_id = :learner_id AND course_id = :course_id
                                       AND status IN ('enrolled', 'in_progress')
                                     LIMIT 1");
    $enrollmentStmt->execute([':learner_id' => $learnerId, ':course_id' => $courseId]);
    $enrollmentId = (int) $enrollmentStmt->fetchColumn();

    if ($enrollmentId <= 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Active course enrollment required.']);
        exit;
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO ld_study_time_log
        (learner_id, enrollment_id, session_token, sequence_no, active_seconds)
        VALUES (:learner_id, :enrollment_id, :session_token, :sequence_no, :active_seconds)');
    $insert->execute([
        ':learner_id' => $learnerId,
        ':enrollment_id' => $enrollmentId,
        ':session_token' => $sessionToken,
        ':sequence_no' => $sequence,
        ':active_seconds' => $seconds,
    ]);

    echo json_encode(['success' => true, 'recorded' => $insert->rowCount() > 0]);
} catch (Throwable $error) {
    error_log('[learning] Study-time record failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not record study time.']);
}