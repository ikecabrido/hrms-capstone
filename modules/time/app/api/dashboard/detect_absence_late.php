<?php
/**
 * API: Detect and Announce Absences & Late Arrivals
 * Allows HR to manually trigger absence/late detection
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../helpers/AbsenceAndLateDetector.php';

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
    $detector = new \App\Helpers\AbsenceAndLateDetector();
    $response = [];

    switch ($action) {
        case 'detect_absences':
            $date = $_GET['date'] ?? date('Y-m-d');
            $results = $detector->detectTodayAbsences();
            
            // Create announcements
            if (!empty($results) && isset($results[0]['employee_id'])) {
                foreach ($results as $result) {
                    if ($result['status'] === 'Absence record created') {
                        $detector->announceAbsence($result['employee_id'], date('Y-m-d'));
                    }
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Absence detection completed',
                'detected' => count($results),
                'results' => $results
            ];
            break;

        case 'detect_late':
            $date = $_GET['date'] ?? date('Y-m-d');
            $results = $detector->detectLateArrivals($date);
            
            // Create announcements
            if (!empty($results) && isset($results[0]['employee_id'])) {
                foreach ($results as $result) {
                    if ($result['status'] === 'Late record created') {
                        $detector->announceLate(
                            $result['employee_id'],
                            $date,
                            $result['minutes_late']
                        );
                    }
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Late arrival detection completed',
                'detected' => count($results),
                'results' => $results
            ];
            break;

        case 'detect_all':
            // Detect both absences and late arrivals
            $absenceResults = $detector->detectTodayAbsences();
            $lateResults = $detector->detectLateArrivals(date('Y-m-d'));
            
            // Create announcements for absences
            if (!empty($absenceResults) && isset($absenceResults[0]['employee_id'])) {
                foreach ($absenceResults as $result) {
                    if ($result['status'] === 'Absence record created') {
                        $detector->announceAbsence($result['employee_id'], date('Y-m-d'));
                    }
                }
            }
            
            // Create announcements for late arrivals
            if (!empty($lateResults) && isset($lateResults[0]['employee_id'])) {
                foreach ($lateResults as $result) {
                    if ($result['status'] === 'Late record created') {
                        $detector->announceLate(
                            $result['employee_id'],
                            date('Y-m-d'),
                            $result['minutes_late']
                        );
                    }
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Absence and late arrival detection completed',
                'absences_detected' => count($absenceResults),
                'late_detected' => count($lateResults),
                'absences' => $absenceResults,
                'late_arrivals' => $lateResults
            ];
            break;

        default:
            http_response_code(400);
            $response = [
                'success' => false,
                'message' => 'Invalid action. Use: detect_absences, detect_late, or detect_all'
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
