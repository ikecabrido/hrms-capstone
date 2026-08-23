<?php

namespace App\Middleware;

use App\Core\Session;

class ProtectedRoutes
{
    private static array $routes = [

        // Dashboard
        'employee-dashboard',
        'admin-dashboard',

        // Profile
        'user-profile',
        'update-password',
        'update-user-profile',
        'update-profile-image',

        // Attendance
        'attendance',

        // Leave
        'leave-request',
        'leave-store',

        // Payroll
        'payroll',
        'payroll-request-store',

        // Benefits
        'benefits-and-government-contribution',
        'employee-benefits-store',

        // Announcement
        'announcement',
        'announcement-view',

        // Notification
        'notification',
        'notification-mark-read',
        'notification-mark-all-read',

        // Performance
        'performance',

        // Complaint
        'complaint',
    ];

    public static function check(string $url): void
    {
        if (
            in_array($url, self::$routes, true) &&
            !Session::get('user_id')
        ) {
            header('Location: index.php?url=auth-index&error=session_timeout');
            exit;
        }
    }
}