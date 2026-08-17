<?php
/**
 * Compatibility wrapper for Time module auth checks.
 * This module now uses the single app-wide session and auth flow from auth/session.php.
 */

class AuthController
{
    public static function isAuthenticated()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['employee_id']) && !empty($_SESSION['role']);
    }

    public static function hasRole($role)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['role']) && (string) $_SESSION['role'] === (string) $role;
    }

    public static function getCurrentUserId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['employee_id'] ?? null;
    }

    public static function getCurrentRole()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['role'] ?? null;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header('Location: /auth/login.php');
        exit();
    }
}
