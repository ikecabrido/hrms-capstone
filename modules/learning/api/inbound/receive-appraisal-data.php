<?php
/**
 * Inbound: receive-appraisal-data.php
 * Receives training recommendations from Performance Management.
 * If status = 'Approved', auto-assigns the recommended course via Enrollment::invite().
 * Source table: pm_training_recommendations (employee_id, development_area, priority_level, status)
 *
 * POST /api/inbound/receive-appraisal-data.php
 * Header: X-API-Key: <key>
 * Body (JSON): { recommendation_id, employee_id, development_area, priority_level, status, recommendation_reason }
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

    // 1. Authenticate
    ApiAuth::requireAuth($pdo, 'performance-management');

    // 2. Parse payload
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    // 3. Validate required fields
    $requiredFields = ['recommendation_id', 'employee_id', 'development_area', 'status'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    $recommendationId = (int)$input['recommendation_id'];
    $employeeId = (int)$input['employee_id'];
    $developmentArea = $input['development_area'];
    $status = $input['status'];
    $priorityLevel = $input['priority_level'] ?? 'Medium';
    $reason = $input['recommendation_reason'] ?? '';

    // 4. Idempotency check
    $log = new IntegrationLog($pdo);
    $extRefId = "pm-rec-{$recommendationId}";
    if ($log->isDuplicate('performance-management', $extRefId)) {
        echo json_encode(['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        exit;
    }

    // 5. Only process Approved recommendations
    if ($status !== 'Approved') {
        $log->logCall('inbound', 'performance-management', 'receive-appraisal-data', 'success', $input);
        echo json_encode(['success' => true, 'message' => "Recommendation status is '$status', no action taken"]);
        exit;
    }

    // 6. Look up mapped courses for this development area
    $courseMap = new CourseMap($pdo);
    $courses = $courseMap->getCoursesForDevelopmentArea($developmentArea);

    $enrolled = 0;
    $enrollmentResults = [];
    $enrollment = new Enrollment($pdo);

    foreach ($courses as $course) {
        $result = $enrollment->invite($employeeId, (int)$course['course_id'], 0); // invitedBy=0 (system)
        $enrollmentResults[] = [
            'course_id' => (int)$course['course_id'],
            'course_title' => $course['course_title'],
            'result' => $result,
        ];
        if ($result['success']) $enrolled++;
    }

    // 7. Mark as processed
    $log->markProcessed('performance-management', $extRefId, 'appraisal_recommendation');
    $log->logCall('inbound', 'performance-management', 'receive-appraisal-data', 'success', $input);

    echo json_encode([
        'success' => true,
        'message' => "Processed recommendation #$recommendationId",
        'courses_found' => count($courses),
        'enrolled' => $enrolled,
        'details' => $enrollmentResults,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'performance-management', 'receive-appraisal-data', 'failed', $input ?? null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
