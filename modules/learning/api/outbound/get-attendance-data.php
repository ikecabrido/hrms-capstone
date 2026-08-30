<?php
/**
 * Outbound: get-attendance-data.php
 * Returns video conference attendance records for Time & Attendance (ta_attendance).
 * NOTE: ld_conference_attendance currently has no write-side code, so data may be empty
 * until conference attendance tracking is implemented.
 *
 * GET /api/outbound/get-attendance-data.php
 * Header: X-API-Key: <key>
 * Params: employee_id (optional), conference_id (optional), date_from, date_to
 */
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 3) . '/classes/apiauth.php';
require_once dirname(__FILE__, 3) . '/classes/integrationlog.php';
require_once dirname(__FILE__, 5) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    ApiAuth::requireAuth($pdo, 'learning-development');

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($_GET['employee_id'])) {
        $where .= " AND ca.learner_id = :eid";
        $params[':eid'] = (int)$_GET['employee_id'];
    }
    if (!empty($_GET['conference_id'])) {
        $where .= " AND ca.conference_id = :cid";
        $params[':cid'] = (int)$_GET['conference_id'];
    }
    if (!empty($_GET['date_from'])) {
        $where .= " AND vc.scheduled_at >= :df";
        $params[':df'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where .= " AND vc.scheduled_at <= :dt";
        $params[':dt'] = $_GET['date_to'] . " 23:59:59";
    }

    $stmt = $pdo->prepare("
        SELECT
            ca.learner_id AS employee_id,
            ca.conference_id,
            vc.title AS conference_title,
            vc.scheduled_at,
            vc.duration_minutes,
            ca.attended,
            ca.joined_at,
            ca.left_at,
            c.title AS course_title
        FROM ld_conference_attendance ca
        JOIN ld_video_conference vc ON vc.id = ca.conference_id
        JOIN ld_course c ON c.id = vc.course_id
        $where
        ORDER BY vc.scheduled_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format for ta_attendance shape
    $formatted = array_map(function ($row) {
        $duration = 0;
        if ($row['joined_at'] && $row['left_at']) {
            $duration = round((strtotime($row['left_at']) - strtotime($row['joined_at'])) / 60);
        }

        return [
            'employee_id'      => (int)$row['employee_id'],
            'attendance_date'  => date('Y-m-d', strtotime($row['scheduled_at'])),
            'time_in'          => $row['joined_at'],
            'time_out'         => $row['left_at'],
            'status'           => $row['attended'] ? 'PRESENT' : 'ABSENT',
            'recorded_by'      => 'SYSTEM',
            'source'           => 'learning-development',
            'conference_title' => $row['conference_title'],
            'course_title'     => $row['course_title'],
            'duration_minutes' => $duration,
        ];
    }, $rows);

    $log = new IntegrationLog($pdo);
    $log->logCall('outbound', 'learning-development', 'get-attendance-data', 'success', [
        'count' => count($formatted),
        'filters' => $_GET,
    ]);

    echo json_encode([
        'success' => true,
        'data' => $formatted,
        'count' => count($formatted),
        'note' => count($formatted) === 0
            ? 'No attendance records found. Conference attendance tracking may not be active yet.'
            : null,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    if (isset($pdo) && isset($log)) {
        $log->logCall('outbound', 'learning-development', 'get-attendance-data', 'failed', null, $e->getMessage());
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
