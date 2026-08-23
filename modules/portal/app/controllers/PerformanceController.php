<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Performance;

class PerformanceController
{
    private $employeeModel;
    private $performanceModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->performanceModel = new Performance();
    }

    public function index()
    {
        $userId = $_SESSION['user_id'];
        $employee = $this->employeeModel->getByUserId($userId);
        $employeePerformanceFeedback = $this->performanceModel->getPerformance($employee['id']);

        $title = "Performance Evaluation";
        $content = __DIR__ . '/../views/employee-portal/performance/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}