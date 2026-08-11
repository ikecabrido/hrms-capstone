<?php

namespace App\Helper;

class SessionHelper
{
    private const TIMEOUT = 900;

    public static function checkTimeout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) >= self::TIMEOUT
        ) {
            $isAdmin = !empty($_SESSION['user']['is_admin']);

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

            Helper::redirect(
                $isAdmin
                    ? 'index.php?url=auth-admin-logout'
                    : 'index.php?url=auth-logout'
            );
        }

        $_SESSION['last_activity'] = time();
    }
}
