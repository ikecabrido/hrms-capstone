<?php
/**
 * Outbound: get-compliance-training-log.php
 * Returns compliance-tagged training completions for Learning Compliance (lc_trainings).
 * Courses tagged as compliance via category='compliance' or linked to compliance skills.
 *
 * GET /api/outbound/get-compliance-training-log.php
 * Header: X-API-Key: <key>
 * Params: employee_id (optional), status (optional)
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'learning-development');

    $where = "WHERE (c.category = 'Compliance' OR c.category = 'compliance' OR c.category = 'Safety')";
    $params = [];

    if (!empty($_GET['employee_id'])) {
        $where .= " AND e.learner_id = :eid";
        $params[':eid'] = (int)$_GET['employee_id'];
    }
    if (!empty($_GET['status'])) {
        $where .= " AND e.status = :status";
        $params[':status'] = $_GET['status'];
    }

    $stmt = $pdo->prepare("
        SELECT
            e.learner_id AS employee_id,
            c.title AS training_name,
            c.category AS training_type,
            e.completed_at AS date_completed,
            DATE_ADD(e.completed_at, INTERVAL 1 YEAR) AS expiry_date,
            CASE
                WHEN e.status = 'completed' THEN 'Completed'
                WHEN e.status = 'in_progress' THEN 'In Progress'
                WHEN e.enrollment_deadline < NOW() AND e.status != 'completed' THEN 'Expired'
                ELSE 'Pending'
            END AS status,
            e.enrolled_at AS created_at
        FROM ld_enrollment e
        JOIN ld_course c ON c.id = e.course_id
        $where
        ORDER BY e.completed_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format to match lc_trainings shape
    $formatted = array_map(function ($row) {
        return [
            'employee_id'   => (int)$row['employee_id'],
            'training_name' => $row['training_name'],
            'training_type' => $row['training_type'],
            'date_completed' => $row['date_completed'] ? date('Y-m-d', strtotime($row['date_completed'])) : null,
            'expiry_date'   => $row['expiry_date'] ? date('Y-m-d', strtotime($row['expiry_date'])) : null,
            'status'        => $row['status'],
        ];
    }, $rows);

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-compliance-training-log', 'success', [
        'count' => count($formatted),
        'filters' => $_GET,
    ]);

    echo json_encode(['success' => true, 'data' => $formatted, 'count' => count($formatted)]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-compliance-training-log', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
