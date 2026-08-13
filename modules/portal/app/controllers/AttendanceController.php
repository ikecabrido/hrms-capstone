<?php

namespace App\Controllers;

use Exception;
use App\Models\Attendance;

class AttendanceController
{
    private Attendance $attendanceModel;

    public function __construct()
    {
        $this->attendanceModel = new Attendance();
    }
    public function index()
    {
        $title = "Employee Attendance";
        $content = __DIR__ . '/../views/employee-portal/attendance/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}