<?php

namespace App\Helper;

use Exception;
use App\Core\Session;

class LoginHelper
{
    public const MAX_ATTEMPTS = 3;
    public const LOCKOUT_SECONDS = 60;
    public static function checkRateLimit(): void
    {
        $lockedUntil = (int) Session::get(
            'login_locked_until'
        );

        if ($lockedUntil <= 0) {
            return;
        }

        if (time() >= $lockedUntil) {
            self::resetAttempts();
            return;
        }

        $remaining = $lockedUntil - time();

        throw new Exception(
            "Too many failed login attempts. Please wait {$remaining} seconds."
        );
    }
    public static function recordFailedAttempt(): bool
    {
        $attempts = (int) Session::get('login_attempts');

        $attempts++;

        Session::set(
            'login_attempts',
            $attempts
        );

        if ($attempts >= self::MAX_ATTEMPTS) {

            Session::set(
                'login_locked_until',
                time() + self::LOCKOUT_SECONDS
            );

            return true;
        }

        return false;
    }
    public static function getAttempts(): int
    {
        return (int) Session::get(
            'login_attempts'
        );
    }
    public static function resetAttempts(): void
    {
        Session::set('login_attempts', 0);

        Session::set(
            'login_locked_until',
            0
        );
    }
    public static function setAuthenticatedUser(array $user): void
    {
        Session::set('user_id', (int) $user['id']);
        Session::set('username', $user['username']);
        Session::set('role', $user['role']);
        Session::set('is_admin', (int) $user['is_admin']);

        Session::set('success', 'Login successful!');
    }
}
