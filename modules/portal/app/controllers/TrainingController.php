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



        $title = "Training, Learning and Development";
        $content = __DIR__ . '/../views/employee-portal/training/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}