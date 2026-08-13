<?php
/**
 * Holidays Management Page
 * Displays holiday calendar, upcoming holidays, and management options
 */

// Start session and auth first
require_once __DIR__ . '/../app/core/Session.php';
Session::start();

// Check if user is authenticated
require_once __DIR__ . '/../app/controllers/AuthController.php';
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$current_page = 'holidays.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'EMPLOYEE';

// Set defaults - will load data async
$allHolidays = array();
$upcomingHolidays = array();
$nextHoliday = null;
$daysUntilNext = 'N/A';
$currentMonth = date('F Y');
?>
<?php
$current_page = 'holidays.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'EMPLOYEE';
$page_title = 'Holidays';
$page_subtitle = 'Holiday calendar and management';
$page_head_extra = <<<HTML
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/dist/css/adminlte.min.css">
<link rel="stylesheet" href="../../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
<link rel="stylesheet" href="../../assets/plugins/toastr/toastr.min.css">
<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/adminlte-overrides.css">
<link rel="stylesheet" href="../assets/css/hr-template.css">
<link rel="stylesheet" href="../assets/css/holidays.css">
HTML;
?>
<?php
require_once __DIR__ . '/../layout/page_start.php';
require_once __DIR__ . '/../layout/sidebar.php';
$page_title = 'Holidays';
$page_subtitle = 'Holiday calendar and management';
$page_icon = 'fa-calendar-alt';
require_once __DIR__ . '/../layout/content_header.php';
?>

            <!-- Content will be loaded here -->
            <div id="holidayContent" class="glass-panel" style="padding: 30px; border-radius: 18px; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #005ba8; margin-bottom: 15px;"></i>
                <p style="color: #666; font-size: 14px;">Loading holiday data...</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/plugins/jquery/jquery.min.js"></script>
    <script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/plugins/toastr/toastr.min.js"></script>
    <script src="../../assets/dist/js/adminlte.js"></script>
    <!-- FullCalendar Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <script src="../assets/js/holidays.js"></script>

<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>
