<?php

namespace App\Controllers;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class ResetPasswordController
{
    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->adminLoginModel = new AdminLogin();
    }
    public function index()
    {

    }
}
