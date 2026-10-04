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

    <div id="holidayModal" role="dialog" aria-modal="true" aria-labelledby="holidayModalTitle" hidden style="position:fixed; inset:0; z-index:1050; background:rgba(0,0,0,.55); padding:20px; overflow:auto;">
        <div style="background:#fff; max-width:720px; margin:30px auto; border-radius:12px; box-shadow:0 10px 35px rgba(0,0,0,.25);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:18px 22px; border-bottom:1px solid #e9ecef;">
                <h2 id="holidayModalTitle" style="margin:0; font-size:20px; color:#183b56;">Add Holiday</h2>
                <button type="button" id="closeHolidayModal" aria-label="Close" style="border:0; background:transparent; font-size:24px; color:#6c757d; cursor:pointer;">&times;</button>
            </div>
            <form id="holidayForm" novalidate style="padding:22px;">
                <input type="hidden" id="holidayId" name="id">
                <div id="holidayFormMessage" role="alert" style="display:none; margin-bottom:14px; padding:10px 12px; border-radius:6px;"></div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:16px;">
                    <label>Holiday Name <span style="color:#c0392b;">*</span>
                        <input type="text" id="holidayName" name="name" required maxlength="255" style="width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px;">
                    </label>
                    <label>Holiday Date <span style="color:#c0392b;">*</span>
                        <input type="date" id="holidayDate" name="holiday_date" required style="width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px;">
                    </label>
                    <label>Holiday Scope <span style="color:#c0392b;">*</span>
                        <select id="holidayScope" name="holiday_scope" required style="width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px;">
                            <option value="national">National</option>
                            <option value="provincial">Provincial</option>
                            <option value="company">Company</option>
                        </select>
                    </label>
                    <label id="provinceField" hidden>Province <span style="color:#c0392b;">*</span>
                        <input type="text" id="provinceName" name="province_name" maxlength="100" placeholder="e.g. Pangasinan" style="width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px;">
                    </label>
                    <label>Work Status <span style="color:#c0392b;">*</span>
                        <select id="holidayWorkingDay" name="is_working_day" required style="width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px;">
                            <option value="0">Non-Working Holiday</option>
                            <option value="1">Working Holiday</option>
                        </select>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; padding-top:26px;">
                        <input type="checkbox" id="holidayRecurring" name="is_recurring" value="1"> Recurring holiday
                    </label>
                </div>
                <div style="margin-top:16px;">
                    <label>Description
                        <textarea id="holidayDescription" name="description" rows="3" maxlength="1000" style="display:block; width:100%; margin-top:6px; padding:9px; border:1px solid #ced4da; border-radius:5px; resize:vertical;"></textarea>
                    </label>
                </div>
                <p style="margin:14px 0 0; color:#6c757d; font-size:12px;">Working holidays require employees to follow their assigned schedule. Non-working holidays do not require attendance and should not be marked absent.</p>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" id="cancelHolidayModal" class="btn btn-light" style="padding:9px 16px; border:1px solid #ced4da; border-radius:5px; background:#fff; cursor:pointer;">Cancel</button>
                    <button type="submit" id="saveHolidayButton" class="btn btn-primary" style="padding:9px 16px; border:0; border-radius:5px; background:#005ba8; color:#fff; cursor:pointer;">Save Holiday</button>
                </div>
            </form>
        </div>
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
