<?php
/**
 * Holidays Management Page
 * Displays holiday calendar, upcoming holidays, and management options
 */

require_once __DIR__ . '/../app/core/Session.php';
Session::start();

require_once __DIR__ . '/../app/controllers/AuthController.php';

$current_page = 'holidays';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'EMPLOYEE';

// Set defaults - will load data async
$allHolidays = array();
$upcomingHolidays = array();
$nextHoliday = null;
$daysUntilNext = 'N/A';
$currentMonth = date('F Y');
?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/hr-template.css">
<link rel="stylesheet" href="assets/css/holidays.css">

    <div class="module-header">
        <h1>Holidays</h1>
    </div>

    <div class="module-content">
            <!-- Content will be loaded here -->
            <div id="holidayContent" class="glass-panel" style="padding: 30px; border-radius: 18px; text-align: center;">
                <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #005ba8; margin-bottom: 15px;"></i>
                <p style="color: #666; font-size: 14px;">Loading holiday data...</p>
            </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- FullCalendar Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="assets/js/holidays.js"></script>
