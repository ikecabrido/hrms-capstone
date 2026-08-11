<?php

use App\Models\Employee;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$employeeModel = new Employee();

$userId = $_SESSION['user_id'] ?? null;

$employeeDashboard = $userId
    ? $employeeModel->getByUserId($userId)
    : [];

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

$employeePosition = $employeeDashboard['position']
    ?? 'Employee Position';

$employeeInitial = strtoupper(
    substr(
        $employeeDashboard['first_name'] ?? 'E',
        0,
        1
    )
);
?>