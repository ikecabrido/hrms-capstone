<?php
/**
 * Schedule Calendar - Employee Schedule Management
 * Displays employee schedules in calendar format with timeline editor
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/models/Employee.php';
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

if (!$authenticated) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

// Only 'time' role can access this page
if ($role !== 'time') {
    header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    exit;
}

$current_page = 'schedule_calendar.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
?>
<?php
$current_page = 'schedule_calendar.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'time';
$page_title = 'Schedule Calendar';
$page_subtitle = 'Employee schedule calendar and timeline';
$page_head_extra = <<<HTML
<link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="../../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/adminlte-overrides.css">
<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Calendar Schedule CSS -->
<link rel="stylesheet" href="../assets/css/calendar_schedule.css">
<link rel="stylesheet" href="../assets/css/hr-template.css">

HTML;
?>
<?php
require_once __DIR__ . '/../layout/page_start.php';
require_once __DIR__ . '/../layout/sidebar.php';
$page_title = 'Schedule Calendar';
$page_subtitle = 'View and manage employee schedules';
$page_icon = 'fa-calendar';
require_once __DIR__ . '/../layout/content_header.php';
?>


    <div class="main-content">
            <div class="calendar-container glass-panel">
                <div class="calendar-body">
                    <!-- Calendar Component -->
                    <?php include '../app/components/calendar_schedule.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="../assets/js/schedule-calendar-init.js"></script>
    
    <!-- Calendar Schedule JS -->
    <script src="../assets/js/calendar_schedule.js"></script>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>
