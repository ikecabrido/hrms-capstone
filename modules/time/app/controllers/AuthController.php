<?php
/**
 * Authentication Controller for Time & Attendance System
 * Handles login, logout, and session management
 */

require_once __DIR__ . '/../models/Users.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../helpers/Helper.php';
require_once __DIR__ . '/../helpers/AuditLog.php';

class AuthController
{
    private $userModel;
    private $auditLog;

    public function __construct()
    {
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
    }

    /**
     * Handle user login
     * 
     * @param string $username - Username
     * @param string $password - Password
     * @return string - Empty string on success, error message on failure
     */
    public function login($username, $password)
    {
        Session::start();

        // Sanitize input
        $username = Helper::sanitize($username);
        $password = trim($password);

        // Find user in database
        $user = $this->userModel->login($username);

        if (!$user) {
            $this->auditLog->log('LOGIN_FAILED', null, null, null, 
                ['reason' => 'Invalid username'], 'FAILED', 'User not found');
            return "Invalid username or password";
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->auditLog->log('LOGIN_FAILED', $user['user_id'], null, null, 
                ['reason' => 'Wrong password'], 'FAILED', 'Password mismatch');
            return "Invalid username or password";
        }

        // Check if account is active
        if ($user['status'] !== 'ACTIVE') {
            $this->auditLog->log('LOGIN_FAILED', $user['user_id'], null, null, 
                ['reason' => 'Account not active'], 'FAILED', 'Account status: ' . $user['status']);
            return "Account not yet approved by HR";
        }

        // Set session data
        Session::set('user_id', $user['user_id']);
        Session::set('role', $user['role']);
        Session::set('username', $user['username']);

        // Log successful login
        $this->auditLog->log('LOGIN_SUCCESS', $user['user_id'], null, null, 
            ['role' => $user['role']], 'SUCCESS');

        // Redirect based on role
        if ($user['role'] === 'HR_ADMIN') {
            Helper::redirect('dashboard.php');
        } else {
            Helper::redirect('employee_dashboard.php');
        }

        return "";
    }

    /**
     * Handle user logout
     */
    public function logout()
    {
        Session::start();
        $user_id = Session::get('user_id');
        
        if ($user_id) {
            $this->auditLog->log('LOGOUT', $user_id, null, null, [], 'SUCCESS');
        }

        Session::destroy();
        Helper::redirect('index.php');
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated()
    {
        Session::start();
        
        // Check time_attendance session format
        if (!is_null(Session::get('user_id'))) {
            return true;
        }
        
        // Check global login session format
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            // Auto-sync global session to time_attendance format for compatibility
            $userId = $_SESSION['user']['id'];
            Session::set('user_id', $userId);
            Session::set('email', $_SESSION['user']['username']);
            
            // Keep the role as-is from global session
            $role = $_SESSION['user']['role'];
            Session::set('role', $role);
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if user has specific role
     * 
     * @param string $role - Role to check
     * @return bool
     */
    public static function hasRole($role)
    {
        Session::start();
        
        // Check time_attendance session format
        $sessionRole = Session::get('role');
        if ($sessionRole === $role) {
            return true;
        }
        
        // Check global login session format
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role'])) {
            return $_SESSION['user']['role'] === $role;
        }
        
        return false;
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public static function getCurrentUserId()
    {
        Session::start();
        
        // Check time_attendance session format
        $userId = Session::get('user_id');
        if (!is_null($userId)) {
            return $userId;
        }
        
        // Check global login session format
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'];
        }
        
        return null;
    }

    /**
     * Get current user role
     * 
     * @return string|null
     */
    public static function getCurrentRole()
    {
        Session::start();
        
        // Check time_attendance session format
        $role = Session::get('role');
        if (!is_null($role)) {
            return $role;
        }
        
        // Check global login session format
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role'])) {
            return $_SESSION['user']['role'];
        }
        
        return null;
    }
}
