<?php
/**
 * API: Trigger Absence & Late Detection (Manual)
 * Allows HR to manually trigger detection for testing or immediate updates
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../helpers/EnhancedAbsenceDetector.php';

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check HR permission
if (!AuthController::hasRole('time') && !AuthController::hasRole('hr')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';
    $detector = new \App\Helpers\EnhancedAbsenceDetector();
    $response = [];

    // Support optional date range parameters for replaying detection
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    switch ($action) {
        case 'detect_late':
            $result = $detector->detectAndMarkLateToday();
            
            $response = [
                'success' => true,
                'message' => 'Late arrival detection completed',
                'status' => $result['status'],
                'date' => $result['date'] ?? date('Y-m-d'),
                'marked_late' => $result['late_count'] ?? 0,
                'details' => $result['results'] ?? []
            ];
            break;

        case 'detect_absence':
            if ($start_date && $end_date) {
                $result = $detector->detectAndMarkAbsencesRange($start_date, $end_date);
            } else {
                $result = $detector->detectAndMarkAbsenceToday();
            }
            
            $response = [
                'success' => true,
                'message' => 'Absence detection completed',
                'status' => $result['status'],
                'date' => $result['date'] ?? date('Y-m-d'),
                'marked_absent' => $result['absence_count'] ?? 0,
                'details' => $result['results'] ?? []
            ];
            break;

        case 'detect_all':
            $lateResult = $detector->detectAndMarkLateToday();
            if ($start_date && $end_date) {
                $absenceResult = $detector->detectAndMarkAbsencesRange($start_date, $end_date);
            } else {
                $absenceResult = $detector->detectAndMarkAbsenceToday();
            }
            
            $response = [
                'success' => true,
                'message' => 'Complete detection cycle finished',
                'date' => date('Y-m-d'),
                'late_detection' => [
                    'status' => $lateResult['status'] ?? 'skipped',
                    'marked' => $lateResult['late_count'] ?? 0,
                    'details' => $lateResult['results'] ?? []
                ],
                'absence_detection' => [
                    'status' => $absenceResult['status'] ?? 'skipped',
                    'marked' => $absenceResult['absence_count'] ?? 0,
                    'details' => $absenceResult['results'] ?? []
                ]
            ];
            break;

        case 'get_today_summary':
            $summary = $detector->getTodayAttendanceSummary();
            $employees = $detector->getTodayAllEmployeesWithStatus(100, 0);
            
            $response = [
                'success' => true,
                'message' => "Today's attendance summary",
                'date' => date('Y-m-d'),
                'summary' => $summary,
                'employees' => $employees
            ];
            break;

        default:
            http_response_code(400);
            $response = [
                'success' => false,
                'message' => 'Invalid action. Use: detect_late, detect_absence, detect_all, or get_today_summary'
            ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
