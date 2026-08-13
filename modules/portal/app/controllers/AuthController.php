<?php

namespace App\Controllers;

use Exception;
use App\Core\Session;
use App\Helper\Helper;
use App\Models\Employee;
use App\Helper\LoginHelper;
use PHPMailer\PHPMailer\PHPMailer;
use App\Services\LoginValidationService;

class AuthController
{
    // private AuditLog $auditLog;
    private Employee $employeeModel;
    public function __construct()
    {
        $this->employeeModel = new Employee();
    }
    public function index()
    {
        $title = "Login";
        $content = __DIR__ . '/../views/auth/login.php';
        require __DIR__ . '/../views/index.php';
    }
    public function adminIndex()
    {
        $title = "Login";
        $content = __DIR__ . '/../views/auth/admin-login.php';
        require __DIR__ . '/../views/index.php';
    }
    public function login(): void
    {
        Session::start();

        try {

            // Check if login is currently locked
            LoginHelper::checkRateLimit();

            $employeeId = Helper::sanitize(
                $_POST['employee_id'] ?? ''
            );

            $password = trim(
                $_POST['password'] ?? ''
            );

            $employee = $this->employeeModel
                ->getByEmployeeNum($employeeId);

            $validationService = new LoginValidationService();

            $validationService->validate(
                $employeeId,
                $password,
                $employee
            );

            // Login successful
            LoginHelper::resetAttempts();

            LoginHelper::setAuthenticatedUser([
                'id' => $employee['user_id'],
                'username' => $employee['username'],
                'role' => $employee['role'],
                'is_admin' => $employee['is_admin'],
            ]);

            Helper::redirect(
                'index.php?url=employee-dashboard'
            );

        } catch (Exception $e) {

            $this->handleLoginFailure($e);

            Helper::redirect(
                'index.php?url=auth-index'
            );

            exit;
        }
    }
    public function adminLogin(): void
    {
        Session::start();
        try {

            // Check login rate limit
            LoginHelper::checkRateLimit();

            $employeeId = Helper::sanitize(
                $_POST['employee_id'] ?? ''
            );

            $password = trim(
                $_POST['password'] ?? ''
            );

            $employee = $this->employeeModel
                ->getByEmployeeNum($employeeId);

            $validationService = new LoginValidationService();

            $validationService->validate(
                $employeeId,
                $password,
                $employee
            );

            if ((int) ($employee['is_admin'] ?? 0) !== 1) {

                throw new Exception(
                    'You are not authorized to access the administrator portal.'
                );
            }
            LoginHelper::resetAttempts();

            LoginHelper::setAuthenticatedUser([
                'id' => $employee['user_id'],
                'username' => $employee['username'],
                'role' => $employee['role'],
                'is_admin' => $employee['is_admin'],
            ]);

            Helper::redirect(
                'index.php?url=admin-dashboard'
            );

        } catch (Exception $e) {

            $this->handleLoginFailure($e);

            Helper::redirect(
                'index.php?url=admin'
            );

            exit;
        }
    }
    public function logout()
    {
        // Start session if it has not been started yet
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Remove all session data
        $_SESSION = [];

        // Remove the session cookie
        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session
        session_destroy();

        // Redirect to login page
        header('Location: index.php?url=auth-index');
        exit;
    }
    private function handleLoginFailure(Exception $e): void
    {
        $lockedUntil = (int) Session::get(
            'login_locked_until'
        );

        // Already locked
        if ($lockedUntil > time()) {

            Session::set(
                'error',
                'Too many failed login attempts.'
            );

            return;
        }

        // Record failed attempt
        $justLocked = LoginHelper::recordFailedAttempt();

        // User reached 3 attempts
        if ($justLocked) {

            Session::set(
                'error',
                'Too many failed login attempts.'
            );

            return;
        }

        // Still has attempts
        $attempts = LoginHelper::getAttempts();

        $remainingAttempts =
            LoginHelper::MAX_ATTEMPTS - $attempts;

        Session::set(
            'error',
            $e->getMessage() .
            " You have {$remainingAttempts} attempt(s) remaining."
        );
    }
}
