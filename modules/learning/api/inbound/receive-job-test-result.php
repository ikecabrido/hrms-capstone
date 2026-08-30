<?php
/**
 * Inbound: receive-job-test-result.php
 * Receives job test results from Recruitment module.
 *
 * Accepts test results from Recruitment. If a recommended_learning_path_id is provided
 * and the candidate passed, Learning auto-invites the employee to the path's courses.
 * The Recruitment module is responsible for calling this endpoint from their own schema.
 *
 * POST /api/inbound/receive-job-test-result.php
 * Header: X-API-Key: <key>
 * Body (JSON): { candidate_id, employee_id, test_name, score, passed, recommended_learning_path_id }
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
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'recruitment');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    // Validate required fields
    if (empty($input['employee_id']) || empty($input['test_name'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: employee_id, test_name']);
        exit;
    }

    $employeeId = (int)$input['employee_id'];
    $candidateId = $input['candidate_id'] ?? null;
    $testName = $input['test_name'];
    $score = isset($input['score']) ? (float)$input['score'] : null;
    $passed = (bool)($input['passed'] ?? false);
    $recommendedPathId = !empty($input['recommended_learning_path_id']) ? (int)$input['recommended_learning_path_id'] : null;

    // Idempotency check
    $log = new IntegrationLog($pdo);
    $extRefId = "lc-test-{$employeeId}-" . md5($testName . ($candidateId ?? ''));
    if ($log->isDuplicate('recruitment', $extRefId)) {
        echo json_encode(['success' => true, 'message' => 'Already processed', 'duplicate' => true]);
        exit;
    }

    $actions = [];

    // If a learning path is recommended, enroll the employee in its courses
    if ($recommendedPathId && $passed) {
        $stmt = $pdo->prepare("
            SELECT lpi.course_id, c.title
            FROM ld_learning_path_item lpi
            JOIN ld_course c ON c.id = lpi.course_id
            WHERE lpi.learning_path_id = :lpid
            ORDER BY lpi.order_index ASC
        ");
        $stmt->execute([':lpid' => $recommendedPathId]);
        $courses = $stmt->fetchAll();

        $enrollment = new Enrollment($pdo);
        foreach ($courses as $course) {
            $result = $enrollment->invite($employeeId, (int)$course['course_id'], 0);
            $actions[] = [
                'action' => 'enrolled_in_course',
                'course_id' => (int)$course['course_id'],
                'course_title' => $course['title'],
                'result' => $result,
            ];
        }

        $actions[] = [
            'action' => 'learning_path_assigned',
            'learning_path_id' => $recommendedPathId,
            'courses_count' => count($courses),
        ];
    }

    $log->markProcessed('recruitment', $extRefId, 'job_test_result');
    $log->logCall('inbound', 'recruitment', 'receive-job-test-result', 'success', $input);

    echo json_encode([
        'success' => true,
        'message' => "Job test result received for employee #$employeeId",
        'test_name' => $testName,
        'score' => $score,
        'passed' => $passed,
        'actions_taken' => $actions,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'recruitment', 'receive-job-test-result', 'failed', $input ?? null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
