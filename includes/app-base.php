<?php
/**
 * AppBase — the one place the application base path is worked out.
 *
 * Absolute URLs used to name one specific checkout directory, so a sign-out link, a
 * redirect or a share URL pointed at the wrong place as soon as the app was deployed
 * under a different folder name — or broke outright when the folder was renamed.
 * Nothing here is configured: the base is derived from the request and the filesystem,
 * so it is correct whatever the folder is called and wherever it sits.
 *
 * Works from any entry point:
 *   - a page rendered by a module router (index.php?page=…),
 *   - a script the web server executes directly (errors/403.php, print pages),
 *   - a cron CLI run, where there is no request and the web path is empty.
 *
 * Typical use:
 *   header('Location: ' . AppBase::pathFor('modules/learning/index.php'));
 *   <a href="<?= htmlspecialchars(AppBase::pathFor('auth/logout.php')) ?>">
 *   $shareUrl = AppBase::urlFor('modules/learning/index.php') . '?page=…';
 */
final class AppBase
{
    /** Path of the application root as the browser sees it; null until derived. */
    private static ?string $webPath = null;

    /**
     * Application root on disk — the directory holding modules/, auth/ and database/.
     * This file lives in <root>/includes/, so the root is one level up.
     */
    public static function root(): string
    {
        $root = realpath(dirname(__DIR__));

        return $root !== false ? $root : dirname(__DIR__);
    }

    /**
     * Root-relative base path of the application: "/hrms-capstone" when the app sits
     * in a subdirectory, "" when it is served from the document root.
     */
    public static function webPath(): string
    {
        if (self::$webPath !== null) {
            return self::$webPath;
        }

        $webPath = '';

        // Preferred source: the filesystem root of the site. The app root is
        // <document root>/<possibly nested folders>, and the URL path mirrors that.
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== ''
            ? realpath($_SERVER['DOCUMENT_ROOT'])
            : false;
        $root = self::root();

        if ($documentRoot !== false && str_starts_with($root, $documentRoot)) {
            $webPath = rtrim(str_replace('\\', '/', substr($root, strlen($documentRoot))), '/');
        } elseif (!empty($_SERVER['SCRIPT_NAME']) && ($pos = strpos($_SERVER['SCRIPT_NAME'], '/modules/')) !== false) {
            // Fallback for servers whose DOCUMENT_ROOT is unusable or symlinked away:
            // every entry script in this app lives under modules/<name>/.
            $webPath = rtrim(substr($_SERVER['SCRIPT_NAME'], 0, $pos), '/');
        }

        return self::$webPath = $webPath;
    }

    /**
     * Request origin, e.g. "http://localhost" — "" when there is no request, which
     * is the case for cron and other CLI runs (no HTTP_HOST is set there).
     */
    public static function origin(): string
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return '';
        }

        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        return ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    }

    /**
     * Absolute base URL of the application, without a trailing slash —
     * e.g. "http://localhost/hrms-capstone", or "" without a request.
     */
    public static function url(): string
    {
        return self::origin() . self::webPath();
    }

    /**
     * Root-relative path for something inside the application, for links and
     * Location headers, e.g. pathFor('auth/logout.php') => "/hrms-capstone/auth/logout.php".
     */
    public static function pathFor(string $relative): string
    {
        return self::webPath() . '/' . ltrim($relative, '/');
    }

    /**
     * Absolute URL of something inside the application, for share links and emails,
     * e.g. urlFor('modules/learning/index.php') => "http://localhost/hrms-capstone/modules/learning/index.php".
     */
    public static function urlFor(string $relative): string
    {
        return self::url() . '/' . ltrim($relative, '/');
    }
}
