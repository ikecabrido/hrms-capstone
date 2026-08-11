<?php

namespace App\Core;

class Auth
{
    /**
     * Check whether the current user is authenticated.
     */
    public static function isAuthenticated(): bool
    {
        Session::start();

        // Check the normalized session first
        if (Session::get('user_id') !== null) {
            return true;
        }

        // Backward compatibility with the old user session structure
        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            isset($_SESSION['user']['id'])
        ) {
            $user = $_SESSION['user'];

            Session::set('user_id', $user['id']);
            Session::set('username', $user['username'] ?? null);
            Session::set('role', $user['role'] ?? null);
            Session::set('full_name', $user['name'] ?? null);

            return true;
        }

        return false;
    }

    /**
     * Require the user to be authenticated.
     */
    public static function requireAuth(
        string $redirect = 'index.php?url=auth-index'
    ): void {
        if (!self::isAuthenticated()) {
            header("Location: {$redirect}");
            exit;
        }
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void
    {
        Session::start();

        $_SESSION = [];

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

        session_destroy();

        header('Location: index.php?url=auth-index');
        exit;
    }

    /**
     * Get the authenticated user's ID.
     */
    public static function userId(): ?int
    {
        $userId = Session::get('user_id');

        return $userId !== null ? (int) $userId : null;
    }

    /**
     * Get the authenticated user's role.
     */
    public static function role(): ?string
    {
        $role = Session::get('role');

        return $role !== null ? (string) $role : null;
    }

    /**
     * Check whether the authenticated user has a specific role.
     */
    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }
}
