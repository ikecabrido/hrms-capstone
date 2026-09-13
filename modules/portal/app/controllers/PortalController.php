<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Announcement;
use App\Models\Performance;

class PortalController
{
    private Employee $employeeModel;
    private Announcement $announcementModel;
    private Performance $performanceModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->announcementModel = new Announcement();
        $this->performanceModel = new Performance();
    }
    public function dashboard()
    {
        $userId = $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);
        $employeeDashboard = $this->employeeModel->getByUserId($_SESSION['user_id']);

        $employeeName = trim(
            ($employeeDashboard['first_name'] ?? '') . ' ' .
            ($employeeDashboard['middle_name'] ?? '') . ' ' .
            ($employeeDashboard['last_name'] ?? '') .
            (!empty($employeeDashboard['suffix']) ? ' ' . $employeeDashboard['suffix'] : '')
        );
        $employeePerformanceFeedback = [];
        if (!empty($employee['employee_id'])) {
            $employeePerformanceFeedback = $this->performanceModel->getPerformance($employee['employee_id']);
        }
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
