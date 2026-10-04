<?php
/**
 * Cron Job: Detect Absence & Late Arrivals
 * 
 * Run this script daily using a cron job:
 * - Detect Absences: 0 17 * * * /usr/bin/php /path/to/scripts/detect_absences.php
 * - Detect Late Arrivals: 0 18 * * * /usr/bin/php /path/to/scripts/detect_late_arrivals.php
 * 
 * Or run both:
 * - 0 17 * * * /usr/bin/php /path/to/scripts/run_absence_late_detection.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Require files
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../app/helpers/AbsenceAndLateDetector.php';

try {
    $detector = new \App\Helpers\AbsenceAndLateDetector();
    
    // Detect absences for today
    $absenceResults = $detector->detectTodayAbsences();
    
    // Create announcements for detected absences
    if (!empty($absenceResults) && isset($absenceResults[0]['employee_id'])) {
        foreach ($absenceResults as $result) {
            if ($result['status'] === 'Absence record created') {
                $detector->announceAbsence(
                    $result['employee_id'],
                    date('Y-m-d')
                );
            }
        }
    }
    
    // Detect late arrivals for today
    $lateResults = $detector->detectLateArrivals(date('Y-m-d'));
    
    // Create announcements for detected late arrivals
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
    
    // Log success
    $logMessage = date('Y-m-d H:i:s') . " | Absence & Late Detection Completed\n";
    $logMessage .= "Absences detected: " . (isset($absenceResults[0]) ? count($absenceResults) : 0) . "\n";
    $logMessage .= "Late arrivals detected: " . (isset($lateResults[0]) ? count($lateResults) : 0) . "\n";
    $logMessage .= "---\n";
    
    file_put_contents(__DIR__ . '/../logs/absence_late_detection.log', $logMessage, FILE_APPEND);
    
} catch (Exception $e) {
    // Log error
    $errorMessage = date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../logs/absence_late_detection_error.log', $errorMessage, FILE_APPEND);
    exit(1);
}

exit(0);
?>
