<?php
/**
 * Calendar Schedule Component
 * Displays employee schedule in calendar format with daily timeline editor
 */

// Check if this is an AJAX request for employee search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'search_employees') {
        require_once __DIR__ . '/../core/TimeDatabase.php';
        
        $search = $_GET['q'] ?? '';
        
        $query = "SELECT employee_id, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name
                  FROM em_employees 
                  WHERE employment_status = 'Active' 
                  AND CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?
                  ORDER BY first_name
                  LIMIT 20";
        
        $db = TimeDatabase::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bindParam(1, $search_param);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
        exit;
    }
}
?>

<div id="calendar-schedule-container">
    <!-- Controls Grid (hidden in Day View) -->
    <div id="controls-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 28px; margin-bottom: 35px;">
        <!-- Employee Search Card -->
        <div style="background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%); padding: 28px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0, 61, 130, 0.08); border: 2px solid rgba(0, 61, 130, 0.08);">
            <h6 style="font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #003d82;">
                <i class="fas fa-search" style="font-size: 18px;"></i>Search Employee
            </h6>
            <input 
                type="text" 
                id="employee-search" 
                class="form-control" 
                placeholder="Enter name or employee ID..."
                autocomplete="off"
                style="border-radius: 10px; border: 2px solid #e0e0e0; padding: 12px; font-weight: 500;">
            <div id="search-results" class="list-group mt-3" style="display:none; max-height: 300px; overflow-y: auto; border-radius: 10px;"></div>
            <div id="selected-employee" style="display:none; margin-top: 16px; padding: 12px; background: #e3f2fd; border-radius: 10px; border-left: 4px solid #003d82;">
                <p style="margin: 0; color: #003d82; font-weight: 600;">
                    <i class="fas fa-check-circle mr-2"></i>Selected: <strong id="employee-name"></strong>
                </p>
                <button class="btn btn-sm btn-outline-secondary mt-2" id="clear-employee" style="width: 100%;"><i class="fas fa-times mr-1"></i>Clear Selection</button>
            </div>
        </div>

        <!-- Legend Card -->
        <div style="background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%); padding: 28px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0, 61, 130, 0.08); border: 2px solid rgba(0, 61, 130, 0.08);">
            <h6 style="font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #003d82;">
                <i class="fas fa-key" style="font-size: 18px;"></i>Legend
            </h6>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #28a745; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    <span style="font-weight: 600; color: #333;">Shift Assigned</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #17a2b8; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    <span style="font-weight: 600; color: #333;">Present/Checked In</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #ffc107; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    <span style="font-weight: 600; color: #333;">Late</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #dc3545; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    <span style="font-weight: 600; color: #333;">Absent</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #9c27b0; border-radius: 3px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                    <span style="font-weight: 600; color: #333;">Flexible Schedule</span>
                </div>
            </div>
        </div>

        <!-- View Controls Card -->
        <div style="background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%); padding: 28px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0, 61, 130, 0.08); border: 2px solid rgba(0, 61, 130, 0.08);">
            <h6 style="font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #003d82;">
                <i class="fas fa-eye" style="font-size: 18px;"></i>View Options
            </h6>
            <div class="btn-group-vertical w-100" role="group" style="gap: 8px;">
                <button type="button" class="btn side-month-btn" style="background: linear-gradient(135deg, #003d82 0%, #005ba8 100%); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; transition: all 0.3s;">
                    <i class="fas fa-calendar mr-2"></i> Month View
                </button>
                <button type="button" class="btn side-week-btn" style="background: #f8f9fa; color: #003d82; border: 2px solid #e0e0e0; padding: 12px; border-radius: 10px; font-weight: 600; transition: all 0.3s;">
                    <i class="fas fa-calendar-alt mr-2"></i> Week View
                </button>
                <button type="button" class="btn side-day-btn" style="background: #f8f9fa; color: #003d82; border: 2px solid #e0e0e0; padding: 12px; border-radius: 10px; font-weight: 600; transition: all 0.3s;">
                    <i class="fas fa-clock mr-2"></i> Day View
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar Section - Full Width -->
    <div id="calendar-section" style="background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%); padding: 28px; border-radius: 16px; box-shadow: 0 4px 16px rgba(0, 61, 130, 0.08); border: 2px solid rgba(0, 61, 130, 0.08); width: 100%; box-sizing: border-box;">
        <!-- Single Calendar Container (shown for Month and Week views) -->
        <div id="calendar-container" style="background: white; padding: 12px; border: 1px solid #ddd; border-radius: 4px; min-height: 700px; display: block; width: 100%; overflow-x: auto; box-sizing: border-box;">
            <div id="calendar"></div>
        </div>

        <!-- Day View Tab Content -->
        <div class="tab-content" style="width: 100%; box-sizing: border-box;">
            <div class="tab-pane fade" id="day-view" role="tabpanel" style="width: 100%; box-sizing: border-box;">
                <div style="width: 100%; margin-bottom: 20px; box-sizing: border-box;">
                    <div style="display: flex; gap: 10px; width: 100%; box-sizing: border-box;">
                        <button type="button" class="btn" id="back-to-calendar" onclick="handleBackToCalendar()" style="padding: 12px 24px; background: #003d82; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; color: white;">
                            <i class="fas fa-arrow-left"></i> Back to Calendar
                        </button>
                        <button type="button" class="btn" id="prev-day" onclick="handlePrevDay()" style="padding: 12px 24px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; font-weight: 600; color: #003d82;">
                            <i class="fas fa-chevron-left"></i> Previous Day
                        </button>
                        <button type="button" class="btn" id="current-day-display" style="flex: 1; padding: 12px 24px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 10px; cursor: default; font-weight: 600; color: #003d82;">
                            <span id="current-day-text">Today</span>
                        </button>
                        <button type="button" class="btn" id="next-day" onclick="handleNextDay()" style="padding: 12px 24px; background: #f8f9fa; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; font-weight: 600; color: #003d82;">
                            Next Day <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div id="day-timeline-container" style="overflow-x: auto; overflow-y: auto; padding: 20px; background: white; border: 1px solid #ddd; border-radius: 4px; width: 100%; height: auto; min-height: 600px; box-sizing: border-box;">
                    <canvas id="day-timeline-canvas" style="display: block; margin: 0 auto; border: 1px solid #f0f0f0; border-radius: 4px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
