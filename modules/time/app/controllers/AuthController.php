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

        $hasEmployee = !empty($_SESSION['employee_id']);
        $hasRoleValue = !empty($_SESSION['role']) || !empty($_SESSION['role_name']) || !empty($_SESSION['role_id']);

        return $hasEmployee && $hasRoleValue;
    }

    public static function hasRole($role)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestedRole = strtolower(trim((string) $role));
        $sessionRole = $_SESSION['role'] ?? $_SESSION['user']['role'] ?? null;
        $sessionRoleName = $_SESSION['role_name'] ?? $_SESSION['user']['role_name'] ?? null;
        $sessionRoleId = $_SESSION['role_id'] ?? $_SESSION['user']['role_id'] ?? null;

        $normalize = function ($value) {
            if ($value === null) {
                return '';
            }

            return preg_replace('/[^a-z0-9]+/i', '', strtolower(trim((string) $value)));
        };

        $requestedNormalized = $normalize($requestedRole);
        $candidateValues = [
            $sessionRole,
            $sessionRoleName,
            $sessionRoleId,
        ];

        foreach ($candidateValues as $candidate) {
            if ($normalize($candidate) === $requestedNormalized) {
                return true;
            }
        }

        // Compat layer for the app-wide HRMS login session, where role IDs are stored as ints.
        $roleIdMap = [
            'time' => ['4', '5'],
            'hr' => ['2', '3', '7'],
            'exit' => ['10'],
            'employee' => ['2'],
        ];

        $roleAliases = $roleIdMap[$requestedRole] ?? [];
        foreach ($candidateValues as $candidate) {
            $sessionRoleValue = (string) $candidate;
            if (in_array($sessionRoleValue, $roleAliases, true)) {
                return true;
            }
        }

        // Additional aliases for legacy role names like "HR Staff" or "Time Management".
        $roleNameAliases = [
            'time' => ['time', 'timemanagement', 'attendance', 'timekeeping', 'timekeeper', 'timezone'],
            'hr' => ['hr', 'hrstaff', 'hrofficer', 'humanresources', 'hradmin'],
        ];

        $nameAliases = $roleNameAliases[$requestedRole] ?? [];
        foreach ($candidateValues as $candidate) {
            $normalizedCandidate = $normalize($candidate);
            if (in_array($normalizedCandidate, $nameAliases, true)) {
                return true;
            }
        }

        return false;
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
