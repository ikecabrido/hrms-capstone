<?php
/**
 * Inbound: receive-recognition-eligibility.php
 * Receives recognition approvals from Employee Engagement (eer_recognitions).
 * If status = 'approved', auto-enrolls the recipient in category-matched courses.
 * Source table: eer_recognitions (receiver_id, status, category)
 *
 * POST /api/inbound/receive-recognition-eligibility.php
 * Header: X-API-Key: <key>
 * Body (JSON): { recognition_id, receiver_id, status, category, points }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 3) . '/classes/enrollment.php';
require_once dirname(__FILE__, 3) . '/classes/coursemap.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'employee-engagement');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $requiredFields = ['recognition_id', 'receiver_id', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    $recognitionId = (int)$input['recognition_id'];
    $receiverId = (int)$input['receiver_id'];
    $status = $input['status'];
    $category = $input['category'] ?? 'general';
    $points = (int)($input['points'] ?? 0);

    // Idempotency check
    $log = new IntegrationLog($pdo);
    $extRefId = "eer-rec-{$recognitionId}";
    if ($log->isDuplicate('employee-engagement', $extRefId)) {
        echo json_encode(['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        exit;
    }

    // Only process approved recognitions
    if ($status !== 'approved') {
        $log->logCall('inbound', 'employee-engagement', 'receive-recognition-eligibility', 'success', $input);
        echo json_encode(['success' => true, 'message' => "Recognition status is '$status', no action taken"]);
        exit;
    }

    // Look up mapped courses for this recognition category
    $courseMap = new CourseMap($pdo);
    $courses = $courseMap->getCoursesForRecognitionCategory($category);

    $enrolled = 0;
    $enrollmentResults = [];
    $enrollment = new Enrollment($pdo);

    foreach ($courses as $course) {
        $result = $enrollment->invite($receiverId, (int)$course['course_id'], 0);
        $enrollmentResults[] = [
            'course_id' => (int)$course['course_id'],
            'course_title' => $course['course_title'],
            'result' => $result,
        ];
        if ($result['success']) $enrolled++;
    }

    $log->markProcessed('employee-engagement', $extRefId, 'recognition_eligibility');
    $log->logCall('inbound', 'employee-engagement', 'receive-recognition-eligibility', 'success', $input);

    echo json_encode([
        'success' => true,
        'message' => "Processed recognition #$recognitionId for employee #$receiverId",
        'recognition_category' => $category,
        'points' => $points,
        'courses_found' => count($courses),
        'enrolled' => $enrolled,
        'details' => $enrollmentResults,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'employee-engagement', 'receive-recognition-eligibility', 'failed', $input ?? null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
