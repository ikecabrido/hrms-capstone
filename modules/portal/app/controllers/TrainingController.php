<?php

namespace App\Controllers;

use App\Models\Training;

class TrainingController
{
    private Training $trainingModel;

    public function __construct()
    {
        $this->trainingModel = new Training();
    }

    public function index()
    {
        $allTrainingCourses = $this->trainingModel->allCourse();

        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/employee-portal/training/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
    public function adminIndex()
    {
        $allTrainingCourses = $this->trainingModel->allCourse();

        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/admin-portal/training/content.php';
        require __DIR__ . '/../views/admin-portal/index.php';
    }
}