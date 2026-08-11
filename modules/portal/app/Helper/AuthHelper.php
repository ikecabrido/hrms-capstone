<?php

namespace App\Core;

use App\Models\Employee;

class AuthHelper
{
    private Employee $employeeModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
    }

    /**
     * Get employee information for the given user.
     */
    public function checkUserEmployee(int $userId): array
    {
        if (!$userId) {
            die('User not logged in.');
        }

        $employee = $this->employeeModel->findByUserId($userId);

        if (!$employee) {
            Session::set('error', 'Employee data not found');

            header('Location: index.php?url=auth-index');
            exit;
        }

        return $employee;
    }
}
