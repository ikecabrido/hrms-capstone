<?php
/**
 * Schedule Calendar - Employee Schedule Management
 * Displays employee schedules in calendar format with timeline editor
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/helpers/AuditLog.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check if user is authenticated - try global session first, then time_attendance session
$authenticated = false;
$role = null;

// Check global login session
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    $authenticated = true;
    $role = $_SESSION['user']['role'];
} else if (AuthController::isAuthenticated()) {
    // Fallback to time_attendance auth check
    $authenticated = true;
    $role = AuthController::getCurrentRole();
}

$current_page = 'schedule_calendar';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/calendar_schedule.css">
<link rel="stylesheet" href="assets/css/hr-template.css">

    <div class="module-header">
        <h1>Schedule Calendar</h1>
    </div>

    <div class="module-content">
            <div class="calendar-container glass-panel">
                <div class="calendar-body">
                    <!-- Calendar Component -->
                    <?php include __DIR__ . '../../app/components/calendar_schedule.php'; ?>
                </div>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="assets/js/schedule-calendar-init.js"></script>

    <!-- Calendar Schedule JS -->
    <script src="assets/js/calendar_schedule.js"></script>
