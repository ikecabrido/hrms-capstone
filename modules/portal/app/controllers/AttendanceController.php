<?php

namespace App\Controllers;

use Exception;
use App\Models\Employee;
use App\Models\Attendance;

class AttendanceController
{
    private Employee $employeeModel;
    private Attendance $attendanceModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->attendanceModel = new Attendance();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'];

        $employeeAttendance = $this->employeeModel->getByUserId($userId);

        $attendanceHistory = $this->attendanceModel->getAttendance(
            $employeeAttendance['id']
        );

        $title = "Employee Attendance";
        $content = __DIR__ . '/../views/employee-portal/attendance/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}