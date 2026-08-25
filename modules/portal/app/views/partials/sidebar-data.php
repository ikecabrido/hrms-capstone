<?php

use App\Models\Employee;
use App\Models\Users;
use App\Models\NotificationRecipient;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$employeeModel = new Employee();
$usersModel = new Users();

$userId = $_SESSION['user_id'] ?? null;

/*
|--------------------------------------------------------------------------
| Employee Image
|--------------------------------------------------------------------------
*/
$employeeImage = $userId
    ? $usersModel->findById($userId)
    : [];

/*
|--------------------------------------------------------------------------
| Employee
|--------------------------------------------------------------------------
*/

$employeeDashboard = $userId
    ? $employeeModel->getByUserId($userId)
    : [];

$employeeProfileInfo = $userId
    ? $employeeModel->findByUserId($userId)
    : [];


/*
|--------------------------------------------------------------------------
| Employee Name
|--------------------------------------------------------------------------
*/

$employeeName = trim(
    ($employeeDashboard['first_name'] ?? '') . ' ' .
    ($employeeDashboard['middle_name'] ?? '') . ' ' .
    ($employeeDashboard['last_name'] ?? '') .
    (!empty($employeeDashboard['suffix'])
        ? ' ' . $employeeDashboard['suffix']
        : '')
);

$employeeName = $employeeName !== ''
    ? $employeeName
    : 'Employee Name';


/*
|--------------------------------------------------------------------------
| Employee Position
|--------------------------------------------------------------------------
*/

$employeePosition = $employeeDashboard['position']
    ?? 'Employee Position';


/*
|--------------------------------------------------------------------------
| Employee Initial
|--------------------------------------------------------------------------
*/

$employeeInitial = strtoupper(
    substr(
        $employeeDashboard['first_name'] ?? 'E',
        0,
        1
    )
);


/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/

$employeeNotification = [];

if (!empty($employeeProfileInfo['id'])) {

    $notificationRecipientModel = new NotificationRecipient();

    $employeeNotification = $notificationRecipientModel
        ->getEmployeeNotifications(
            $employeeProfileInfo['id']
        );
}