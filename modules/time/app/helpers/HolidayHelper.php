<?php

namespace App\Helpers;

use App\Models\Holiday;

class HolidayHelper
{
    private static $holiday = null;
    private static $db = null;

    /**
     * Initialize helper with database instance
     */
    public static function init($database)
    {
        self::$db = $database;
        self::$holiday = new Holiday($database);
    }

    /**
     * Check if a date is a holiday
     */
    public static function isHoliday($date)
    {
        if (!self::$holiday) {
            return false;
        }

        return self::$holiday->isHoliday($date);
    }

    /**
     * Get holiday details for a date
     */
    public static function getHolidayByDate($date)
    {
        if (!self::$holiday) {
            return null;
        }

        return self::$holiday->getHolidayByDate($date);
    }

    /**
     * Get upcoming holidays count
     */
    public static function getUpcomingCount($days = 30)
    {
        if (!self::$holiday) {
            return 0;
        }

        $holidays = self::$holiday->getUpcomingHolidays($days);
        return count($holidays);
    }

    /**
     * Get next holiday info
     */
    public static function getNextHoliday()
    {
        if (!self::$holiday) {
            return null;
        }

        $holidays = self::$holiday->getUpcomingHolidays(365);

        if (!empty($holidays)) {
            return $holidays[0]; // First upcoming holiday
        }

        return null;
    }

    /**
     * Calculate days until next holiday
     */
    public static function daysUntilNextHoliday()
    {
        $nextHoliday = self::getNextHoliday();

        if (!$nextHoliday) {
            return null;
        }

        $today = new \DateTime();
        $holidayDate = new \DateTime($nextHoliday['holiday_date']);
        $diff = $holidayDate->diff($today);

        return $diff->days;
    }

    /**
     * Check if employee should be marked as holiday (not absent)
     * Returns: 'HOLIDAY', 'ABSENT', 'PRESENT' or null
     */
    public static function getAttendanceStatus($date, $isCheckedIn = false)
    {
        if (self::isHoliday($date)) {
            return 'HOLIDAY';
        }

        if (!$isCheckedIn) {
            return 'ABSENT';
        }

        return 'PRESENT';
    }

    /**
     * Format holiday for display
     */
    public static function formatHoliday($holiday)
    {
        return [
            'id' => $holiday['id'],
            'name' => $holiday['name'],
            'date' => $holiday['holiday_date'],
            'dateFormatted' => date('F j, Y', strtotime($holiday['holiday_date'])),
            'shortDate' => date('M j', strtotime($holiday['holiday_date'])),
            'daysLeft' => self::daysUntilDate($holiday['holiday_date']),
            'category' => $holiday['category'],
            'recurring' => $holiday['is_recurring'],
            'description' => $holiday['description']
        ];
    }

    /**
     * Calculate days until a specific date
     */
    public static function daysUntilDate($targetDate)
    {
        $today = new \DateTime();
        $target = new \DateTime($targetDate);
        
        $today->setTime(0, 0, 0);
        $target->setTime(0, 0, 0);

        $diff = $target->diff($today);

        // Return negative if date is in past
        return $target >= $today ? $diff->days : -$diff->days;
    }

    /**
     * Get holidays for calendar display
     */
    public static function getHolidaysForCalendar($year, $month = null)
    {
        if (!self::$holiday) {
            return [];
        }

        $filters = ['year' => $year];
        if ($month) {
            $filters['month'] = $month;
        }

        $holidays = self::$holiday->getAllHolidays($filters);
        $formatted = [];

        foreach ($holidays as $holiday) {
            $formatted[] = [
                'id' => 'holiday-' . $holiday['id'],
                'title' => $holiday['name'],
                'start' => $holiday['holiday_date'],
                'end' => $holiday['holiday_date'],
                'extendedProps' => [
                    'category' => $holiday['category'],
                    'recurring' => $holiday['is_recurring'],
                    'isHoliday' => true
                ],
                'backgroundColor' => self::getCategoryColor($holiday['category']),
                'borderColor' => self::getCategoryColor($holiday['category']),
                'textColor' => '#fff'
            ];
        }

        return $formatted;
    }

    /**
     * Get color based on holiday category
     */
    public static function getCategoryColor($category)
    {
        $colors = [
            'national' => '#e74c3c',   // Red
            'regional' => '#f39c12',   // Orange
            'optional' => '#3498db',   // Blue
            'special' => '#9b59b6'     // Purple
        ];

        return $colors[$category] ?? '#95a5a6'; // Gray default
    }

    /**
     * Check if two dates overlap considering holidays
     * Useful for leave requests validation
     */
    public static function hasHolidaysBetween($startDate, $endDate)
    {
        if (!self::$holiday) {
            return false;
        }

        $holidays = self::$holiday->getHolidaysByRange($startDate, $endDate);
        return count($holidays) > 0;
    }

    /**
     * Get holidays between dates
     */
    public static function getHolidaysBetween($startDate, $endDate)
    {
        if (!self::$holiday) {
            return [];
        }

        return self::$holiday->getHolidaysByRange($startDate, $endDate);
    }

    /**
     * Get holiday statistics
     */
    public static function getHolidayStats($year = null)
    {
        if (!self::$holiday) {
            return null;
        }

        $year = $year ?? date('Y');
        $filters = ['year' => $year];

        $all = self::$holiday->getAllHolidays($filters);

        $stats = [
            'total' => count($all),
            'national' => 0,
            'regional' => 0,
            'optional' => 0,
            'recurring' => 0
        ];

        foreach ($all as $holiday) {
            $stats[$holiday['category']]++;
            if ($holiday['is_recurring']) {
                $stats['recurring']++;
            }
        }

        return $stats;
    }
}
