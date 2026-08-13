<?php
/**
 * Shift Controller
 * Handles shift management operations
 * 
 * @package Time_and_Attendance
 * @subpackage Controllers
 */

require_once(__DIR__ . '/../models/Shift.php');
require_once(__DIR__ . '/../models/EmployeeShift.php');
require_once(__DIR__ . '/../helpers/AuditLog.php');
// Debug helper (writes to time_attendance/debug_shifts.log)
require_once __DIR__ . '/../../debug_helpers.php';

class ShiftController {
    private $db;
    private $shift;
    private $employeeShift;
    private $auditLog;

    public function __construct($db) {
        $this->db = $db;
        // Diagnostic checkpoint: constructor start
        if (function_exists('shiftDebug')) shiftDebug('[DIAG] ShiftController::__construct start');
        $this->shift = new Shift($db);
        $this->employeeShift = new EmployeeShift($db);
        $this->auditLog = new AuditLog($db);
        // Diagnostic checkpoints
        if (function_exists('shiftDebug')) shiftDebug('[DIAG] ShiftController::__construct end');
    }

    /**
     * Update an employee's current assignment. If no current active assignment exists, assign a new one.
     * Returns an array with success/message and optionally employee_shift_id.
     */
    public function updateEmployeeAssignment($employee_id, $shift_id, $effective_from, $effective_to = null) {
        if ($this->hasActiveFlexibleSchedule($employee_id)) {
            return ['success' => false, 'message' => 'Employee has an active flexible schedule. Remove it before assigning a regular shift.'];
        }

        $current = $this->getCurrentShift($employee_id);

        if (empty($current)) {
            // No active assignment, fall back to assign
            return $this->assignShiftToEmployee($employee_id, $shift_id, $effective_from, $effective_to);
        }

        // Update the existing active assignment
        $this->employeeShift->employee_shift_id = $current['employee_shift_id'];
        $this->employeeShift->shift_id = $shift_id;
        $this->employeeShift->effective_from = $effective_from;
        $this->employeeShift->effective_to = $effective_to;
        $this->employeeShift->is_active = 1;

        if ($this->employeeShift->update()) {
            // Log action
            $this->auditLog->log(
                'ASSIGNMENT_UPDATED',
                $_SESSION['user_id'] ?? null,
                $employee_id,
                null,
                ['table' => 'employee_shifts', 'message' => 'Updated assignment for employee ID: ' . $employee_id]
            );

            return ['success' => true, 'message' => 'Assignment updated successfully', 'employee_shift_id' => $this->employeeShift->employee_shift_id];
        }

        return ['success' => false, 'message' => 'Failed to update assignment'];
    }

    /**
     * Get all shifts
     */
    public function getAllShifts($active_only = false) {
        return $this->shift->getAll($active_only);
    }

    /**
     * Get shift by ID
     */
    public function getShiftById($shift_id) {
        return $this->shift->getById($shift_id);
    }

    /**
     * Create new shift
     */
    public function createShift($data) {
        // Validate required fields
        if (empty($data['shift_name']) || empty($data['start_time']) || empty($data['end_time'])) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        $this->shift->shift_name = $data['shift_name'];
        $this->shift->start_time = $data['start_time'];
        $this->shift->end_time = $data['end_time'];
        $this->shift->break_duration = $data['break_duration'] ?? 60;
        $this->shift->description = $data['description'] ?? null;
        $this->shift->is_active = $data['is_active'] ?? 1;

        if ($this->shift->create()) {
            // Log action
            $this->auditLog->log(
                'SHIFT_CREATED',
                $_SESSION['user_id'] ?? null,
                null,
                null,
                ['table' => 'shifts', 'shift_name' => $this->shift->shift_name, 'message' => 'Created new shift: ' . $this->shift->shift_name]
            );

            return ['success' => true, 'message' => 'Shift created successfully'];
        }

        return ['success' => false, 'message' => 'Failed to create shift'];
    }

    /**
     * Update shift
     */
    public function updateShift($shift_id, $data) {
        $shift = $this->shift->getById($shift_id);
        if (empty($shift)) {
            return ['success' => false, 'message' => 'Shift not found'];
        }

        $this->shift->shift_id = $shift_id;
        $this->shift->shift_name = $data['shift_name'] ?? $shift['shift_name'];
        $this->shift->start_time = $data['start_time'] ?? $shift['start_time'];
        $this->shift->end_time = $data['end_time'] ?? $shift['end_time'];
        $this->shift->break_duration = $data['break_duration'] ?? $shift['break_duration'];
        $this->shift->description = $data['description'] ?? $shift['description'];
        $this->shift->is_active = isset($data['is_active']) ? $data['is_active'] : $shift['is_active'];

        if ($this->shift->update()) {
            // Log action
            $this->auditLog->log(
                'SHIFT_UPDATED',
                $_SESSION['user_id'] ?? null,
                null,
                null,
                ['table' => 'shifts', 'shift_id' => $shift_id, 'message' => 'Updated shift: ' . $this->shift->shift_name]
            );

            return ['success' => true, 'message' => 'Shift updated successfully'];
        }

        return ['success' => false, 'message' => 'Failed to update shift'];
    }

    /**
     * Delete shift
     */
    public function deleteShift($shift_id) {
        $shift = $this->shift->getById($shift_id);
        if (empty($shift)) {
            return ['success' => false, 'message' => 'Shift not found'];
        }

        if ($this->shift->delete($shift_id)) {
            // Log action
            $this->auditLog->log(
                'SHIFT_DELETED',
                $_SESSION['user_id'] ?? null,
                null,
                null,
                ['table' => 'shifts', 'shift_id' => $shift_id, 'message' => 'Deleted shift: ' . $shift['shift_name']]
            );

            return ['success' => true, 'message' => 'Shift deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete shift'];
    }

    /**
     * Assign shift to employee
     */
    public function assignShiftToEmployee($employee_id, $shift_id, $effective_from, $effective_to = null) {
        if ($this->hasActiveFlexibleSchedule($employee_id)) {
            return ['success' => false, 'message' => 'Employee has an active flexible schedule. Remove it before assigning a regular shift.'];
        }

        // Validate shift exists
        $shift = $this->shift->getById($shift_id);
        if (empty($shift)) {
            return ['success' => false, 'message' => 'Shift not found'];
        }

        $this->employeeShift->employee_id = $employee_id;
        $this->employeeShift->shift_id = $shift_id;
        $this->employeeShift->effective_from = $effective_from;
        $this->employeeShift->effective_to = $effective_to;
        $this->employeeShift->is_active = 1;

        if ($this->employeeShift->assign()) {
            // Log action
            $this->auditLog->log(
                'SHIFT_ASSIGNED',
                $_SESSION['user_id'] ?? null,
                $employee_id,
                null,
                ['table' => 'employee_shifts', 'shift_name' => $shift['shift_name'], 'message' => 'Assigned ' . $shift['shift_name'] . ' to employee ID: ' . $employee_id]
            );

            return ['success' => true, 'message' => 'Shift assigned successfully'];
        }

        return ['success' => false, 'message' => 'Failed to assign shift'];
    }

    /**
     * Get employee shift assignments
     */
    public function getEmployeeShifts($employee_id) {
        return $this->employeeShift->getEmployeeAssignments($employee_id, false);
    }

    /**
     * Get current shift for employee
     */
    public function getCurrentShift($employee_id) {
        return $this->employeeShift->getCurrentShift($employee_id);
    }

    /**
     * Check if employee has an active flexible schedule
     */
    public function hasActiveFlexibleSchedule($employee_id, $date = null) {
        return $this->employeeShift->getActiveFlexibleSchedule($employee_id, $date);
    }

    /**
     * Get employees on specific shift
     */
    public function getEmployeesOnShift($shift_id) {
        return $this->employeeShift->getAllAssignments($shift_id);
    }

    /**
     * Check if time is within shift hours
     */
    public function isWithinShiftHours($employee_id, $time, $date = null) {
        return $this->shift->isWithinShiftHours($employee_id, $time, $date);
    }

    /**
     * Get shift statistics
     */
    public function getShiftStatistics() {
        $shifts = $this->shift->getAll();
        $stats = [];

        foreach ($shifts as $shift) {
            $count = $this->employeeShift->getShiftEmployeeCount($shift['shift_id']);
            $stats[] = [
                'shift_id' => $shift['shift_id'],
                'shift_name' => $shift['shift_name'],
                'start_time' => $shift['start_time'],
                'end_time' => $shift['end_time'],
                'employee_count' => $count,
                'is_active' => $shift['is_active']
            ];
        }

        return $stats;
    }
}
?>
