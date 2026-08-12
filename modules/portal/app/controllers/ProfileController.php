<?php

namespace App\Controllers;

use App\Models\Users;
use App\Models\Employee;

class ProfileController
{
    private Users $userModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->userModel = new Users();
        $this->employeeModel = new Employee();
    }
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header(
                'Location: /hrms-capstone/modules/portal/index.php?url=auth-index'
            );
            exit;
        }

        $user = $this->userModel->findById($userId);
        $employeeProfileInfo = $this->employeeModel->findByUserId($userId);

        $title = "Employee Profile";

        $content = __DIR__ . '/../views/employee-portal/profile/content.php';
        require __DIR__ . '/../views/employee-portal/index.php';
    }
}
