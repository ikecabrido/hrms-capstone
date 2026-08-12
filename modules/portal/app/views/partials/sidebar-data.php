<?php


$employeeDashboard = $this->employeeModel->getByUserId($_SESSION['user_id']);

$employeeName = trim(
    ($employeeDashboard['first_name'] ?? '') . ' ' .
        ($employeeDashboard['middle_name'] ?? '') . ' ' .
        ($employeeDashboard['last_name'] ?? '') .
        (!empty($employeeDashboard['suffix']) ? ' ' . $employeeDashboard['suffix'] : '')
);

$employeeName = $employeeName !== ''
    ? $employeeName
    : 'Employee Name';

$employeePosition = $employeeDashboard['position'] ?? 'Employee Position';

$employeeInitial = strtoupper(
    substr($employeeDashboard['first_name'] ?? 'E', 0, 1)
);
