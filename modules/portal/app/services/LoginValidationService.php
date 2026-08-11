<?php

namespace App\Services;

use Exception;

class LoginValidationService
{
    public function validate($employeeId, $password, $employee)
    {
        if ($employeeId === '' || $password === '') {
            throw new Exception('Please fill in all fields.');
        }

        if (
            !$employee ||
            empty($employee['user_id']) ||
            !password_verify($password, $employee['password'])
        ) {
            throw new Exception('Invalid credentials.');
        }

        if ((int) ($employee['is_active'] ?? 1) !== 1) {
            throw new Exception(
                'Your account has been deactivated. Please contact the HR Administrator.'
            );
        }

        return true;
    }
}