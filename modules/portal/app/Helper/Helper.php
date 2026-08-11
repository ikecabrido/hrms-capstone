<?php

namespace App\Helper;

use DateTime;
use Throwable;

class Helper
{
    public static function getCurrentDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function getCurrentDate(): string
    {
        return date('Y-m-d');
    }

    public static function formatTime(?string $datetime): string
    {
        if (empty($datetime)) {
            return 'N/A';
        }

        return date('h:i A', strtotime($datetime));
    }

    public static function formatDate(?string $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        return date('M d, Y', strtotime($date));
    }

    public static function calculateDuration(
        ?string $time_in,
        ?string $time_out
    ): string {
        if (empty($time_in) || empty($time_out)) {
            return 'N/A';
        }

        $in = strtotime($time_in);
        $out = strtotime($time_out);
        $duration = $out - $in;

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);

        return $hours . 'h ' . $minutes . 'm';
    }

    public static function isWithinTimeInWindow(
        int $start_hour = 6,
        int $end_hour = 9
    ): bool {
        $current_hour = (int) date('H');

        return $current_hour >= $start_hour
            && $current_hour < $end_hour;
    }

    public static function sanitize(string $input): string
    {
        return htmlspecialchars(
            trim($input),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    public static function determineStatus(?string $time_in): string
    {
        if (empty($time_in)) {
            return 'ABSENT';
        }

        try {
            $timeInObj = new DateTime($time_in);
            $date = $timeInObj->format('Y-m-d');

            $earlyLimit = new DateTime($date . ' 06:30:00');
            $onTimeLimit = new DateTime($date . ' 09:00:00');

            if ($timeInObj < $earlyLimit) {
                return 'EARLY';
            }

            if ($timeInObj < $onTimeLimit) {
                return 'ON_TIME';
            }

            return 'LATE';
        } catch (Throwable $e) {
            error_log('determineStatus error: ' . $e->getMessage());

            return 'UNKNOWN';
        }
    }

    public static function validateEmail(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function getClientIP(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    public static function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }

    public static function jsonResponse(
        bool $success,
        string $message,
        mixed $data = null
    ): never {
        header('Content-Type: application/json');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);

        exit;
    }

    public static function calculateHours(
        ?string $time_in,
        ?string $time_out,
        int $standard_hours = 8
    ): array {
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

        $total_hours = round($duration_seconds / 3600, 2);

        $regular_hours = min(
            $total_hours,
            $standard_hours
        );

        $overtime_hours = max(
            0,
            $total_hours - $standard_hours
        );

        return [
            'total_hours' => $total_hours,
            'regular_hours' => round($regular_hours, 2),
            'overtime_hours' => round($overtime_hours, 2)
        ];
    }

    public static function calculateWorkingDays(
        string $start_date,
        string $end_date
    ): int {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $working_days = 0;

        while ($start <= $end) {
            if ((int) date('w', $start) !== 0) {
                $working_days++;
            }

            $start = strtotime('+1 day', $start);
        }

        return $working_days;
    }

    public static function isNonWorkingDay(
        string $date,
        array $holidays = []
    ): bool {
        $day_of_week = (int) date('w', strtotime($date));

        if ($day_of_week === 0) {
            return true;
        }

        return in_array($date, $holidays, true);
    }
}
