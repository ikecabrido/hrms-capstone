<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningRole.php';

function importResponse(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

function parseExportValueList(string $source): array
{
    $values = [];
    $length = strlen($source);
    $position = 0;

    while ($position < $length) {
        while ($position < $length && ctype_space($source[$position])) {
            $position++;
        }
        if ($position >= $length) {
            throw new InvalidArgumentException('Invalid exported row: a value is missing.');
        }

        if ($source[$position] === "'") {
            $position++;
            $value = '';
            $closed = false;
            while ($position < $length) {
                $character = $source[$position];
                if ($character === "\\") {
                    if ($position + 1 >= $length) {
                        throw new InvalidArgumentException('Invalid exported row: incomplete string escape.');
                    }
                    $position++;
                    $escaped = $source[$position];
                    $escapeMap = [
                        '0' => "\0", 'b' => "\x08", 'n' => "\n", 'r' => "\r",
                        't' => "\t", 'Z' => "\x1a", '\\' => '\\', "'" => "'", '"' => '"',
                    ];
                    $value .= $escapeMap[$escaped] ?? $escaped;
                    $position++;
                    continue;
                }
                if ($character === "'") {
                    if ($position + 1 < $length && $source[$position + 1] === "'") {
                        $value .= "'";
                        $position += 2;
                        continue;
                    }
                    $position++;
                    $closed = true;
                    break;
                }
                $value .= $character;
                $position++;
            }
            if (!$closed) {
                throw new InvalidArgumentException('Invalid exported row: unterminated string.');
            }
            $values[] = $value;
        } else {
            $remaining = substr($source, $position);
            if (preg_match('/^NULL(?=\s*(?:,|$))/i', $remaining, $match)) {
                $values[] = null;
                $position += strlen($match[0]);
            } elseif (preg_match('/^0x([0-9a-f]*)/i', $remaining, $match)) {
                $values[] = hex2bin($match[1]);
                $position += strlen($match[0]);
            } elseif (preg_match('/^-?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?/', $remaining, $match)) {
                $values[] = $match[0];
                $position += strlen($match[0]);
            } else {
                throw new InvalidArgumentException('Unsupported SQL value in exported row.');
            }
        }

        while ($position < $length && ctype_space($source[$position])) {
            $position++;
        }
        if ($position === $length) {
            break;
        }
        if ($source[$position] !== ',') {
            throw new InvalidArgumentException('Invalid exported row: expected a comma between values.');
        }
        $position++;
    }

    return $values;
}

if (empty($_SESSION['employee_id'])) {
    importResponse(401, ['success' => false, 'message' => 'Unauthorized.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    importResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}
if (LearningRole::forEmployee((new Database())->getConnection(), (int) $_SESSION['employee_id']) !== 'admin') {
    importResponse(403, ['success' => false, 'message' => 'Forbidden.']);
}

$file = $_FILES['sql_file'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
    importResponse(400, ['success' => false, 'message' => 'Choose a valid SQL export file.']);
}
if (($file['size'] ?? 0) <= 0 || $file['size'] > 50 * 1024 * 1024) {
    importResponse(413, ['success' => false, 'message' => 'The SQL file must be smaller than 50 MB.']);
}
if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'sql') {
    importResponse(415, ['success' => false, 'message' => 'Upload an .sql file created by the Learning database export.']);
}

$pdo = (new Database())->getConnection();
$validateOnly = ($_POST['mode'] ?? 'import') === 'validate';
$handle = fopen($file['tmp_name'], 'rb');
if ($handle === false) {
    importResponse(400, ['success' => false, 'message' => 'Unable to read the uploaded SQL file.']);
}

$tableColumns = [];
$metadata = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND LEFT(TABLE_NAME, 3) = 'ld_'
    ORDER BY TABLE_NAME, ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
foreach ($metadata as $column) {
    $tableColumns[$column['TABLE_NAME']][] = $column['COLUMN_NAME'];
}

$insertPattern = '/^INSERT INTO `(ld_[A-Za-z0-9_]+)` \((`(?:``|[^`])+`(?:, `(?:``|[^`])+`)*)\) VALUES \((.*)\);$/D';
$importedRows = 0;
$tableCounts = [];
$preparedStatements = [];
$foreignKeysDisabled = false;

try {
    if (!$validateOnly) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $foreignKeysDisabled = true;
        $pdo->beginTransaction();
    }

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (strpos($line, 'INSERT INTO ') !== 0) {
            continue;
        }
        if (!preg_match($insertPattern, $line, $matches)) {
            throw new InvalidArgumentException('The file contains an INSERT statement that is not in the Learning export format.');
        }

        $table = $matches[1];
        if (!isset($tableColumns[$table])) {
            throw new InvalidArgumentException('The export references a table that does not exist in this database: ' . $table);
        }

        preg_match_all('/`((?:``|[^`])+)`/', $matches[2], $columnMatches);
        $columns = array_map(static function ($column) {
            return str_replace('``', '`', $column);
        }, $columnMatches[1]);
        if (!$columns || count($columns) !== count(array_unique($columns))) {
            throw new InvalidArgumentException('The export contains invalid or duplicate column names.');
        }

        $availableColumns = array_flip($tableColumns[$table]);
        foreach ($columns as $column) {
            if (!isset($availableColumns[$column])) {
                throw new InvalidArgumentException('The export references a column that does not exist in ' . $table . ': ' . $column);
            }
        }

        $values = parseExportValueList($matches[3]);
        if (count($values) !== count($columns)) {
            throw new InvalidArgumentException('The export row has a different number of values and columns.');
        }

        if (!$validateOnly) {
            $signature = $table . ':' . implode(',', $columns);
            if (!isset($preparedStatements[$signature])) {
                $quotedTable = '`' . str_replace('`', '``', $table) . '`';
                $quotedColumns = implode(', ', array_map(static function ($column) {
                    return '`' . str_replace('`', '``', $column) . '`';
                }, $columns));
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $preparedStatements[$signature] = $pdo->prepare('INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES (' . $placeholders . ')');
            }
            $preparedStatements[$signature]->execute($values);
        }

        $importedRows++;
        $tableCounts[$table] = ($tableCounts[$table] ?? 0) + 1;
    }

    if ($importedRows === 0) {
        throw new InvalidArgumentException('No Learning table data was found in the uploaded file.');
    }

    if (!$validateOnly) {
        $pdo->commit();
    }
    fclose($handle);
    if ($foreignKeysDisabled) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    importResponse(200, [
        'success' => true,
        'validated' => $validateOnly,
        'rows' => $importedRows,
        'tables' => count($tableCounts),
        'message' => $validateOnly
            ? 'Validation passed: ' . number_format($importedRows) . ' rows across ' . count($tableCounts) . ' tables. No data was changed.'
            : 'Imported ' . number_format($importedRows) . ' rows across ' . count($tableCounts) . ' tables.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($foreignKeysDisabled) {
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $restoreError) {
            error_log('Could not restore foreign key checks after Learning import: ' . $restoreError->getMessage());
        }
    }
    if (is_resource($handle)) {
        fclose($handle);
    }
    error_log('Learning data import failed: ' . $e->getMessage());
    importResponse($e instanceof InvalidArgumentException ? 422 : 500, [
        'success' => false,
        'message' => $e instanceof InvalidArgumentException
            ? $e->getMessage()
            : 'Import failed. No rows were committed.',
    ]);
}