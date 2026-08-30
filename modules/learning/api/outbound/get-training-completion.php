<?php
/**
 * Outbound: get-training-completion.php
 * Returns Learning & Development training completion data for Performance Management.
 * Destination table: pm_employee_training (completion_status, final_score, certificate_status)
 *
 * GET /api/outbound/get-training-completion.php
 * Header: X-API-Key: <key>  |  Authorization: Bearer <key>
 * Params: learner_id (optional), course_id (optional), status (optional)
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Authenticate
    ApiAuth::requireAuth($pdo, 'learning-development');

    // 2. Query enrollment + completion data
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($_GET['learner_id'])) {
        $where .= " AND e.learner_id = :lid";
        $params[':lid'] = (int)$_GET['learner_id'];
    }
    if (!empty($_GET['course_id'])) {
        $where .= " AND e.course_id = :cid";
        $params[':cid'] = (int)$_GET['course_id'];
    }
    if (!empty($_GET['status'])) {
        $where .= " AND e.status = :status";
        $params[':status'] = $_GET['status'];
    }

    $stmt = $pdo->prepare("
        SELECT
            e.learner_id AS employee_id,
            e.course_id AS training_id,
            e.status AS ld_status,
            CASE
                WHEN e.status = 'completed' THEN 'Completed'
                WHEN e.status = 'in_progress' THEN 'In Progress'
                ELSE 'Pending'
            END AS completion_status,
            e.enrolled_at AS assigned_date,
            e.completed_at AS end_date,
            COALESCE(cert.verification_code, NULL) AS certificate_code,
            CASE WHEN cert.id IS NOT NULL THEN 'Issued' ELSE 'Not Issued' END AS certificate_status,
            COALESCE(avg_score.avg_score, 0) AS final_score
        FROM ld_enrollment e
        LEFT JOIN ld_certificate cert ON cert.learner_id = e.learner_id AND cert.course_id = e.course_id
        LEFT JOIN (
            SELECT qa.learner_id, q.module_id, ROUND(AVG(qa.score), 2) AS avg_score
            FROM ld_quiz_attempt qa
            JOIN ld_quiz q ON q.id = qa.quiz_id
            GROUP BY qa.learner_id, q.module_id
        ) avg_score ON avg_score.learner_id = e.learner_id
        $where
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // 3. Format to match pm_employee_training shape
    $formatted = array_map(function ($row) {
        return [
            'employee_id'       => (int)$row['employee_id'],
            'training_id'       => (int)$row['training_id'],
            'completion_status' => $row['completion_status'],
            'completion_percentage' => $row['ld_status'] === 'completed' ? 100.00 : ($row['ld_status'] === 'in_progress' ? 50.00 : 0.00),
            'final_score'       => (float)$row['final_score'],
            'certificate_status' => $row['certificate_status'],
            'assigned_date'     => $row['assigned_date'],
            'end_date'          => $row['end_date'],
        ];
    }, $rows);

    // 4. Log
    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-training-completion', 'success', [
        'count' => count($formatted),
        'filters' => $_GET,
    ]);

    echo json_encode(['success' => true, 'data' => $formatted, 'count' => count($formatted)]);
} catch (Exception $e) {
    http_response_code(500);
    $payload = ['success' => false, 'error' => $e->getMessage()];
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-training-completion', 'failed', $payload, $e->getMessage());
    }
    echo json_encode($payload);
}
