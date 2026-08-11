<?php

namespace App\Controllers;

use App\Models\Employee;

class PortalController
{
    private Employee $employeeModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
    }
    public function dashboard()
    {
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

        $title = "Employee Dashboard";
        $content = __DIR__ . '/../views/employee-portal/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }
}
