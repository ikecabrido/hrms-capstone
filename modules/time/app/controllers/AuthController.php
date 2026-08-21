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

        $requestedRole = strtolower(trim((string) $role));
        $sessionRole = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? null;
        $sessionRoleName = $_SESSION['role_name'] ?? $_SESSION['user']['role_name'] ?? null;

        $matches = function ($candidate) use ($requestedRole) {
            if ($candidate === null) {
                return false;
            }

            $normalized = strtolower(trim((string) $candidate));
            return $normalized === $requestedRole
                || $normalized === str_replace('_', '', $requestedRole)
                || str_replace('_', '', $normalized) === str_replace('_', '', $requestedRole);
        };

        if ($matches($sessionRole) || $matches($sessionRoleName)) {
            return true;
        }

        // Compat layer for the app-wide HRMS login session, where role IDs are stored as ints.
        $roleIdMap = [
            'time' => ['4'],
            'hr' => ['2', '3', '7'],
            'employee' => ['2'],
        ];

        $roleAliases = $roleIdMap[$requestedRole] ?? [];
        $sessionRoleValue = (string) ($sessionRole ?? '');
        return in_array($sessionRoleValue, $roleAliases, true);
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
