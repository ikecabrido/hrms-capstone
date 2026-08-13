<?php
/**
 * Helper Functions for Time & Attendance System
 * Contains utility functions for date handling, status checks, and common operations
 */

class Helper
{
    /**
     * Get current time with timezone support
     */
    public static function getCurrentDateTime()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * Get current date
     */
    public static function getCurrentDate()
    {
        return date("Y-m-d");
    }

    /**
     * Format time for display (HH:MM AM/PM)
     */
    public static function formatTime($datetime)
    {
        if (empty($datetime)) {
            return "N/A";
        }
        return date("h:i A", strtotime($datetime));
    }

    /**
     * Format date for display (Month DD, YYYY)
     */
    public static function formatDate($date)
    {
        if (empty($date)) {
            return "N/A";
        }
        return date("M d, Y", strtotime($date));
    }

    /**
     * Calculate attendance duration between time_in and time_out
     */
    public static function calculateDuration($time_in, $time_out)
    {
        if (empty($time_in) || empty($time_out)) {
            return "N/A";
        }

        $in = strtotime($time_in);
        $out = strtotime($time_out);
        $duration = $out - $in;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);

        return $hours . "h " . $minutes . "m";
    }

    /**
     * Check if current time is within allowed time-in window
     * Default: 6:00 AM to 9:00 AM
     */
    public static function isWithinTimeInWindow($start_hour = 6, $end_hour = 9)
    {
        $current_hour = (int)date("H");
        return $current_hour >= $start_hour && $current_hour < $end_hour;
    }

    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitize($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Determine attendance status based on time-in
     * Returns: EARLY (before 6:30), ON_TIME (6:30-9:00), LATE (after 9:00)
     */
    public static function determineStatus($time_in)
    {
        if (empty($time_in)) {
            return "ABSENT";
        }

        try {
            $timeInObj = new DateTime($time_in);
            $date = $timeInObj->format('Y-m-d');
            
            $earlyLimit = new DateTime($date . ' 06:30:00');
            $onTimeLimit = new DateTime($date . ' 09:00:00');

            if ($timeInObj < $earlyLimit) {
                return 'EARLY';
            } elseif ($timeInObj < $onTimeLimit) {
                return 'ON_TIME';
            } else {
                return 'LATE';
            }
        } catch (Exception $e) {
            error_log("determineStatus error: " . $e->getMessage());
            return 'UNKNOWN';
        }
    }

    /**
     * Validate email format
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Get client IP address
     */
    public static function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Get user agent
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Redirect to page
     */
    public static function redirect($location)
    {
        header("Location: " . $location);
        exit;
    }

    /**
     * Return JSON response
     */
    public static function jsonResponse($success, $message, $data = null)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    /**
     * Calculate total hours, regular hours, and overtime
     * Regular hours: max 8 per day
     * Overtime hours: anything beyond 8 hours
     * 
     * @param string $time_in DateTime string (YYYY-MM-DD HH:MM:SS)
     * @param string $time_out DateTime string (YYYY-MM-DD HH:MM:SS)
     * @param int $standard_hours Default working hours per day (default 8)
     * @return array ['total_hours' => float, 'regular_hours' => float, 'overtime_hours' => float]
     */
    public static function calculateHours($time_in, $time_out, $standard_hours = 8)
    {
        if (empty($time_in) || empty($time_out)) {
            return [
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0
            ];
        }

        $in = strtotime($time_in);
        $out = strtotime($time_out);
        $duration_seconds = $out - $in;

        // Convert seconds to decimal hours
        $total_hours = round($duration_seconds / 3600, 2);
        
        // Regular hours capped at standard_hours
        $regular_hours = min($total_hours, $standard_hours);
        
        // Overtime hours = anything beyond standard
        $overtime_hours = max(0, $total_hours - $standard_hours);

        return [
            'total_hours' => $total_hours,
            'regular_hours' => round($regular_hours, 2),
            'overtime_hours' => round($overtime_hours, 2)
        ];
    }

    /**
     * Calculate leave days between two dates, excluding Sundays and optional holidays.
     *
     * @param string $start_date YYYY-MM-DD
     * @param string $end_date YYYY-MM-DD
     * @param array $holidays Optional array of holiday dates to check against
     * @return int Number of working days
     */
    public static function calculateWorkingDays($start_date, $end_date, $holidays = [])
    {
        if (empty($start_date) || empty($end_date)) {
            return 0;
        }

        try {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
        } catch (Exception $e) {
            error_log("calculateWorkingDays error: " . $e->getMessage());
            return 0;
        }

        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);

        if ($start > $end) {
            return 0;
        }

        $working_days = 0;
        $normalizedHolidays = array_map(function ($holiday) {
            return date('Y-m-d', strtotime($holiday));
        }, $holidays);

        $current = clone $start;
        while ($current <= $end) {
            $dateString = $current->format('Y-m-d');
            if (!self::isNonWorkingDay($dateString, $normalizedHolidays)) {
                $working_days++;
            }
            $current->modify('+1 day');
        }

        return $working_days;
    }

    /**
     * Validate leave request dates and calculate the number of working days.
     *
     * @param string $start_date YYYY-MM-DD
     * @param string $end_date YYYY-MM-DD
     * @param array $holidays Optional array of holiday dates to check against
     * @return array ['valid' => bool, 'message' => string, 'total_days' => int]
     */
    public static function validateLeaveRequestDates($start_date, $end_date, $holidays = [])
    {
        if (empty($start_date) || empty($end_date)) {
            return ['valid' => false, 'message' => 'Start date and end date are required', 'total_days' => 0];
        }

        try {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Invalid date format', 'total_days' => 0];
        }

        if ($start > $end) {
            return ['valid' => false, 'message' => 'End date must be on or after the start date', 'total_days' => 0];
        }

        $total_days = self::calculateWorkingDays($start_date, $end_date, $holidays);
        if ($total_days < 1) {
            return ['valid' => false, 'message' => 'Leave must cover at least one working day', 'total_days' => 0];
        }

        return ['valid' => true, 'message' => '', 'total_days' => $total_days];
    }

    /**
     * Check if a given date is a Sunday or holiday
     *
     * @param string $date YYYY-MM-DD
     * @param array $holidays Optional array of holiday dates to check against
     * @return bool True if date is Sunday or in holidays array
     */
    public static function isNonWorkingDay($date, $holidays = [])
    {
        $day_of_week = date('w', strtotime($date));

        // 0 = Sunday
        if ($day_of_week == 0) {
            return true;
        }

        // Check if in holidays array
        return in_array($date, $holidays);
    }
}
