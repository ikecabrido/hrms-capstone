<?php
// 15. Beginning of shifts.php end-of-file checkpoint will be logged at the end of the file
/**
 * Shift Management Page
 * HR-only page for managing shifts and employee assignments
 */

// Start session and check authentication
// Ensure required app bootstrap is available so runtime variables used
// later (like $db, $shifts, $allAssignments) are defined.
// JS helpers and modal-prefill moved to the scripts section further down the file.
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../app/controllers/ShiftController.php';

?>
    <?php
// Get data based on action

// For edit action, get specific shift
$editShift = null;
if ($action === 'edit' && isset($_GET['shift_id'])) {
    $editShift = $shiftController->getShiftById($_GET['shift_id']);
}

?>
<?php
$current_page = 'shifts.php';
$current_role = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? 'time';
$page_title = 'Shift Management';
$page_subtitle = 'Shift management and assignments';
$page_head_extra = <<<HTML
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/hr-template.css">
<link rel="stylesheet" href="assets/css/shifts.css">
    <script src="assets/js/mobile-responsive.js" defer></script>
HTML;
?>
<?php
// 12. Before page_start.php
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 12 Before page_start.php');
require_once __DIR__ . '/../layout/page_start.php';
require_once __DIR__ . '/../layout/sidebar.php';
// 13. After page_start.php
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 13 After page_start.php');
// Before the main Shift Management HTML
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 14 Before main Shift Management HTML');
$page_title = 'Shift Management';
$page_subtitle = 'Create, edit, and assign employee shifts';
$page_icon = 'fa-clock';
require_once __DIR__ . '/../layout/content_header.php';
?>
    <div class="shift-container">
        <div class="container glass-panel">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <!-- Action Button for Assign Shift -->
        <div class="shift-action-buttons">
            <button class="btn btn-primary" onclick="openModal('generateFixedModal');">
                <i class="fas fa-calendar-plus"></i>
                Assign Shift
            </button>
                <button class="btn btn-primary" onclick="openCreateShiftModal();">
                    <i class="fas fa-plus"></i>
                    Create Shift
                </button>
                <button class="btn btn-primary" onclick="openMultipleAssign()">
                    <i class="fas fa-users-cog"></i>
                    Multiple Assign
                </button>
        </div>

    <div id="overview" class="tab-content glass-panel" style="display: block;">
        <h2 style="margin-bottom: 30px; font-size: 24px; font-weight: 700; color: #2c3e50;">
            <i class="fas fa-chart-bar"></i>
            Shift Management Overview
        </h2>
            
            <div class="shift-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?php echo count($shifts); ?></div>
                    <div class="stat-label">Total Shifts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number">
                        <?php
                        $activeShiftCount = 0;
                        foreach ($shifts as $shift) {
                            if (!empty($shift['is_active'])) {
                                $activeShiftCount++;
                            }
                        }
                        echo $activeShiftCount;
                        ?>
                    </div>
                    <div class="stat-label">Active Shifts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?php echo count($allAssignments ?? []); ?></div>
                    <div class="stat-label">Total Assignments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number">
                        <?php 
                        try {
                            $flex_count = $db->query("SELECT COUNT(*) as count FROM ta_flexible_schedules")->fetch(PDO::FETCH_ASSOC);
                            echo $flex_count['count'] ?? 0;
                        } catch (Exception $e) {
                            echo 0;
                        }
                        ?>
                    </div>
                    <div class="stat-label">Flexible Schedules</div>
                </div>
            </div>
        </div>

        <!-- Shift Breakdown removed: condensed into Shift Overview -->

        <h3 style="margin-top: 50px; font-size: 22px; font-weight: 700; color: #2c3e50;">
            <i class="fas fa-user-check"></i> Shift Assignments
        </h3>
        
        <!-- Search and Controls -->
        <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="flex: 1; min-width: 200px; position: relative;">
                <input type="text" id="assignmentSearch" placeholder="Search by employee, department, or shift..." style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;" autocomplete="off">
                <div id="assignmentSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e0e0e0; border-top: none; border-radius: 0 0 6px 6px; max-height: 200px; overflow-y: auto; display: none; z-index: 1000; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                </div>
            </div>
            <select id="assignmentSortBy" style="padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; cursor: pointer;">
                <option value="employee">Sort by Employee</option>
                <option value="shift">Sort by Shifts</option>
                <option value="active">Sort by Active Count</option>
                <option value="status">Sort by Status</option>
            </select>
            <button type="button" onclick="resetAssignmentFilters()" style="padding: 10px 20px; background: #f0f0f0; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 500;">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
        
        <div class="table-container glass-panel">
            <table id="assignmentTable">
                <thead>
                    <tr>
                        <th style="cursor: pointer;" onclick="sortAssignments('employee')"><i class="fas fa-user"></i> Employee <i class="fas fa-sort"></i></th>
                        <th style="cursor: pointer;" onclick="sortAssignments('shift_count')"><i class="fas fa-briefcase"></i> Shifts <i class="fas fa-sort"></i></th>
                        <th style="cursor: pointer;" onclick="sortAssignments('active_count')"><i class="fas fa-check-circle"></i> Active <i class="fas fa-sort"></i></th>
                        <th style="cursor: pointer;" onclick="sortAssignments('status')"><i class="fas fa-info-circle"></i> Status <i class="fas fa-sort"></i></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="assignmentTableBody"></tbody>
            </table>
            
            <!-- Shift Templates Table -->
            <h3 style="margin-top: 24px; font-size: 18px; font-weight: 700;">Shift Templates</h3>
            <table id="templatesTable" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Time</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="templatesTableBody"></tbody>
            </table>
            <!-- Pagination for Assignments -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding: 15px; background: white; border-radius: 10px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span id="assignmentInfo" style="font-size: 14px; color: #666;">Showing 0 of 0 records</span>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" onclick="previousAssignmentPage()" id="prevAssignBtn" style="padding: 8px 15px; background: #f0f0f0; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <div id="assignmentPageNumbers" style="display: flex; gap: 5px;"></div>
                    <button type="button" onclick="nextAssignmentPage()" id="nextAssignBtn" style="padding: 8px 15px; background: #f0f0f0; border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="assignmentPerPage" style="font-size: 14px;">Records per page:</label>
                    <select id="assignmentPerPage" onchange="changeAssignmentPageSize()" style="padding: 6px 10px; border: 2px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="employeeShiftModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-user-clock"></i> Employee Shift Details</h2>
                    <button class="modal-close" onclick="closeModal('employeeShiftModal')">&times;</button>
                </div>
                <div class="modal-body" id="employeeShiftModalBody" style="padding-bottom: 0;"></div>
                <div class="modal-action-row" style="justify-content: flex-end; gap: 10px; padding: 20px 24px 24px;">
                    <button class="btn btn-secondary" onclick="closeModal('employeeShiftModal')">Close</button>
                    <button class="btn btn-primary" id="employeeShiftModalEditButton" type="button" onclick="openGenerateFixedModalForEmployee()" style="display: none;">
                        <i class="fas fa-edit"></i> Edit Schedule
                    </button>
                </div>
            </div>
        </div>

        <div id="scheduleDetailModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 520px;">
                <div class="modal-header">
                    <h2><i class="fas fa-calendar-check"></i> Shift Information</h2>
                    <button class="modal-close" onclick="closeModal('scheduleDetailModal')">&times;</button>
                </div>
                <div class="modal-body" id="scheduleDetailModalBody"></div>
                <div class="modal-action-row" style="justify-content: flex-end; padding: 20px 24px 24px;">
                    <button class="btn btn-secondary" type="button" onclick="closeModal('scheduleDetailModal')">Close</button>
                </div>
            </div>
        </div>

        <!-- viewFlexibleScheduleModal removed (flexible schedule UI deprecated) -->

<script>
    window.__TA_CONFIG = {
        assignments: <?php echo json_encode($allAssignments ?? [], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS); ?>,
        shifts: <?php echo json_encode($shifts ?? [], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS); ?>
    };
</script>
<script src="assets/js/shifts.js"></script>

    <!-- MODALS -->
    <!-- Create Shift Modal -->
    <div id="createShiftModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Shift</h2>
                <button class="modal-close" onclick="closeModal('createShiftModal')">&times;</button>
            </div>
            <form id="createShiftForm" method="POST" class="shift-form" style="padding: 0;">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="shift_name"><i class="fas fa-briefcase"></i> Shift Name *</label>
                        <input type="text" id="shift_name" name="shift_name" required placeholder="e.g., Morning Shift">
                    </div>
                    <div class="form-group">
                        <label for="create_start_date"><i class="fas fa-calendar-day"></i> Effective Start *</label>
                        <input type="date" id="create_start_date" name="create_start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="create_end_date"><i class="fas fa-calendar-day"></i> Effective End *</label>
                        <input type="date" id="create_end_date" name="create_end_date" required>
                    </div>

                    <div class="form-group weekday-template-section">
                        <label><i class="fas fa-calendar-week"></i> Weekday Template (exclude Sundays)</label>
                        <div class="weekday-template-grid">
                            <!-- Weekday rows 1..6 (Mon..Sat) -->
                            <?php for ($d = 1; $d <= 6; $d++): ?>
                            <div class="weekday-row">
                                <?php $__day_names = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; $__day_label = $__day_names[$d-1] ?? 'Day'; ?>
                                <label class="weekday-label"><input type="checkbox" id="create_day_<?php echo $d; ?>_enabled" class="create-day-enabled"> <?php echo $__day_label; ?></label>
                                <div id="create_day_<?php echo $d; ?>_controls" class="weekday-controls" style="display:none;">
                                    <div class="weekday-control-field">
                                        <label for="create_day_<?php echo $d; ?>_start">Start</label>
                                        <input type="time" id="create_day_<?php echo $d; ?>_start">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="create_day_<?php echo $d; ?>_end">End</label>
                                        <input type="time" id="create_day_<?php echo $d; ?>_end">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="create_day_<?php echo $d; ?>_break_start">Break Start</label>
                                        <input type="time" id="create_day_<?php echo $d; ?>_break_start">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="create_day_<?php echo $d; ?>_break_end">Break End</label>
                                        <input type="time" id="create_day_<?php echo $d; ?>_break_end">
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="modal-action-row">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('createShiftModal')">Cancel</button>
                        <button type="submit" form="createShiftForm" name="create_shift" class="btn btn-primary"><i class="fas fa-save"></i> Create Shift</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Shift Modal -->
    <div id="editShiftModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header edit-modal-header">
                <div>
                    <span class="edit-modal-eyebrow">SHIFT MANAGEMENT</span>
                    <h2><i class="fas fa-edit"></i> Edit Shift</h2>
                    <p>Update the shift details and availability.</p>
                </div>
                <button class="modal-close" onclick="closeModal('editShiftModal')">&times;</button>
            </div>
            <form id="editShiftForm" method="POST" class="shift-form" style="padding: 0;">
                <div class="modal-body">
                    <input type="hidden" id="edit_shift_id" name="shift_id">
                    <div class="edit-form-section">
                        <div class="edit-section-title"><i class="fas fa-sliders-h"></i><span>Shift details</span></div>
                    <div class="edit-form-grid">
                    <div class="form-group edit-form-full">
                        <label for="edit_shift_name"><i class="fas fa-briefcase"></i> Shift Name *</label>
                        <input type="text" id="edit_shift_name" name="shift_name" required placeholder="e.g., Morning Shift">
                    </div>
                    <div class="form-group">
                        <label for="edit_start_time"><i class="fas fa-sign-in-alt"></i> Start Time *</label>
                        <input type="time" id="edit_start_time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_end_time"><i class="fas fa-sign-out-alt"></i> End Time *</label>
                        <input type="time" id="edit_end_time" name="end_time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_break_duration"><i class="fas fa-hourglass-half"></i> Break Duration (minutes)</label>
                        <input type="number" id="edit_break_duration" name="break_duration" min="0" max="480">
                    </div>
                    <div class="form-group edit-form-full">
                        <label for="edit_description"><i class="fas fa-file-alt"></i> Description</label>
                        <textarea id="edit_description" name="description" placeholder="Enter shift description (optional)"></textarea>
                    </div>
                    </div>
                    </div>
                    <div class="edit-form-section">
                        <div class="edit-section-title"><i class="fas fa-calendar-week"></i><span>Weekday Template</span></div>
                        <div class="weekday-template-grid">
                            <?php for ($d = 1; $d <= 6; $d++): ?>
                            <div class="weekday-row">
                                <?php $__day_names = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; $__day_label = $__day_names[$d-1] ?? 'Day'; ?>
                                <label class="weekday-label"><input type="checkbox" id="edit_day_<?php echo $d; ?>_enabled"> <?php echo $__day_label; ?></label>
                                <div id="edit_day_<?php echo $d; ?>_controls" class="weekday-controls" style="display:none;">
                                    <div class="weekday-control-field">
                                        <label for="edit_day_<?php echo $d; ?>_start">Start</label>
                                        <input type="time" id="edit_day_<?php echo $d; ?>_start">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="edit_day_<?php echo $d; ?>_end">End</label>
                                        <input type="time" id="edit_day_<?php echo $d; ?>_end">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="edit_day_<?php echo $d; ?>_break_start">Break Start</label>
                                        <input type="time" id="edit_day_<?php echo $d; ?>_break_start">
                                    </div>
                                    <div class="weekday-control-field">
                                        <label for="edit_day_<?php echo $d; ?>_break_end">Break End</label>
                                        <input type="time" id="edit_day_<?php echo $d; ?>_break_end">
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="edit-form-section edit-options-section">
                        <div class="edit-section-title"><i class="fas fa-toggle-on"></i><span>Availability</span></div>
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" id="edit_is_active" name="is_active">
                            <span><i class="fas fa-check"></i> Active</span>
                        </label>
                    </div>
                    <div class="form-group" style="display: flex; align-items: center; gap: 12px; background: #f0f8ff; padding: 15px; border-radius: 6px; border-left: 4px solid #2196F3;">
                        <input type="checkbox" id="edit_exclude_saturday" name="exclude_saturday" style="width: 20px; height: 20px; cursor: pointer;">
                        <label for="edit_exclude_saturday" style="margin: 0; cursor: pointer; flex: 1;">
                            <strong style="color: #1565c0;">Exclude Saturdays?</strong>
                            <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">Check this if the shift does not operate on Saturdays</p>
                        </label>
                    </div>
                    </div>

                    <div class="modal-action-row">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editShiftModal')">Cancel</button>
                        <button type="submit" form="editShiftForm" name="update_shift" class="btn btn-primary"><i class="fas fa-save"></i> Update Shift</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-check"></i> Assign Shift to Employees</h2>
                <button class="modal-close" onclick="closeModal('assignmentModal')">&times;</button>
            </div>
            <form method="POST" class="shift-form" style="padding: 0;">
                <div class="modal-body">
                    <!-- Search and Filter Section -->
                    <div id="employeeSearchFilterContainer" style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px; position: relative;">
                            <input 
                                type="text" 
                                id="employeeSearchInput" 
                                placeholder="Search employees..." 
                                style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;"
                                oninput="filterEmployeeList(this.value)"
                            >
                            <div id="selectedEmployeeDisplay" style="display:none; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius:6px; background:#fff; font-weight:600;">
                                <!-- Filled dynamically in edit mode -->
                            </div>
                        </div>
                        <button id="employeeSearchButton" type="button" class="btn btn-info" onclick="searchEmployees()" title="Search" style="padding: 10px 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-search"></i>
                        </button>
                        <select id="employeeFilterStatus" style="padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; cursor: pointer;" onchange="filterEmployeeByStatus(this.value)">
                            <option value="">All Employees</option>
                            <option value="assigned">Assigned</option>
                            <option value="unassigned">Unassigned</option>
                        </select>
                    </div>

                    <!-- Multi-Select Employee List -->
                    <div class="form-group">
                        <label id="employeeMultiLabel"><i class="fas fa-users"></i> Select Employees (Multi-select) *</label>
                        <div id="employeeListContainer" style="border: 2px solid #e0e0e0; border-radius: 6px; max-height: 300px; overflow-y: auto; background: #f9f9f9; display: none;">
                            <div id="employeeCheckboxList" style="padding: 10px;">
                                <!-- Checkboxes will be populated by JavaScript -->
                            </div>
                        </div>
                        <div id="selectedSummary" style="color: #666; margin-top: 8px; display: block;">
                            <strong>Selected:</strong> <span id="selectedCount">0</span> employee(s)
                        </div>
                    </div>

                    <!-- Shift Selection -->
                    <div class="form-group">
                        <label for="shift_id"><i class="fas fa-briefcase"></i> Shift *</label>
                        <select id="shift_id" name="shift_id" required>
                            <option value="">Select a shift...</option>
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?php echo $shift['shift_id']; ?>">
                                    <?php echo htmlspecialchars($shift['shift_name']); ?> 
                                    (<?php echo date('g:i A', strtotime($shift['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($shift['end_time'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Selection -->
                    <div class="form-group">
                        <label for="effective_from"><i class="fas fa-calendar-check"></i> Effective From *</label>
                        <input type="date" id="effective_from" name="effective_from" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="effective_to"><i class="fas fa-calendar-times"></i> Effective To (Optional)</label>
                        <input type="date" id="effective_to" name="effective_to">
                    </div>

                    <!-- No Saturday Checkbox -->
                    <div class="form-group" style="display: flex; align-items: center; gap: 12px; background: #f0f8ff; padding: 15px; border-radius: 6px; border-left: 4px solid #2196F3;">
                        <input 
                            type="checkbox" 
                            id="exclude_saturday" 
                            name="exclude_saturday"
                            style="width: 20px; height: 20px; cursor: pointer;"
                        >
                        <label for="exclude_saturday" style="margin: 0; cursor: pointer; flex: 1;">
                            <strong style="color: #1565c0;">No Saturday?</strong>
                            <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">
                                Check this to exclude Saturdays from the shift assignment
                            </p>
                        </label>
                    </div>

                    <!-- Hidden field to store selected employees -->
                    <input type="hidden" id="selected_employees" name="selected_employees" value="">

                    <div class="modal-action-row">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('assignmentModal')">Cancel</button>
                        <button type="button" id="assignmentModalActionButton" class="btn btn-primary" onclick="handleAssignmentModalAction()" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check"></i> Assign to Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Employee Shift Modal -->
    <div id="generateFixedModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-clock"></i> Edit Employee Shift</h2>
                <button class="modal-close" onclick="closeModal('generateFixedModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="gf_employee_search"><i class="fas fa-user"></i> Employee</label>
                    <input type="text" id="gf_employee_search" autocomplete="off" readonly style="width: 100%; background:#f5f5f5; cursor:not-allowed;">
                    <div id="gf_selected_employee_display" style="margin-top: 10px; color: #444; font-weight: 600;">No employee selected</div>
                    <input type="hidden" id="gf_employee_id" name="gf_employee_id">
                </div>

                <div class="form-group">
                    <label for="gf_start_date"><i class="fas fa-calendar-day"></i> Schedule Start</label>
                    <input type="date" id="gf_start_date" name="gf_start_date">
                </div>

                <div class="form-group">
                    <label for="gf_end_date"><i class="fas fa-calendar-day"></i> Schedule End</label>
                    <input type="date" id="gf_end_date" name="gf_end_date">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-calendar-week"></i> Days of Week</label>
                    <div style="display: grid; gap: 10px;">
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_1_enabled" onchange="toggleGfDayRow(1)"> Monday</label>
                            <div id="gf_day_1_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_1_start">Start</label>
                                    <input type="time" id="gf_day_1_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_1_end">End</label>
                                    <input type="time" id="gf_day_1_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_1_break_start">Break Start</label>
                                    <input type="time" id="gf_day_1_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_1_break_end">Break End</label>
                                    <input type="time" id="gf_day_1_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_2_enabled" onchange="toggleGfDayRow(2)"> Tuesday</label>
                            <div id="gf_day_2_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_2_start">Start</label>
                                    <input type="time" id="gf_day_2_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_2_end">End</label>
                                    <input type="time" id="gf_day_2_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_2_break_start">Break Start</label>
                                    <input type="time" id="gf_day_2_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_2_break_end">Break End</label>
                                    <input type="time" id="gf_day_2_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_3_enabled" onchange="toggleGfDayRow(3)"> Wednesday</label>
                            <div id="gf_day_3_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_3_start">Start</label>
                                    <input type="time" id="gf_day_3_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_3_end">End</label>
                                    <input type="time" id="gf_day_3_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_3_break_start">Break Start</label>
                                    <input type="time" id="gf_day_3_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_3_break_end">Break End</label>
                                    <input type="time" id="gf_day_3_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_4_enabled" onchange="toggleGfDayRow(4)"> Thursday</label>
                            <div id="gf_day_4_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_4_start">Start</label>
                                    <input type="time" id="gf_day_4_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_4_end">End</label>
                                    <input type="time" id="gf_day_4_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_4_break_start">Break Start</label>
                                    <input type="time" id="gf_day_4_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_4_break_end">Break End</label>
                                    <input type="time" id="gf_day_4_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_5_enabled" onchange="toggleGfDayRow(5)"> Friday</label>
                            <div id="gf_day_5_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_5_start">Start</label>
                                    <input type="time" id="gf_day_5_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_5_end">End</label>
                                    <input type="time" id="gf_day_5_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_5_break_start">Break Start</label>
                                    <input type="time" id="gf_day_5_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_5_break_end">Break End</label>
                                    <input type="time" id="gf_day_5_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                        <div class="gf-day-row">
                            <label><input type="checkbox" id="gf_day_6_enabled" onchange="toggleGfDayRow(6)"> Saturday</label>
                            <div id="gf_day_6_controls" class="gf-day-controls">
                                <div class="gf-time-field">
                                    <label for="gf_day_6_start">Start</label>
                                    <input type="time" id="gf_day_6_start" placeholder="Start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_6_end">End</label>
                                    <input type="time" id="gf_day_6_end" placeholder="End">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_6_break_start">Break Start</label>
                                    <input type="time" id="gf_day_6_break_start" placeholder="Break start">
                                </div>
                                <div class="gf-time-field">
                                    <label for="gf_day_6_break_end">Break End</label>
                                    <input type="time" id="gf_day_6_break_end" placeholder="Break end">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="modal-action-row">
                <button type="button" class="btn btn-secondary" onclick="closeModal('generateFixedModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitGenerateFixed()"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Template View Modal -->
    <div id="templateViewModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> View Shift Template</h2>
                <button class="modal-close" onclick="closeModal('templateViewModal')">&times;</button>
            </div>
            <div class="modal-body" id="templateViewBody"></div>
            <div class="modal-action-row">
                <button class="btn btn-secondary" onclick="closeModal('templateViewModal')">Close</button>
                <button class="btn btn-primary" onclick="openEditTemplateModal()">Edit Template</button>
            </div>
        </div>
    </div>

    <!-- Edit Template Modal uses existing editShiftModal to allow changing details -->
    <!-- Flexible schedule UI deprecated in this view. -->
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="assets/css/shifts.css">

<?php
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 15 At end of page');
require_once __DIR__ . '/../layout/content_footer.php';
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 15a After content_footer.php');
require_once __DIR__ . '/../layout/page_end.php';
if (function_exists('shiftDebug')) shiftDebug('[DIAG] 15b After page_end.php');
?>
