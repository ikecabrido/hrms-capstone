<?php 

namespace App\Controllers;

use App\Models\Users;

class AttendanceController
{
    public function index()
    {
        $title = "Attendance Page";
        $content = __DIR__ . '/../views/employee-portal/attendance/main.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}