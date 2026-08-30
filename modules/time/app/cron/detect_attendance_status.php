<?php
/**
 * Canonical Attendance Status Detection Cron Script
 *
 * Thin CLI wrapper around AttendanceDetectionRunner::run(), which is the actual
 * shared detection logic (also invoked automatically from
 * app/api/attendance/absence_late_management.php so detection doesn't depend on
 * an external OS scheduler being configured).
 *
 * Usage:
 *   php detect_attendance_status.php
 *   php detect_attendance_status.php 2026-08-03
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../helpers/AttendanceDetectionRunner.php';

$scriptDate = null;
if (isset($argv[1]) && trim($argv[1]) !== '') {
    $candidate = DateTime::createFromFormat('Y-m-d', trim($argv[1]));
    if ($candidate && $candidate->format('Y-m-d') === trim($argv[1])) {
        $scriptDate = $candidate->format('Y-m-d');
    }
}

$results = AttendanceDetectionRunner::run($scriptDate);

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit($results['success'] ? 0 : 1);
