<?php
/**
 * CSRF — Token generation and validation for state-changing requests.
 * Use on every POST/PUT/DELETE AJAX call.
 */
class CSRF
{
    /**
     * Generate a CSRF token and store it in the session.
     * Call once per session (e.g. on login or first page load).
     */
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Get the current CSRF token from session (generates if missing).
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            return self::generate();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a submitted token against the session token.
     * Returns true if valid, false otherwise.
     */
    public static function validate(?string $submittedToken): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submittedToken);
    }

    /**
     * Quick check — validates and exits with 403 if invalid.
     * Call at the top of every state-changing AJAX endpoint.
     */
    public static function requireValid(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Accept from header, POST body, or query string
        $token = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? $_POST['csrf_token']
            ?? $_GET['csrf_token']
            ?? '';

        if (!self::validate($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }

    /**
     * Output a hidden input field with the CSRF token (for HTML forms).
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::getToken()) . '">';
    }
}
