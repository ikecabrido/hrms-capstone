<?php
/**
 * Get Employee Schedule API
 * Returns shifts and attendance for a specific employee and date range
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../core/TimeDatabase.php';
require_once __DIR__ . '/../../models/EmployeeShift.php';
require_once __DIR__ . '/../../models/Attendance.php';

try {
    $employee_id = $_GET['employee_id'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if (!$employee_id) {
        throw new Exception('Employee ID is required');
    }

    if (!$start_date || !$end_date) {
        throw new Exception('Date range is required');
    }

    $db = TimeDatabase::getInstance();
    $conn = $db->getConnection();

    // Get employee info from the canonical Time module employee table
    $employee_query = "SELECT employee_id,
                            CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) AS full_name,
                            first_name,
                            last_name,
                            department,
                            position,
                            employment_status
                       FROM em_employees
                       WHERE employee_id = ? AND employment_status = 'Active'";
    $stmt = $conn->prepare($employee_query);
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee not found');
    }

    // Get current employee shift assignment
    $shift_query = "SELECT es.*, s.* 
                    FROM ta_employee_shifts es
                    JOIN ta_shifts s ON es.shift_id = s.shift_id
                    WHERE es.employee_id = ? AND es.is_active = 1
                    LIMIT 1";
    
    $stmt = $conn->prepare($shift_query);
    $stmt->execute([$employee_id]);
    $current_shift = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get attendance records for the date range
    $attendance_query = "SELECT * FROM ta_attendance 
                        WHERE employee_id = ? 
                        AND attendance_date BETWEEN ? AND ?
                        ORDER BY attendance_date ASC";
    
    $stmt = $conn->prepare($attendance_query);
    $stmt->execute([$employee_id, $start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get flexible schedules for this employee (both one-time and recurring)
    $flexible_query = "SELECT * FROM ta_flexible_schedules
                       WHERE employee_id = ?
                       AND ((schedule_date BETWEEN ? AND ?)
                          OR (day_of_week IS NOT NULL AND 
                              (repeat_until IS NULL OR repeat_until >= ?) AND
                              (contract_end_date IS NULL OR contract_end_date >= ?)))
                       ORDER BY schedule_date ASC";
    
    $stmt = $conn->prepare($flexible_query);
    $stmt->execute([$employee_id, $start_date, $end_date, $start_date, $start_date]);
    $flexible_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all available shifts for reference
    $shifts_query = "SELECT * FROM ta_shifts WHERE is_active = 1 ORDER BY start_time";
    $stmt = $conn->prepare($shifts_query);
    $stmt->execute();
    $available_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build schedule data
    $schedule_data = [];
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

        $holiday_query = "SELECT holiday_date, name, holiday_scope, province_name, is_working_day
                                            FROM ta_holidays
                                            WHERE is_active = 1
                                                AND holiday_date BETWEEN ? AND ?
                                            ORDER BY holiday_date ASC, id ASC";
        $holiday_stmt = $conn->prepare($holiday_query);
        $holiday_stmt->execute([$start_date, $end_date]);
        $holidays_by_date = [];
        foreach ($holiday_stmt->fetchAll(PDO::FETCH_ASSOC) as $holiday) {
                $holidays_by_date[$holiday['holiday_date']] = $holiday;
        }

    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $day_of_week = (int)$date->format('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
        $day_data = [
            'date' => $date_str,
            'day_name' => $date->format('l'),
            'shift' => $current_shift,
            'attendance' => null,
            'flexible' => null,
            'holiday' => $holidays_by_date[$date_str] ?? null,
            'has_exclusion' => false
        ];

                // Fixed schedules are represented by employee-shift assignments.
                // Resolve the assignment for this date instead of reading a separate
                // custom-shift table.
                $day_shift_query = "SELECT es.*, s.*
                                                        FROM ta_employee_shifts es
                                                        JOIN ta_shifts s ON es.shift_id = s.shift_id
                                                        WHERE es.employee_id = ?
                                                            AND es.is_active = 1
                                                            AND es.effective_from <= ?
                                                            AND (es.effective_to IS NULL OR es.effective_to >= ?)
                                                        ORDER BY es.effective_from DESC, es.employee_shift_id DESC
                                                        LIMIT 1";
                $day_shift_stmt = $conn->prepare($day_shift_query);
                $day_shift_stmt->execute([$employee_id, $date_str, $date_str]);
                $day_shift = $day_shift_stmt->fetch(PDO::FETCH_ASSOC);
                $day_data['shift'] = $day_shift ?: null;

        // Check if this employee has a shift exclusion for this date
        $exclusion_query = "SELECT COUNT(*) as exclusion_count 
                           FROM ta_shift_exclusions se
                           WHERE se.exclusion_date = :date
                           AND se.employee_shift_id IN (
                               SELECT employee_shift_id FROM ta_employee_shifts 
                               WHERE employee_id = :employee_id 
                               AND is_active = 1
                               AND effective_from <= :date
                               AND (effective_to IS NULL OR effective_to >= :date)
                           )";
        
        try {
            $stmt = $conn->prepare($exclusion_query);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':date', $date_str);
            $stmt->execute();
            
            $exclusion_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $day_data['has_exclusion'] = $exclusion_result['exclusion_count'] > 0;
        } catch (Exception $e) {
            // Table might not exist, ignore
            $day_data['has_exclusion'] = false;
        }

        // Find attendance for this date
        foreach ($attendance_records as $record) {
            if ($record['attendance_date'] === $date_str) {
                $day_data['attendance'] = $record;
                break;
            }
        }

        if (!$day_data['has_exclusion']) {
            // Find flexible schedule for this date (one-time or recurring)
            // BUT SKIP if employee has a shift exclusion for this date
                foreach ($flexible_schedules as $flex) {
                    $should_display = false;
                    
                    // Check if it's a one-time schedule that matches this date
                    if ($flex['schedule_date'] === $date_str) {
                        $should_display = true;
                    }
                    
                    // Check if it's a recurring schedule that matches this day of week
                    if (!$should_display && $flex['day_of_week'] !== null) {
                        if ((int)$flex['day_of_week'] === $day_of_week) {
                            // Check if we're within the repeat_until or contract_end_date range
                            $repeat_until = $flex['repeat_until'] ? new DateTime($flex['repeat_until']) : null;
                            $contract_end = $flex['contract_end_date'] ? new DateTime($flex['contract_end_date']) : null;
                            $current_date = new DateTime($date_str);
                            
                            // Determine the end date (whichever is later or exists)
                            $end_limit = null;
                            if ($repeat_until && $contract_end) {
                                $end_limit = $repeat_until > $contract_end ? $repeat_until : $contract_end;
                            } elseif ($repeat_until) {
                                $end_limit = $repeat_until;
                            } elseif ($contract_end) {
                                $end_limit = $contract_end;
                            }
                            
                            // If no end limit, show indefinitely (until end of calendar view)
                            if (!$end_limit) {
                                $should_display = true;
                            } elseif ($current_date <= $end_limit) {
                                $should_display = true;
                            }
                        }
                    }
                    
                    if ($should_display) {
                        $day_data['flexible'] = $flex;
                        break;
                    }
                }
        }

        $schedule_data[] = $day_data;
    }

    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'current_shift' => $current_shift,
        'available_shifts' => $available_shifts,
        'schedule' => $schedule_data
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
