<?php

namespace App\Controllers;

use App\Models\Enrollment;

class EnrollmentController
{
    private Enrollment $enrollmentModel;

    public function __construct()
    {
        $this->enrollmentModel = new Enrollment();
    }

    public function enroll()
    {
        
    }
}