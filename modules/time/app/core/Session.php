<?php
/**
 * Compatibility helper for the old Time module session wrapper.
 * All auth/session state now comes from the single root auth/session.php flow.
 */

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    public static function destroy()
    {
        self::start();
        session_unset();
        session_destroy();
    }
}
