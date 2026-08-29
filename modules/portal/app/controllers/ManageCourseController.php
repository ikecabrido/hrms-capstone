<?php

namespace App\Controllers;

use App\Models\CourseContent;
use Exception;

class ManageCourseController
{
    private CourseContent $courseContentModel;
    public function __construct()
    {
        $this->courseContentModel = new CourseContent();
    }
    public function index()
    {
        $modules = $this->courseContentModel->all();

        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/admin-portal/training/course/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
}