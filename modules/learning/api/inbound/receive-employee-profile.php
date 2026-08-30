<?php
/**
 * Inbound: receive-employee-profile.php
 * Read-only snapshot of employee profile from Employee Management.
 * Feeds ld_prerequisite checks and learner profile display.
 * No local copy stored — read at time of use only (sync-as-snapshot rule).
 *
 * POST /api/inbound/receive-employee-profile.php
 * Header: X-API-Key: <key>
 * Body (JSON): { employee_id, first_name, last_name, email, department, position }
 *
 * Also supports GET for retrieving employee profile by ID:
 * GET /api/inbound/receive-employee-profile.php?employee_id=123
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'employee-management');

    $log = new IntegrationLog($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Read employee profile from em_employees
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT e.employee_id, e.first_name, e.last_name, e.email,
                   d.department_name, p.position_name
            FROM em_employees e
            LEFT JOIN em_departments d ON d.department_id = e.department_id
            LEFT JOIN em_positions p ON p.position_id = e.position_id
            WHERE e.employee_id = :eid
        ");
        $stmt->execute([':eid' => $employeeId]);
        $employee = $stmt->fetch();

        if (!$employee) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }

        // Get their enrolled courses
        $stmt2 = $pdo->prepare("
            SELECT c.id, c.title, c.category, en.status, en.enrolled_at
            FROM ld_enrollment en
            JOIN ld_course c ON c.id = en.course_id
            WHERE en.learner_id = :eid
            ORDER BY en.enrolled_at DESC
        ");
        $stmt2->execute([':eid' => $employeeId]);
        $enrollments = $stmt2->fetchAll();

        // Get their skills
        $stmt3 = $pdo->prepare("
            SELECT DISTINCT s.id, s.name
            FROM ld_skill s
            JOIN ld_course_skill cs ON cs.skill_id = s.id
            JOIN ld_enrollment en ON en.course_id = cs.course_id
            WHERE en.learner_id = :eid AND en.status = 'completed'
        ");
        $stmt3->execute([':eid' => $employeeId]);
        $skills = $stmt3->fetchAll();

        $log->logCall('inbound', 'employee-management', 'receive-employee-profile', 'success', ['employee_id' => $employeeId, 'action' => 'read']);

        echo json_encode([
            'success' => true,
            'employee' => $employee,
            'enrollments' => $enrollments,
            'completed_skills' => $skills,
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Receive profile snapshot (stored as-is for caching/validation)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['employee_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payload or missing employee_id']);
            exit;
        }

        $extRefId = "em-profile-{$input['employee_id']}";
        if ($log->isDuplicate('employee-management', $extRefId)) {
            echo json_encode(['success' => true, 'message' => 'Snapshot already received', 'duplicate' => true]);
            exit;
        }

        // Validate that employee exists in em_employees
        $stmt = $pdo->prepare("SELECT employee_id FROM em_employees WHERE employee_id = :eid");
        $stmt->execute([':eid' => (int)$input['employee_id']]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Employee not found in system']);
            exit;
        }

        $log->markProcessed('employee-management', $extRefId, 'employee_profile_snapshot');
        $log->logCall('inbound', 'employee-management', 'receive-employee-profile', 'success', $input);

        echo json_encode([
            'success' => true,
            'message' => 'Employee profile snapshot received',
            'employee_id' => (int)$input['employee_id'],
            'note' => 'Data is read at time of use only; no local copy stored.',
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    if (isset($log)) {
        $log->logCall('inbound', 'employee-management', 'receive-employee-profile', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
