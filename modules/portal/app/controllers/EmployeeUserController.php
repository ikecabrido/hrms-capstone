<?php

namespace App\Controllers;

use PDOException;
use App\Core\Session;
use App\Helper\Helper;
use App\Models\Employee;
use App\Models\UserAccount;

class EmployeeUserController
{
    private Employee $employeeModel;
    private UserAccount $userAccountModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->userAccountModel = new UserAccount();
    }
    public function index()
    {
        $employeeWithoutAccount = $this->employeeModel->getWithoutUserAccount();

        $title = "Manage User Account";
        $content = __DIR__ . '/../views/admin-portal/user/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function viewAllEmployees()
    {
        $employeesList = $this->userAccountModel->getAllEmployees();

        $title = "View Employees";
        $content = __DIR__ . '/../views/admin-portal/employee/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
    public function storeEmployees()
    {
        try {
            $employeeId = (int) ($_POST['employee_id'] ?? 0);
            $role = trim($_POST['role'] ?? 'employee');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirmation = $_POST['password_confirmation'] ?? '';

            if (!$employeeId || !$username || !$email || !$password || !$passwordConfirmation) {
                Session::set('error', 'Please complete all required fields.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Session::set('error', 'Invalid email address.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if (strlen($password) < 8) {
                Session::set('error', 'Password must be at least 8 characters.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if ($password !== $passwordConfirmation) {
                Session::set('error', 'Passwords do not match.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if ($this->userAccountModel->getByUsername($username)) {
                Session::set('error', 'Username is already taken.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if ($this->userAccountModel->getByEmail($email)) {
                Session::set('error', 'Email is already registered.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            $employee = $this->employeeModel->getByEmployeeId($employeeId);

            if (!$employee) {
                Session::set('error', 'Employee not found.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if (!empty($employee['user_id'])) {
                Session::set('error', 'This employee already has an account.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            $data = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role
            ];

            $userId = $this->userAccountModel->create($data);

            if (!$userId) {
                Session::set('error', 'Failed to create employee account.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            if (!$this->employeeModel->updateUserId($employeeId, $userId)) {
                Session::set('error', 'Account created, but employee linking failed.');
                Helper::redirect('index.php?url=user-account');
                return;
            }

            Session::set('success', 'Employee account created successfully.');

            Helper::redirect('index.php?url=user-account');

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
    public function viewAllAttendance()
    {
        $attendanceList = $this->userAccountModel->getAllAttendance();

        $title = "View Attendance";
        $content = __DIR__ . '/../views/admin-portal/attendance/content.php';

        require __DIR__ . '/../views/admin-portal/index.php';
    }
}