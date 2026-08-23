<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Announcement;

class PortalController
{
    private Employee $employeeModel;
    private Announcement $announcementModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->announcementModel = new Announcement();
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
        $announcements = $this->announcementModel->all();

        $title = "Employee Dashboard";
        $content = __DIR__ . '/../views/employee-portal/content.php';

        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminDashboard()
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
        
        $title = "Admin Dashboard";
        $content = __DIR__ . '/../views/admin-portal/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
}
