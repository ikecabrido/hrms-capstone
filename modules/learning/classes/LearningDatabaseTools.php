<?php
require_once dirname(__DIR__, 3) . '/database/db.php';

/**
 * Dedicated connection for the admin database export/import tools.
 *
 * The application normally connects with its own account, but schema imports
 * need an account that can create, alter and drop Learning tables. Keep those
 * credentials separate from the rest of the application and try them first.
 *
 * Deployments can override the values below with LD_IMPORT_DB_HOST,
 * LD_IMPORT_DB_PORT, LD_IMPORT_DB_NAME, LD_IMPORT_DB_USER and
 * LD_IMPORT_DB_PASSWORD environment variables.
 */
final class LearningDatabaseTools
{
    private const DEFAULTS = [
        'host'     => 'localhost',
        'port'     => '3306',
        'database' => 'payr_bcp',
        'username' => 'payr_bestlink',
        'password' => '12345',
    ];

    /** @var PDO|null */
    private static $connection = null;

    /** @var string */
    private static $source = '';

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = self::config();

        try {
            self::$connection = self::connect($config);
            self::$source = 'import credentials';
            return self::$connection;
        } catch (Throwable $e) {
            // A missing development account should not make the database tools
            // unusable when the application is already connected to the same
            // database. Never fall back to a different database, though: that
            // would import rows into the wrong schema.
            $fallback = (new Database())->getConnection();
            $fallbackDatabase = (string) $fallback->query('SELECT DATABASE()')->fetchColumn();

            if ($fallbackDatabase !== $config['database']) {
                throw new RuntimeException(
                    'The import database "' . $config['database'] . '" is unavailable, and the application is connected to "'
                    . $fallbackDatabase . '". Refusing to import into a different database.',
                    0,
                    $e
                );
            }

            error_log('[learning] Import database credentials are unavailable; using the application connection for '
                . $config['database'] . '.');
            self::$connection = $fallback;
            self::$source = 'application connection fallback';
            return self::$connection;
        }
    }

    /** The database the export/import tools are configured to target. */
    public static function databaseName(): string
    {
        return (string) self::config()['database'];
    }

    /** Where the active connection came from, for diagnostics. */
    public static function source(): string
    {
        self::connection();
        return self::$source;
    }

    private static function connect(array $config): PDO
    {
        $dsn = 'mysql:host=' . $config['host']
            . ';port=' . $config['port']
            . ';dbname=' . $config['database']
            . ';charset=utf8mb4';

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }

    private static function config(): array
    {
        $defaults = self::DEFAULTS;

        return [
            'host'     => (string) (getenv('LD_IMPORT_DB_HOST') ?: $defaults['host']),
            'port'     => (string) (getenv('LD_IMPORT_DB_PORT') ?: $defaults['port']),
            'database' => (string) (getenv('LD_IMPORT_DB_NAME') ?: $defaults['database']),
            'username' => (string) (getenv('LD_IMPORT_DB_USER') ?: $defaults['username']),
            'password' => (string) (getenv('LD_IMPORT_DB_PASSWORD') ?: $defaults['password']),
        ];
    }
}
