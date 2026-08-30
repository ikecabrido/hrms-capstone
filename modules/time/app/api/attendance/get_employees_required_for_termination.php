<?php
/**
 * API: Get employees currently meeting the existing near-termination attendance rule
 * Read-only endpoint. Uses AttendanceTerminationService as single source of truth.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../services/AttendanceTerminationService.php';
require_once __DIR__ . '/../../core/TimeDatabase.php';

use App\Services\AttendanceTerminationService;

Session::start();

// Authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Authorization: allow time, hr, and exit-process roles that review termination eligibility.
if (!AuthController::hasRole('time') && !AuthController::hasRole('hr') && !AuthController::hasRole('exit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Accept GET or POST parameters
$daysBack = (int)($_REQUEST['days_back'] ?? 7);
$limit = (int)($_REQUEST['limit'] ?? 100);

// Basic bounds
if ($daysBack < 1) $daysBack = 7;
if ($limit < 1) $limit = 100;
if ($limit > 1000) $limit = 1000;

try {
    $svc = new AttendanceTerminationService();
    $threshold = (int)$svc->getAbsenceTerminationThreshold();
    $rows = $svc->getEmployeesRequiringTerminationAction($daysBack, $limit);

    // Fetch employee_code for returned employees in one query
    $ids = array_map(function($r){ return (int)$r['employee_id']; }, $rows);
    $codes = [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db = \TimeDatabase::getInstance()->getConnection();
        $q = "SELECT employee_id, employee_code FROM em_employees WHERE employee_id IN ({$placeholders})";
        $st = $db->prepare($q);
        foreach ($ids as $i => $val) $st->bindValue($i+1, $val, PDO::PARAM_INT);
        $st->execute();
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $codes[(int)$r['employee_id']] = $r['employee_code'] ?? null;
        }
    }

    $data = [];
    foreach ($rows as $r) {
        $eid = (int)$r['employee_id'];
        $data[] = [
            'employee_id' => $eid,
            'employee_name' => $r['full_name'] ?? ($r['employee_name'] ?? ''),
            'employee_code' => $codes[$eid] ?? null,
            'department' => $r['department'] ?? '',
            'position' => $r['position'] ?? '',
            'absence_count' => isset($r['missed_shift_days']) ? (int)$r['missed_shift_days'] : (isset($r['missed_days']) ? (int)$r['missed_days'] : 0),
            'days_back' => $daysBack,
            'threshold' => $threshold,
            'termination_action_required' => ((int)($r['missed_shift_days'] ?? 0) >= $threshold),
            'last_missed_shift' => $r['last_missed_shift'] ?? null,
            'reason' => $r['reason'] ?? 'termination_threshold_reached'
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
