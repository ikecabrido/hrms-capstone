<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/LearningRole.php';

if (empty($_SESSION['employee_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

try {
    $pdo = (new Database())->getConnection();
    if (LearningRole::forEmployee($pdo, (int) $_SESSION['employee_id']) !== 'admin') {
        http_response_code(403);
        exit('Forbidden.');
    }

    $tables = $pdo->query("SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE = 'BASE TABLE'
          AND LEFT(TABLE_NAME, 3) = 'ld_'
        ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);

    if (!$tables) {
        http_response_code(404);
        exit('No Learning tables were found in the configured database.');
    }

    $stream = fopen('php://temp/maxmemory:5242880', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to prepare the export file.');
    }

    fwrite($stream, "-- Learning database export\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $table) {
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);
        if (!$createRow || empty($createRow[1])) {
            continue;
        }

        fwrite($stream, "DROP TABLE IF EXISTS {$quotedTable};\n{$createRow[1]};\n\n");

        $columnStmt = $pdo->prepare('SELECT COLUMN_NAME, DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name
            ORDER BY ORDINAL_POSITION');
        $columnStmt->execute([':table_name' => $table]);
        $columns = $columnStmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'COLUMN_NAME');
        $quotedColumns = implode(', ', array_map(static function ($column) {
            return '`' . str_replace('`', '``', $column) . '`';
        }, $columnNames));
        $binaryTypes = ['binary', 'varbinary', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'bit'];
        $rows = $pdo->query('SELECT * FROM ' . $quotedTable);

        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row[$column['COLUMN_NAME']];
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (in_array(strtolower($column['DATA_TYPE']), $binaryTypes, true)) {
                    $values[] = '0x' . bin2hex((string) $value);
                } elseif (in_array(strtolower($column['DATA_TYPE']), ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'decimal', 'numeric', 'float', 'double', 'real'], true)) {
                    $values[] = (string) $value;
                } else {
                    $quotedValue = $pdo->quote((string) $value);
                    if ($quotedValue === false) {
                        throw new RuntimeException('Unable to encode an exported value.');
                    }
                    $values[] = $quotedValue;
                }
            }

            fwrite($stream, 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES (' . implode(', ', $values) . ");\n");
        }
        fwrite($stream, "\n");
    }

    fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
    rewind($stream);

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="ld_database_' . date('Y-m-d_His') . '.sql"');
    header('Cache-Control: private, no-store, max-age=0');
    fpassthru($stream);
    fclose($stream);
} catch (Throwable $e) {
    error_log('Learning database export failed: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }
    exit('Learning database export failed. Check the application error log.');
}