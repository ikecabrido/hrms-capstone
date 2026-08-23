<?php

namespace App\Middleware;

class SessionTimeout
{
    private const TIMEOUT = 1800; // 30 minutes


    public static function check(): void
    {
        // User is not logged in
        if (empty($_SESSION['user_id'])) {
            return;
        }

        $now = time();

        // First request after login
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = $now;
            return;
        }

        // Check if inactive for 10 minutes
        if (($now - $_SESSION['last_activity']) >= self::TIMEOUT) {

            // Destroy session
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

            // Redirect to login
            header(
                'Location: /hrms-capstone/modules/portal/index.php?url=auth-index&timeout=1'
            );

            exit;
        }

        // Update activity timestamp
        $_SESSION['last_activity'] = $now;
    }
}