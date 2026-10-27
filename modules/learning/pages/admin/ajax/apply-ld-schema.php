<?php
/**
 * Apply a .sql file to the Learning schema.
 *
 * This is the structural counterpart to import-ld-data.php: that endpoint only
 * runs INSERT statements against tables that already exist, whereas this one
 * runs the statements that create, change and drop the tables themselves.
 *
 * Because DDL cannot be rolled back in MySQL/MariaDB, the file is parsed and
 * authorised in full before the first statement runs, and every statement must
 * belong to an ld_ table. Anything that removes or rewrites existing structure
 * (DROP TABLE, DROP INDEX, RENAME TABLE, ALTER ... DROP/MODIFY/CHANGE/RENAME)
 * additionally requires an explicit confirmation from the caller.
 *
 * Two modes, mirroring the data import:
 *   validate — parse, authorise and syntax-check, changing nothing
 *   apply    — do the above, then execute statement by statement
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningRole.php';
require_once dirname(__FILE__, 4) . '/classes/LearningDatabaseTools.php';
require_once dirname(__FILE__, 4) . '/classes/CSRF.php';
require_once dirname(__FILE__, 4) . '/classes/dberror.php';

function ldSchemaResponse(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

/** The action names, and whether they attack existing structure. */
function ldSchemaActionLabels(): array
{
    return [
        'create_table'    => 'Create table',
        'alter_table'     => 'Alter table',
        'rename_table'    => 'Rename table',
        'drop_table'      => 'Drop table',
        'create_index'    => 'Create index',
        'drop_index'      => 'Drop index',
        'session_setting' => 'Session setting',
    ];
}

/**
 * Split a script into statements on semicolons that are not inside a string, an
 * identifier or a comment, so a stored `;` cannot smuggle a second statement
 * past the authorisation step.
 *
 * @return array<int, array{sql: string, line: int}>
 */
function ldSchemaSplit(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $position = 0;
    $line = 1;
    $statementLine = 1;

    while ($position < $length) {
        $character = $sql[$position];
        $next = $position + 1 < $length ? $sql[$position + 1] : '';

        // ── Comments ─────────────────────────────────────────────────────────
        if (($character === '-' && $next === '-') || $character === '#') {
            while ($position < $length && $sql[$position] !== "\n") {
                $position++;
            }
            continue;
        }
        if ($character === '/' && $next === '*') {
            $position += 2;
            while ($position < $length && substr($sql, $position, 2) !== '*/') {
                if ($sql[$position] === "\n") {
                    $line++;
                }
                $position++;
            }
            $position += 2;
            continue;
        }

        // ── Quoted strings and identifiers, kept verbatim ─────────────────────
        if ($character === "'" || $character === '"' || $character === '`') {
            $quote = $character;
            $buffer .= $quote;
            $position++;

            while ($position < $length) {
                $current = $sql[$position];

                if ($current === '\\' && $quote !== '`') {
                    $buffer .= $current . ($sql[$position + 1] ?? '');
                    $position += 2;
                    continue;
                }
                if ($current === "\n") {
                    $line++;
                }
                if ($current === $quote) {
                    if (($sql[$position + 1] ?? '') === $quote) {
                        $buffer .= $quote . $quote;
                        $position += 2;
                        continue;
                    }
                    $buffer .= $quote;
                    $position++;
                    break;
                }

                $buffer .= $current;
                $position++;
            }
            continue;
        }

        // ── Statement boundaries ─────────────────────────────────────────────
        if ($character === ';') {
            if (trim($buffer) !== '') {
                $statements[] = ['sql' => trim($buffer), 'line' => $statementLine];
            }
            $buffer = '';
            $position++;
            continue;
        }

        if ($character === "\n") {
            $line++;
            $buffer .= $character;
            $position++;
            continue;
        }
        if (trim($buffer) === '' && ctype_space($character)) {
            $position++;
            continue;
        }
        if ($buffer === '') {
            $statementLine = $line;
        }

        $buffer .= $character;
        $position++;
    }

    if (trim($buffer) !== '') {
        $statements[] = ['sql' => trim($buffer), 'line' => $statementLine];
    }

    return $statements;
}

/**
 * The statement with string literals blanked out, which is what the keyword
 * scans run against. Without it a comment such as DEFAULT 'drop me' would read
 * as a destructive change and a literal holding the word RENAME would be
 * mistaken for a table rename. Backticked identifiers are kept, since the table
 * names are read from those.
 */
function ldSchemaMeaningfulSql(string $sql): string
{
    return (string) preg_replace([
        "/'(?:\\\\.|''|[^'\\\\])*'/s",
        '/"(?:\\\\.|""|[^"\\\\])*"/s',
    ], ' ', $sql);
}

/** A short, single-line rendering of a statement for error messages. */
function ldSchemaExcerpt(string $sql): string
{
    $excerpt = trim((string) preg_replace('/\s+/', ' ', $sql));
    return strlen($excerpt) > 110 ? substr($excerpt, 0, 107) . '...' : $excerpt;
}

/**
 * Read one table reference out of a statement and insist that it is an ld_
 * table in the current database. A qualified name naming another database is
 * refused rather than silently accepted.
 */
function ldSchemaTableName(string $raw, string $database): string
{
    $name = trim(rtrim(trim($raw), ';'));

    if (!preg_match('/^(?:`((?:``|[^`])+)`|([A-Za-z0-9_$]+))(?:\.(?:`((?:``|[^`])+)`|([A-Za-z0-9_$]+)))?$/', $name, $matches)) {
        throw new InvalidArgumentException('Unrecognised table name: ' . ldSchemaExcerpt($name));
    }

    $first = ($matches[1] ?? '') !== '' ? str_replace('``', '`', $matches[1]) : ($matches[2] ?? '');
    $second = ($matches[3] ?? '') !== '' ? str_replace('``', '`', $matches[3]) : ($matches[4] ?? '');

    if ($second !== '') {
        if (strcasecmp($first, $database) !== 0) {
            throw new InvalidArgumentException('Only tables in this database can be changed; "' . ldSchemaExcerpt($name) . '" names another database.');
        }
        $table = $second;
    } else {
        $table = $first;
    }

    if (strlen($table) > 64) {
        throw new InvalidArgumentException('The table name is longer than MySQL allows: ' . ldSchemaExcerpt($table));
    }
    if (stripos($table, 'ld_') !== 0) {
        throw new InvalidArgumentException('Only ld_ tables can be created, changed, renamed or dropped; "' . ldSchemaExcerpt($table) . '" is not one.');
    }

    return $table;
}

/**
 * Decide what one statement would do. Throws InvalidArgumentException with a
 * readable reason for anything outside the allowed grammar, so a rejected file
 * never reaches the database.
 *
 * @return array{action: string, tables: array<int, string>, destructive: bool, skip: bool}
 */
function ldSchemaClassify(string $statement, string $database): array
{
    $sql = trim($statement);

    // Session settings the export writes. FK checks are managed around the run
    // instead, and the connection is already utf8mb4.
    if (preg_match('/^SET\s+(?:NAMES|FOREIGN_KEY_CHECKS)\b/i', $sql)) {
        return ['action' => 'session_setting', 'tables' => [], 'destructive' => false, 'skip' => true];
    }
    if (preg_match('/^SET\b/i', $sql)) {
        throw new InvalidArgumentException('Only SET NAMES and SET FOREIGN_KEY_CHECKS are accepted; the tool manages every other setting.');
    }

    // ── CREATE TABLE ─────────────────────────────────────────────────────────
    if (preg_match('/^CREATE\s+(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([^\s(]+)/i', $sql, $matches)) {
        $table = ldSchemaTableName($matches[1], $database);
        $meaningful = ldSchemaMeaningfulSql($sql);
        if (preg_match('/\bSELECT\b/i', $meaningful)) {
            throw new InvalidArgumentException('CREATE TABLE ... SELECT would copy rows from another table, so it is not accepted here.');
        }
        if (preg_match('/\bLIKE\s+([^\s(;]+)/i', $meaningful, $like)) {
            // The clone source has to be an ld_ table too.
            ldSchemaTableName($like[1], $database);
        }
        return ['action' => 'create_table', 'tables' => [$table], 'destructive' => false, 'skip' => false];
    }

    // ── ALTER TABLE ──────────────────────────────────────────────────────────
    if (preg_match('/^ALTER\s+TABLE\s+([^\s(]+)/i', $sql, $matches)) {
        $table = ldSchemaTableName($matches[1], $database);
        $meaningful = ldSchemaMeaningfulSql($sql);

        // Renaming the table itself has to land on an ld_ name as well. RENAME
        // COLUMN renames a column, so its target is not a table name.
        if (!preg_match('/\bRENAME\s+COLUMN\b/i', $meaningful)
            && preg_match('/\bRENAME\s+(?:TO\s+|AS\s+)?([^\s(;,]+)/i', $meaningful, $rename)) {
            ldSchemaTableName($rename[1], $database);
        }

        return [
            'action'      => 'alter_table',
            'tables'      => [$table],
            'destructive' => (bool) preg_match('/\b(?:DROP|MODIFY|CHANGE|RENAME)\b/i', $meaningful),
            'skip'        => false,
        ];
    }

    // ── RENAME TABLE ─────────────────────────────────────────────────────────
    if (preg_match('/^RENAME\s+TABLE\s+(.+)$/is', $sql, $matches)) {
        $tables = [];
        foreach (preg_split('/\s*,\s*/', trim($matches[1])) as $pair) {
            if (!preg_match('/^([^\s,]+)\s+TO\s+([^\s,]+)$/i', trim($pair), $pairMatch)) {
                throw new InvalidArgumentException('RENAME TABLE must be written as `old_name` TO `new_name`.');
            }
            $tables[] = ldSchemaTableName($pairMatch[1], $database);
            $tables[] = ldSchemaTableName($pairMatch[2], $database);
        }
        return ['action' => 'rename_table', 'tables' => $tables, 'destructive' => true, 'skip' => false];
    }

    // ── DROP TABLE ───────────────────────────────────────────────────────────
    if (preg_match('/^DROP\s+(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+EXISTS\s+)?(.+)$/is', $sql, $matches)) {
        $list = trim((string) preg_replace('/\s+(?:RESTRICT|CASCADE)\s*$/i', '', trim($matches[1])));
        $tables = [];
        foreach (explode(',', $list) as $name) {
            $tables[] = ldSchemaTableName($name, $database);
        }
        return ['action' => 'drop_table', 'tables' => $tables, 'destructive' => true, 'skip' => false];
    }

    // ── Indexes, which are schema too ────────────────────────────────────────
    if (preg_match('/^CREATE\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?INDEX\s+[^\s(]+\s+ON\s+([^\s(]+)/i', $sql, $matches)) {
        return [
            'action'      => 'create_index',
            'tables'      => [ldSchemaTableName($matches[1], $database)],
            'destructive' => false,
            'skip'        => false,
        ];
    }
    if (preg_match('/^DROP\s+INDEX\s+[^\s(]+\s+ON\s+([^\s(]+)/i', $sql, $matches)) {
        return [
            'action'      => 'drop_index',
            'tables'      => [ldSchemaTableName($matches[1], $database)],
            'destructive' => true,
            'skip'        => false,
        ];
    }

    // ── Everything else, with the reason spelled out ─────────────────────────
    if (preg_match('/^TRUNCATE\b/i', $sql)) {
        throw new InvalidArgumentException('TRUNCATE removes rows rather than schema, so it is not accepted.');
    }
    if (preg_match('/^(?:INSERT|UPDATE|DELETE|REPLACE|LOAD|CALL|DO)\b/i', $sql)) {
        throw new InvalidArgumentException('This tool only changes structure. Use Import row data for INSERT statements.');
    }
    if (preg_match('/^(?:CREATE|ALTER|DROP|GRANT|REVOKE|FLUSH|USE|LOCK|UNLOCK)\s+(?:DATABASE|SCHEMA|USER|SERVER|VIEW|EVENT|TRIGGER|FUNCTION|PROCEDURE|TABLESPACE)\b/i', $sql)
        || preg_match('/^USE\b/i', $sql)) {
        throw new InvalidArgumentException('Statements that target a database, a user or an object other than an ld_ table are not accepted.');
    }

    throw new InvalidArgumentException('Unrecognised statement: ' . ldSchemaExcerpt($sql));
}

/** Counts per action plus the distinct tables a plan touches. */
function ldSchemaPlanSummary(array $plan): array
{
    $counts = array_fill_keys(array_keys(ldSchemaActionLabels()), 0);
    $tables = [];

    foreach ($plan as $item) {
        $counts[$item['action']]++;
        foreach ($item['tables'] as $table) {
            $tables[$table] = true;
        }
    }

    return [$counts, array_keys($tables)];
}

/** Plain-language sentence describing what a plan will do. */
function ldSchemaDescribe(array $counts): string
{
    $parts = [];
    $phrases = [
        'create_table'    => ['table created', 'tables created'],
        'alter_table'     => ['table altered', 'tables altered'],
        'rename_table'    => ['table renamed', 'tables renamed'],
        'drop_table'      => ['table dropped', 'tables dropped'],
        'create_index'    => ['index created', 'indexes created'],
        'drop_index'      => ['index dropped', 'indexes dropped'],
    ];

    foreach ($phrases as $action => [$singular, $plural]) {
        if ($counts[$action] > 0) {
            $parts[] = $counts[$action] . ' ' . ($counts[$action] === 1 ? $singular : $plural);
        }
    }

    if ($counts['session_setting'] > 0) {
        $parts[] = $counts['session_setting'] . ' setting statement' . ($counts['session_setting'] === 1 ? '' : 's') . ' ignored';
    }

    return $parts ? implode(', ', $parts) : 'no schema changes';
}

/** @return array<int, string> Names of the given tables that exist right now. */
function ldSchemaExistingTables(PDO $pdo, array $names): array
{
    if (!$names) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($names), '?'));
    $statement = $pdo->prepare("SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
          AND TABLE_NAME IN ({$placeholders})");
    $statement->execute(array_values($names));

    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

// ── Request checks ───────────────────────────────────────────────────────────
if (empty($_SESSION['employee_id'])) {
    ldSchemaResponse(401, ['success' => false, 'message' => 'Your session has expired. Sign in again.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ldSchemaResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

// This endpoint executes DDL, so it is the last place to skip the CSRF check.
$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!CSRF::validate(is_string($submittedToken) ? $submittedToken : '')) {
    ldSchemaResponse(403, ['success' => false, 'message' => 'Your page token expired. Reload the page and try again.']);
}

try {
    $appPdo = (new Database())->getConnection();
} catch (Throwable $e) {
    DbError::json($e, 'admin/apply-ld-schema');
}

if (LearningRole::forEmployee($appPdo, (int) $_SESSION['employee_id']) !== 'admin') {
    ldSchemaResponse(403, ['success' => false, 'message' => 'Only administrators can change the Learning schema.']);
}

try {
    $pdo = LearningDatabaseTools::connection();
} catch (Throwable $e) {
    error_log('Learning schema import could not open its database connection: ' . $e->getMessage());
    ldSchemaResponse(500, [
        'success' => false,
        'message' => 'The import database connection could not be opened. Check the import database credentials.',
    ]);
}

// ── Upload ───────────────────────────────────────────────────────────────────
$file = $_FILES['sql_file'] ?? null;
if (!$file) {
    ldSchemaResponse(400, ['success' => false, 'message' => 'Choose a valid .sql file.']);
}
$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        ldSchemaResponse(413, [
            'success' => false,
            'message' => 'The SQL file exceeds the upload limit configured by the server. Increase PHP upload_max_filesize and post_max_size to accept a larger file.',
        ]);
    }
    ldSchemaResponse(400, ['success' => false, 'message' => 'The SQL file upload failed. Choose the file again and retry.']);
}
if (!is_uploaded_file($file['tmp_name'] ?? '') || ($file['size'] ?? 0) <= 0) {
    ldSchemaResponse(400, ['success' => false, 'message' => 'Choose a valid, non-empty .sql file.']);
}
if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'sql') {
    ldSchemaResponse(415, ['success' => false, 'message' => 'Upload a file with a .sql extension.']);
}

$contents = file_get_contents($file['tmp_name']);
if ($contents === false) {
    ldSchemaResponse(400, ['success' => false, 'message' => 'Unable to read the uploaded file.']);
}

$validateOnly = ($_POST['mode'] ?? 'apply') === 'validate';
$confirmed = (string) ($_POST['confirm'] ?? '') === '1';
$database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

// ── Parse and authorise everything before touching the database ──────────────
$statements = ldSchemaSplit($contents);
if (!$statements) {
    ldSchemaResponse(422, ['success' => false, 'message' => 'No SQL statements were found in the file.']);
}

$plan = [];
foreach ($statements as $statement) {
    try {
        $classified = ldSchemaClassify($statement['sql'], $database);
    } catch (InvalidArgumentException $e) {
        ldSchemaResponse(422, [
            'success' => false,
            'message' => 'Statement ' . (count($plan) + 1) . ' (line ' . $statement['line'] . '): ' . $e->getMessage(),
        ]);
    }

    $plan[] = $classified + [
        'line'  => $statement['line'],
        'sql'   => $statement['sql'],
        'index' => count($plan) + 1,
    ];
}

// A server-side PREPARE parses the statement without running it, which is the
// closest thing MySQL offers to a dry run for DDL. It never resolves tables, so
// a statement aimed at a table this very file creates still passes.
foreach ($plan as $item) {
    if ($item['skip']) {
        continue;
    }

    // One name per statement, so a failed probe can never collide with the next.
    $probeName = 'ld_schema_probe_' . $item['index'];

    try {
        $pdo->prepare('SET @ld_schema_candidate = ?')->execute([$item['sql']]);
        $pdo->exec('PREPARE ' . $probeName . ' FROM @ld_schema_candidate');
        $pdo->exec('DEALLOCATE PREPARE ' . $probeName);
    } catch (Throwable $e) {
        try {
            $pdo->exec('DEALLOCATE PREPARE ' . $probeName);
        } catch (Throwable $ignored) {
            // Nothing was left prepared, which is the point of the call.
        }
        if (stripos($e->getMessage(), 'prepared statement protocol') !== false) {
            // The server cannot parse it ahead of time; it will be parsed when applied.
            continue;
        }
        ldSchemaResponse(422, [
            'success' => false,
            'message' => 'Statement ' . $item['index'] . ' (line ' . $item['line'] . ') has a syntax error: ' . $e->getMessage(),
        ]);
    }
}

[$counts, $tables] = ldSchemaPlanSummary($plan);
$destructive = [];
foreach ($plan as $item) {
    if ($item['destructive']) {
        $destructive = array_merge($destructive, $item['tables']);
    }
}
$destructiveTables = array_values(array_unique($destructive));

// ── Validate: report, change nothing ─────────────────────────────────────────
if ($validateOnly) {
    ldSchemaResponse(200, [
        'success' => true,
        'validated' => true,
        'statements' => count($plan),
        'counts' => $counts,
        'tables' => $tables,
        'destructiveTables' => $destructiveTables,
        'confirmRequired' => $destructiveTables !== [],
        'message' => 'Validation passed: ' . ldSchemaDescribe($counts) . ' in ' . count($plan) . ' statement'
            . (count($plan) === 1 ? '' : 's') . '. No changes were made.'
            . ($destructiveTables ? ' Confirmation is required to apply it.' : ''),
    ]);
}

// ── Guard the irreversible part ──────────────────────────────────────────────
if ($destructiveTables && !$confirmed) {
    ldSchemaResponse(409, [
        'success' => false,
        'needsConfirm' => true,
        'destructiveTables' => $destructiveTables,
        'message' => 'This file drops or rewrites existing structure ('
            . implode(', ', array_slice($destructiveTables, 0, 5))
            . (count($destructiveTables) > 5 ? ', ...' : '')
            . '). Tick the confirmation box, then validate and apply again.',
    ]);
}

// Structure, not rows: these definitions are the only thing that can be handed
// back if a dropped or altered table needs to be rebuilt.
$existingDestructive = ldSchemaExistingTables($pdo, $destructiveTables);
$rollbackSql = '';
foreach ($existingDestructive as $table) {
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
    if ($create && !empty($create[1])) {
        $rollbackSql .= "DROP TABLE IF EXISTS {$quoted};\n{$create[1]};\n\n";
    }
}
if ($rollbackSql !== '') {
    $rollbackSql = "-- Learning table definitions captured before applying a schema file\n-- "
        . date('Y-m-d H:i:s') . "\n-- Structure only: the rows of these tables are NOT included.\n\n" . $rollbackSql;
}

// ── Apply ────────────────────────────────────────────────────────────────────
$log = [];
$applied = 0;
$foreignKeysDisabled = false;
$failure = null;

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $foreignKeysDisabled = true;

    foreach ($plan as $item) {
        if ($item['skip']) {
            $log[] = [
                'index' => $item['index'],
                'line' => $item['line'],
                'action' => $item['action'],
                'label' => ldSchemaActionLabels()[$item['action']],
                'tables' => $item['tables'],
                'status' => 'skipped',
                'message' => 'Ignored: the tool sets this for the run.',
            ];
            continue;
        }

        try {
            $pdo->exec($item['sql']);
        } catch (Throwable $e) {
            $log[] = [
                'index' => $item['index'],
                'line' => $item['line'],
                'action' => $item['action'],
                'label' => ldSchemaActionLabels()[$item['action']],
                'tables' => $item['tables'],
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
            $failure = ['item' => $item, 'error' => $e];
            break;
        }

        $log[] = [
            'index' => $item['index'],
            'line' => $item['line'],
            'action' => $item['action'],
            'label' => ldSchemaActionLabels()[$item['action']],
            'tables' => $item['tables'],
            'status' => 'applied',
            'message' => '',
        ];
        $applied++;
    }
} catch (Throwable $e) {
    $failure = ['item' => null, 'error' => $e];
} finally {
    if ($foreignKeysDisabled) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $restoreError) {
            error_log('Could not restore foreign key checks after a Learning schema change: ' . $restoreError->getMessage());
        }
    }
}

if ($failure !== null) {
    $item = $failure['item'];
    $detail = $item
        ? 'Statement ' . $item['index'] . ' (line ' . $item['line'] . ') failed: ' . $failure['error']->getMessage()
        : 'The change could not be applied: ' . $failure['error']->getMessage();

    ldSchemaResponse(422, [
        'success' => false,
        'log' => $log,
        'applied' => $applied,
        'rollbackSql' => $rollbackSql,
        'message' => $detail . ($applied > 0
            ? ' ' . $applied . ' earlier statement' . ($applied === 1 ? ' was' : 's were')
                . ' already applied — table definitions cannot be rolled back, so check the schema before retrying.'
            : ' No statement was applied.'),
    ]);
}

ldSchemaResponse(200, [
    'success' => true,
    'validated' => false,
    'statements' => count($plan),
    'applied' => $applied,
    'counts' => $counts,
    'tables' => $tables,
    'log' => $log,
    'rollbackSql' => $rollbackSql,
    'message' => 'Applied ' . $applied . ' of ' . count($plan) . ' statements: ' . ldSchemaDescribe($counts) . '.',
]);
