<?php

namespace App\Core;

use App\Models\Notification;

class NotificationHelper
{
    public static function getEmployeeNotifications(int $employeeId): array
    {
        if ($employeeId <= 0) {
            return [
                'count' => 0,
                'latest' => []
            ];
        }

        $notificationModel = new Notification();

        return [
            'count' => $notificationModel->countUnread($employeeId),
            'latest' => $notificationModel->latest($employeeId)
        ];
    }
}
