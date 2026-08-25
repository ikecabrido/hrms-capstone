<?php

namespace App\Controllers;

use Exception;
use App\Core\Session;
use App\Helper\Helper;
use App\Models\Employee;
use App\Helper\LoginHelper;
use App\Controllers\AuthController;
use App\Services\LoginValidationService;

use App\Models\GetEmployees;


class SendEmployeesController
{
    private GetEmployees $getEmployeesModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->getEmployeesModel = new GetEmployees();
        $this->employeeModel = new Employee();
    }

    public function getAll()
    {
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'data' => $this->getEmployeesModel->sendAll()
        ]);
        exit;
    }
    public function login(): void
    {
        Session::start();

        header('Content-Type: application/json');

        try {
            LoginHelper::checkRateLimit();

            // 1. Read raw JSON payload from incoming request body
            $rawInput = file_get_contents('php://input');
            $jsonInput = json_decode($rawInput, true) ?? [];

            // 2. Extract employee identifier (supports 'employee_id' or 'username' in JSON or $_POST)
            $rawEmployeeCode = $jsonInput['employee_id']
                ?? $jsonInput['username']
                ?? $_POST['employee_id']
                ?? $_POST['username']
                ?? '';

            $employeeCode = Helper::sanitize($rawEmployeeCode);

            // 3. Extract password (supports JSON body or $_POST)
            $password = trim(
                $jsonInput['password'] ?? $_POST['password'] ?? ''
            );

            // 4. Fetch employee record from database
            $employee = $this->employeeModel->getByEmployeeNum($employeeCode);

            // 5. Validate input presence, existence, and password hash
            $validationService = new LoginValidationService();
            $validationService->validate(
                $employeeCode,
                $password,
                $employee
            );

            // 6. Reset rate limiting on successful validation
            LoginHelper::resetAttempts();

            // 7. Store authenticated session state
            LoginHelper::setAuthenticatedUser([
                'id' => $employee['user_id'],
                'username' => $employee['username'],
                'role' => $employee['role'],
                'is_admin' => $employee['is_admin'],
            ]);

            // 8. Return HTTP 200 Success Response
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'id' => $employee['user_id'],
                    'employee_name' => $employee['employee_name'],
                    'username' => $employee['username'],
                    'role' => $employee['role'],
                    'is_admin' => $employee['is_admin'],
                ]
            ]);

        } catch (Exception $e) {
            // Return HTTP 401 Unauthorized Response on validation or auth failure
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }
}