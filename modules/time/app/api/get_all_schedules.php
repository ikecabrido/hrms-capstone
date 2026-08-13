<?php
/**
 * Get All Employees Schedules API
 * Returns shifts and attendance for all employees for a date range
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../database/db.php';

try {
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if (!$start_date || !$end_date) {
        throw new Exception('Date range is required');
    }

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get all active employees
    $employees_query = "SELECT employee_id, full_name FROM employees WHERE employment_status = 'Active' ORDER BY full_name";
    $stmt = $conn->prepare($employees_query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance records for the date range
    $attendance_query = "SELECT a.*, e.full_name
                        FROM ta_attendance a
                        JOIN employees e ON a.employee_id = e.employee_id
                        WHERE a.attendance_date BETWEEN ? AND ?
                        ORDER BY a.attendance_date, e.full_name";
    
    $stmt = $conn->prepare($attendance_query);
    $stmt->execute([$start_date, $end_date]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get employee shifts
    $shifts_query = "SELECT es.*, s.shift_name, s.start_time, s.end_time, e.full_name
                    FROM ta_employee_shifts es
                    JOIN ta_shifts s ON es.shift_id = s.shift_id
                    JOIN employees e ON es.employee_id = e.employee_id
                    WHERE es.is_active = 1";
    
    $stmt = $conn->prepare($shifts_query);
    $stmt->execute();
    $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get flexible schedules for the date range (both one-time and recurring)
    $flexible_query = "SELECT fs.*, e.full_name
                       FROM ta_flexible_schedules fs
                       JOIN employees e ON fs.employee_id = e.employee_id
                       WHERE (fs.schedule_date BETWEEN ? AND ?)
                          OR (fs.day_of_week IS NOT NULL AND 
                              (fs.repeat_until IS NULL OR fs.repeat_until >= ?) AND
                              (fs.contract_end_date IS NULL OR fs.contract_end_date >= ?))
                       ORDER BY fs.schedule_date, e.full_name";
    
    $stmt = $conn->prepare($flexible_query);
    $stmt->execute([$start_date, $end_date, $start_date, $start_date]);
    $flexible_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if flexible schedules table exists
    try {
        $check_table = "SELECT COUNT(*) as count FROM ta_flexible_schedules LIMIT 1";
        $conn->query($check_table);
    } catch (Exception $e) {
        error_log("Flexible schedules table issue: " . $e->getMessage());
    }

    // Build schedule data with events for all employees
    $schedule_data = [];
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end);

    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $day_of_week = (int)$date->format('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
        
        // Get attendance for this day
        $day_attendance = array_filter($attendance_records, function($att) use ($date_str) {
            return $att['attendance_date'] === $date_str;
        });

        // Create events for each attendance record
        foreach ($day_attendance as $att) {
            $employee_name = $att['full_name'];
            
            // Determine status
            $status = 'present';
            if (empty($att['time_in'])) {
                $status = 'absent';
            } elseif ($att['time_in']) {
                $time_in = new DateTime($att['time_in']);
                if ($time_in->format('H') > 9) {
                    $status = 'late';
                }
            }

            $schedule_data[] = [
                'id' => 'att_' . $att['attendance_id'],
                'title' => $employee_name . ' (' . ucfirst($status) . ')',
                'start' => $att['attendance_date'] . 'T' . ($att['time_in'] ? date('H:i', strtotime($att['time_in'])) : '00:00'),
                'end' => $att['attendance_date'] . 'T' . ($att['time_out'] ? date('H:i', strtotime($att['time_out'])) : '23:59'),
                'className' => $status . '-event',
                'extendedProps' => [
                    'employee_id' => $att['employee_id'],
                    'employee_name' => $employee_name,
                    'status' => $status,
                    'time_in' => $att['time_in'],
                    'time_out' => $att['time_out']
                ]
            ];
        }

        // Create events for flexible schedules
        foreach ($flexible_schedules as $flex) {
            $employee_name = $flex['full_name'];
            $start_time = date('H:i', strtotime($flex['start_time']));
            $end_time = date('H:i', strtotime($flex['end_time']));
            
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
                $schedule_data[] = [
                    'id' => 'flex_' . $flex['id'] . '_' . $date_str,
                    'title' => $employee_name . ' (Flexible)',
                    'start' => $date_str . 'T' . $start_time,
                    'end' => $date_str . 'T' . $end_time,
                    'className' => 'flexible-event',
                    'extendedProps' => [
                        'employee_id' => $flex['employee_id'],
                        'employee_name' => $employee_name,
                        'type' => 'flexible',
                        'start_time' => $flex['start_time'],
                        'end_time' => $flex['end_time'],
                        'notes' => $flex['notes'] ?? ''
                    ]
                ];
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $schedule_data,
        'message' => 'Schedules retrieved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
