<?php
declare(strict_types=1);

/**
 * ld_schema_export.php
 * ---------------------------------------------------------------------------
 * Exports every `ld_` table of the HRMS database as a DATA-FREE, IMPORT-SAFE
 * schema file.
 *
 * The generated file is idempotent — it can be imported over and over on a
 * database that already has tables and data:
 *
 *   1) CREATE TABLE IF NOT EXISTS ...   creates only the tables that are missing
 *   2) for tables that already exist:
 *        ADD COLUMN     only when the column is missing
 *        MODIFY COLUMN  only when type / nullability / default / extra changed
 *        ADD KEY/INDEX  only when the index is missing
 *        ADD CONSTRAINT only when the foreign key / check is missing
 *        ENGINE/CHARSET only when the table options changed
 *
 * It never drops a table, column, index or constraint and never touches rows.
 *
 * Usage:
 *   php ld_schema_export.php [--host=127.0.0.1] [--port=3306] [--user=root]
 *                            [--pass=PASSWORD] [--db=hrms] [--prefix=ld_]
 *                            [--out="path/to/file.sql"]
 */

$opts = [
    'host'   => '127.0.0.1',
    'port'   => '3306',
    'user'   => 'root',
    'pass'   => '',
    'db'     => 'hrms',
    'prefix' => 'ld_',
    'out'    => __DIR__ . '/ld_tables update/ld_tables schema.sql',
];

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([A-Za-z]+)=(.*)$/s', $arg, $m) && array_key_exists($m[1], $opts)) {
        $opts[$m[1]] = $m[2];
    } else {
        fwrite(STDERR, "Ignoring unrecognised argument: {$arg}\n");
    }
}

/* ------------------------------------------------------------------ helpers */

function q(PDO $pdo, ?string $value): string
{
    return $value === null ? 'NULL' : $pdo->quote($value);
}

/**
 * Split the body of a SHOW CREATE TABLE statement into its top-level
 * definitions (columns, keys, constraints) and return the trailing options.
 *
 * @return array{0: string[], 1: string}
 */
function parseCreateTable(string $sql): array
{
    $n    = strlen($sql);
    $open = strpos($sql, '(');
    if ($open === false) {
        return [[], ''];
    }

    $defs  = [];
    $buf   = '';
    $depth = 1;

    for ($i = $open + 1; $i < $n; $i++) {
        $c = $sql[$i];

        /* quoted identifier / string literal — copy verbatim */
        if ($c === '`' || $c === "'" || $c === '"') {
            $quote = $c;
            $buf  .= $c;
            $i++;
            while ($i < $n) {
                $ch   = $sql[$i];
                $buf .= $ch;
                if ($quote !== '`' && $ch === '\\' && $i + 1 < $n) {
                    $i++;
                    $buf .= $sql[$i];
                } elseif ($ch === $quote) {
                    if ($i + 1 < $n && $sql[$i + 1] === $quote) { // doubled = escaped
                        $i++;
                        $buf .= $sql[$i];
                    } else {
                        break;
                    }
                }
                $i++;
            }
            continue;
        }

        if ($c === '(') {
            $depth++;
            $buf .= $c;
            continue;
        }

        if ($c === ')') {
            $depth--;
            if ($depth === 0) {
                $defs[] = trim($buf);
                return [$defs, trim(substr($sql, $i + 1))];
            }
            $buf .= $c;
            continue;
        }

        if ($c === ',' && $depth === 1) {
            $defs[] = trim($buf);
            $buf    = '';
            continue;
        }

        $buf .= $c;
    }

    return [$defs, ''];
}

function unquoteIdent(string $ident): string
{
    return str_replace('``', '`', $ident);
}

/* ------------------------------------------------------------- connect + read */

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $opts['host'], $opts['port'], $opts['db']);

try {
    $pdo = new PDO($dsn, $opts['user'], $opts['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to {$opts['host']}:{$opts['port']} db={$opts['db']} — {$e->getMessage()}\n");
    exit(1);
}

$serverVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

$like = addcslashes($opts['prefix'], '\\%_') . '%';

/* collation -> character set (information_schema.TABLES has no charset column) */
$charsetOf = [];
foreach ($pdo->query('SELECT COLLATION_NAME, CHARACTER_SET_NAME FROM information_schema.COLLATIONS') as $row) {
    $charsetOf[$row['COLLATION_NAME']] = $row['CHARACTER_SET_NAME'];
}

$stmt = $pdo->prepare(
    "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION
       FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = ?
        AND TABLE_TYPE   = 'BASE TABLE'
        AND TABLE_NAME   LIKE ? ESCAPE '\\\\'
      ORDER BY TABLE_NAME"
);
$stmt->execute([$opts['db'], $like]);
$tables = $stmt->fetchAll();

if (!$tables) {
    fwrite(STDERR, "No tables matching '{$opts['prefix']}%' found in `{$opts['db']}`.\n");
    exit(1);
}

$creates        = [];   // [table, create-sql]
$tableRows      = [];   // engine / charset sync
$colRows        = [];
$idxRows        = [];
$conRows        = [];
$warnings       = [];
$nColumns       = 0;

foreach ($tables as $t) {
    $table = $t['TABLE_NAME'];

    $collation = (string) $t['TABLE_COLLATION'];
    $charset   = $charsetOf[$collation] ?? (string) strstr($collation, '_', true);

    $tableRows[] = [$table, (string) $t['ENGINE'], $charset, $collation];

    $show = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM);
    $createSql = (string) $show[1];

    $creates[] = [$table, preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $createSql)];

    [$defs, $options] = parseCreateTable($createSql);

    /* column metadata (comparison values) straight from information_schema */
    $metaStmt = $pdo->prepare(
        "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
          ORDER BY ORDINAL_POSITION"
    );
    $metaStmt->execute([$opts['db'], $table]);
    $meta = [];
    foreach ($metaStmt->fetchAll() as $m) {
        $meta[strtolower($m['COLUMN_NAME'])] = $m;
    }

    $ordinal = 0;
    $prev    = null;
    $seqIdx  = 0;
    $seqCon  = 0;

    foreach ($defs as $def) {
        /* ---- column ---- */
        if ($def !== '' && $def[0] === '`') {
            if (!preg_match('/^`((?:[^`]|``)+)`\s*(.*)$/s', $def, $m)) {
                $warnings[] = "{$table}: unparsed column definition — {$def}";
                continue;
            }
            $colName = unquoteIdent($m[1]);
            $mi      = $meta[strtolower($colName)] ?? null;
            if ($mi === null) {
                $warnings[] = "{$table}.{$colName}: not found in information_schema";
                continue;
            }

            $ordinal++;
            $nColumns++;

            $colRows[] = [
                'table'    => $table,
                'ordinal'  => $ordinal,
                'column'   => $colName,
                'def'      => $def,
                'type'     => (string) $mi['COLUMN_TYPE'],
                'nullable' => (string) $mi['IS_NULLABLE'],
                'default'  => $mi['COLUMN_DEFAULT'] === null ? '<none>' : (string) $mi['COLUMN_DEFAULT'],
                'extra'    => (string) $mi['EXTRA'],
                'after'    => $prev,
            ];
            $prev = $colName;
            continue;
        }

        /* ---- primary key ---- */
        if (preg_match('/^PRIMARY\s+KEY\b/i', $def)) {
            $seqIdx++;
            $idxRows[] = ['table' => $table, 'seq' => $seqIdx, 'name' => 'PRIMARY', 'def' => $def];
            continue;
        }

        /* ---- secondary / unique / fulltext / spatial key ---- */
        if (preg_match(
            '/^(?:UNIQUE\s+KEY|UNIQUE\s+INDEX|FULLTEXT\s+KEY|FULLTEXT\s+INDEX|SPATIAL\s+KEY|SPATIAL\s+INDEX|KEY|INDEX)\s+(?:`((?:[^`]|``)+)`|([A-Za-z0-9_$]+))/i',
            $def,
            $m
        )) {
            $idxName = ($m[1] ?? '') !== '' ? unquoteIdent($m[1]) : ($m[2] ?? '');
            if ($idxName === '') {
                $warnings[] = "{$table}: index without a name — {$def}";
                continue;
            }
            $seqIdx++;
            $idxRows[] = ['table' => $table, 'seq' => $seqIdx, 'name' => $idxName, 'def' => $def];
            continue;
        }

        /* ---- named constraint: FK / CHECK ---- */
        if (preg_match('/^CONSTRAINT\s+`((?:[^`]|``)+)`\s+(FOREIGN\s+KEY|CHECK)\b/is', $def, $m)) {
            $conName = unquoteIdent($m[1]);
            $conType = strtoupper(preg_replace('/\s+/', ' ', $m[2]));
            $seqCon++;
            $conRows[] = [
                'table' => $table,
                'seq'   => $seqCon,
                'name'  => $conName,
                'type'  => $conType,
                'def'   => $def,
            ];
            continue;
        }

        $warnings[] = "{$table}: unrecognised definition — {$def}";
    }
}

/* ------------------------------------------------------------------ generate */

$generated = date('Y-m-d H:i:s');
$counts    = [
    'tables'      => count($creates),
    'columns'     => $nColumns,
    'indexes'     => count($idxRows),
    'constraints' => count($conRows),
];

$out = [];
$out[] = '-- =======================================================================';
$out[] = '--  LEARNING & DEVELOPMENT (`ld_`) — SCHEMA ONLY  ·  IMPORT-SAFE';
$out[] = '-- =======================================================================';
$out[] = "--  Generated  : {$generated}";
$out[] = "--  Source     : {$serverVersion} @ {$opts['host']}:{$opts['port']}  db `{$opts['db']}`";
$out[] = "--  Contents   : {$counts['tables']} tables, {$counts['columns']} columns, "
       . "{$counts['indexes']} indexes, {$counts['constraints']} constraints";
$out[] = '--  Data       : none — DDL only';
$out[] = '--';
$out[] = '--  HOW TO IMPORT';
$out[] = '--    mysql -u root hrms < "ld_tables schema.sql"';
$out[] = '--    (or phpMyAdmin > Import > choose this file > Go)';
$out[] = '--';
$out[] = '--  WHAT IT DOES';
$out[] = '--    1) CREATE TABLE IF NOT EXISTS  — only tables that are missing';
$out[] = '--    2) for tables that already exist, only the differences are applied:';
$out[] = '--         ADD COLUMN      (column missing)';
$out[] = '--         MODIFY COLUMN   (type / nullability / default / extra changed)';
$out[] = '--         ADD KEY/INDEX   (index missing)';
$out[] = '--         ADD CONSTRAINT  (foreign key / check missing)';
$out[] = '--         ENGINE / CHARSET (table options changed)';
$out[] = '--';
$out[] = '--  WHAT IT NEVER DOES';
$out[] = '--    * never DROP a table, column, index, constraint or row';
$out[] = '--    * never INSERT / UPDATE / DELETE data';
$out[] = '--    So it is safe to import repeatedly on a database that already has data.';
$out[] = '--';
$out[] = '--  Helper objects (`_ld_schema_cols`, `_ld_schema_idx`, `_ld_schema_con`,';
$out[] = '--  procedure `_ld_schema_sync`) are dropped again at the end of the run.';
$out[] = '-- =======================================================================';
$out[] = '';
$out[] = 'SET NAMES utf8mb4;';
$out[] = 'SET FOREIGN_KEY_CHECKS = 0;';
$out[] = '';

/* -- section 1: missing tables -------------------------------------------- */
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '-- 1) CREATE THE TABLES THAT DO NOT EXIST YET';
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '';
foreach ($creates as [$table, $create]) {
    $out[] = '-- ' . $table;
    $out[] = rtrim($create, ';') . ';';
    $out[] = '';
}

/* -- section 2: stash the desired schema ---------------------------------- */
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '-- 2) STASH THE DESIRED SCHEMA (helper tables, dropped at the end)';
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_con`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_idx`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_cols`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_tables`;';
$out[] = '';
$out[] = 'CREATE TABLE `_ld_schema_tables` (';
$out[] = '  `table_name` VARCHAR(64) NOT NULL,';
$out[] = '  `engine`     VARCHAR(64) NOT NULL,';
$out[] = '  `charset`    VARCHAR(64) NOT NULL,';
$out[] = '  `collation`  VARCHAR(64) NOT NULL,';
$out[] = '  KEY `k_table` (`table_name`)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
$out[] = '';
$out[] = 'CREATE TABLE `_ld_schema_cols` (';
$out[] = '  `table_name`  VARCHAR(64)  NOT NULL,';
$out[] = '  `ordinal`     INT          NOT NULL,';
$out[] = '  `column_name` VARCHAR(64)  NOT NULL,';
$out[] = '  `col_def`     LONGTEXT     NOT NULL,';
$out[] = '  `col_type`    VARCHAR(255) NOT NULL,';
$out[] = '  `is_nullable` VARCHAR(3)   NOT NULL,';
$out[] = '  `col_default` TEXT         NULL,';
$out[] = '  `extra`       VARCHAR(255) NOT NULL DEFAULT \'\',';
$out[] = '  `after_col`   VARCHAR(64)  NULL,';
$out[] = '  KEY `k_table` (`table_name`, `ordinal`)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
$out[] = '';
$out[] = 'CREATE TABLE `_ld_schema_idx` (';
$out[] = '  `table_name` VARCHAR(64) NOT NULL,';
$out[] = '  `seq`        INT         NOT NULL,';
$out[] = '  `index_name` VARCHAR(64) NOT NULL,';
$out[] = '  `idx_def`    LONGTEXT    NOT NULL,';
$out[] = '  KEY `k_table` (`table_name`, `seq`)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
$out[] = '';
$out[] = 'CREATE TABLE `_ld_schema_con` (';
$out[] = '  `table_name`      VARCHAR(64) NOT NULL,';
$out[] = '  `seq`             INT         NOT NULL,';
$out[] = '  `constraint_name` VARCHAR(64) NOT NULL,';
$out[] = '  `constraint_type` VARCHAR(64) NOT NULL,';
$out[] = '  `con_def`         LONGTEXT    NOT NULL,';
$out[] = '  KEY `k_table` (`table_name`, `seq`)';
$out[] = ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
$out[] = '';

$emitInserts = static function (array &$out, string $table, array $cols, array $rows, int $chunkSize = 40): void {
    foreach (array_chunk($rows, $chunkSize) as $chunk) {
        $out[] = 'INSERT INTO ' . $table . ' (' . implode(', ', array_map(static fn($c) => "`{$c}`", $cols)) . ') VALUES';
        $out[] = '  ' . implode(",\n  ", array_map(static fn($vals) => '(' . implode(', ', $vals) . ')', $chunk)) . ';';
    }
    $out[] = '';
};

$emitInserts($out, '`_ld_schema_tables`', ['table_name', 'engine', 'charset', 'collation'], array_map(
    static fn($r) => [q($pdo, $r[0]), q($pdo, $r[1]), q($pdo, $r[2]), q($pdo, $r[3])],
    $tableRows
));

$emitInserts($out, '`_ld_schema_cols`', ['table_name', 'ordinal', 'column_name', 'col_def', 'col_type', 'is_nullable', 'col_default', 'extra', 'after_col'], array_map(
    static fn($r) => [
        q($pdo, $r['table']), (string) $r['ordinal'], q($pdo, $r['column']), q($pdo, $r['def']),
        q($pdo, $r['type']), q($pdo, $r['nullable']), q($pdo, $r['default']), q($pdo, $r['extra']),
        q($pdo, $r['after']),
    ],
    $colRows
));

$emitInserts($out, '`_ld_schema_idx`', ['table_name', 'seq', 'index_name', 'idx_def'], array_map(
    static fn($r) => [q($pdo, $r['table']), (string) $r['seq'], q($pdo, $r['name']), q($pdo, $r['def'])],
    $idxRows
));

$emitInserts($out, '`_ld_schema_con`', ['table_name', 'seq', 'constraint_name', 'constraint_type', 'con_def'], array_map(
    static fn($r) => [q($pdo, $r['table']), (string) $r['seq'], q($pdo, $r['name']), q($pdo, $r['type']), q($pdo, $r['def'])],
    $conRows
));

/* -- section 3: the sync procedure ---------------------------------------- */
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '-- 3) APPLY THE DIFFERENCES TO TABLES THAT ALREADY EXIST';
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '';
$out[] = 'DROP PROCEDURE IF EXISTS `_ld_schema_sync`;';
$out[] = 'DELIMITER $$';
$out[] = 'CREATE PROCEDURE `_ld_schema_sync`()';
$out[] = 'BEGIN';
$out[] = '    DECLARE v_done     INT          DEFAULT 0;';
$out[] = '    DECLARE v_ddl      LONGTEXT;';
$out[] = '';
$out[] = '    DECLARE v_table    VARCHAR(64);';
$out[] = '    DECLARE v_engine   VARCHAR(64);';
$out[] = '    DECLARE v_charset  VARCHAR(64);';
$out[] = '    DECLARE v_coll     VARCHAR(64);';
$out[] = '';
$out[] = '    DECLARE v_ord      INT;';
$out[] = '    DECLARE v_col      VARCHAR(64);';
$out[] = '    DECLARE v_coldef   LONGTEXT;';
$out[] = '    DECLARE v_coltype  VARCHAR(255);';
$out[] = '    DECLARE v_nullable VARCHAR(3);';
$out[] = '    DECLARE v_default  TEXT;';
$out[] = '    DECLARE v_extra    VARCHAR(255);';
$out[] = '    DECLARE v_after    VARCHAR(64);';
$out[] = '';
$out[] = '    DECLARE v_index    VARCHAR(64);';
$out[] = '    DECLARE v_indexdef LONGTEXT;';
$out[] = '';
$out[] = '    DECLARE v_conname  VARCHAR(64);';
$out[] = '    DECLARE v_contype  VARCHAR(64);';
$out[] = '    DECLARE v_condef   LONGTEXT;';
$out[] = '';
$out[] = '    DECLARE cur_tbl CURSOR FOR';
$out[] = '        SELECT table_name, engine, charset, collation';
$out[] = '          FROM `_ld_schema_tables` ORDER BY table_name;';
$out[] = '    DECLARE cur_col CURSOR FOR';
$out[] = '        SELECT table_name, ordinal, column_name, col_def, col_type, is_nullable, col_default, extra, after_col';
$out[] = '          FROM `_ld_schema_cols` ORDER BY table_name, ordinal;';
$out[] = '    DECLARE cur_idx CURSOR FOR';
$out[] = '        SELECT table_name, index_name, idx_def';
$out[] = '          FROM `_ld_schema_idx` ORDER BY table_name, seq;';
$out[] = '    DECLARE cur_con CURSOR FOR';
$out[] = '        SELECT table_name, constraint_name, constraint_type, con_def';
$out[] = '          FROM `_ld_schema_con` ORDER BY table_name, seq;';
$out[] = '';
$out[] = '    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;';
$out[] = '';
$out[] = '    /* --- table options: engine / charset / collation --- */';
$out[] = '    SET v_done = 0;';
$out[] = '    OPEN cur_tbl;';
$out[] = '    tbl_loop: LOOP';
$out[] = '        FETCH cur_tbl INTO v_table, v_engine, v_charset, v_coll;';
$out[] = '        IF v_done = 1 THEN LEAVE tbl_loop; END IF;';
$out[] = '        IF EXISTS (SELECT 1 FROM information_schema.TABLES';
$out[] = '                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)';
$out[] = '           AND NOT EXISTS (SELECT 1 FROM information_schema.TABLES';
$out[] = '                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table';
$out[] = '                               AND ENGINE = v_engine AND TABLE_COLLATION = v_coll) THEN';
$out[] = '            SET v_ddl = CONCAT(\'ALTER TABLE `\', v_table, \'` ENGINE=\', v_engine,';
$out[] = '                               \' DEFAULT CHARACTER SET \', v_charset, \' COLLATE \', v_coll);';
$out[] = '            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;';
$out[] = '        END IF;';
$out[] = '    END LOOP;';
$out[] = '    CLOSE cur_tbl;';
$out[] = '';
$out[] = '    /* --- columns: add missing, fix changed --- */';
$out[] = '    SET v_done = 0;';
$out[] = '    OPEN cur_col;';
$out[] = '    col_loop: LOOP';
$out[] = '        FETCH cur_col INTO v_table, v_ord, v_col, v_coldef, v_coltype, v_nullable, v_default, v_extra, v_after;';
$out[] = '        IF v_done = 1 THEN LEAVE col_loop; END IF;';
$out[] = '        IF EXISTS (SELECT 1 FROM information_schema.TABLES';
$out[] = '                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table) THEN';
$out[] = '            IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS';
$out[] = '                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table';
$out[] = '                               AND COLUMN_NAME = v_col) THEN';
$out[] = '                SET v_ddl = CONCAT(\'ALTER TABLE `\', v_table, \'` ADD COLUMN \', v_coldef,';
$out[] = '                                   CASE WHEN v_after IS NULL THEN \' FIRST\'';
$out[] = '                                        ELSE CONCAT(\' AFTER `\', v_after, \'`\') END);';
$out[] = '                PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;';
$out[] = '            ELSEIF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS';
$out[] = '                                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table';
$out[] = '                                   AND COLUMN_NAME = v_col';
$out[] = '                                   AND COLUMN_TYPE <=> v_coltype';
$out[] = '                                   AND IS_NULLABLE <=> v_nullable';
$out[] = '                                   AND EXTRA <=> v_extra';
$out[] = '                                   AND IFNULL(COLUMN_DEFAULT, \'<none>\') = v_default) THEN';
$out[] = '                SET v_ddl = CONCAT(\'ALTER TABLE `\', v_table, \'` MODIFY COLUMN \', v_coldef);';
$out[] = '                PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;';
$out[] = '            END IF;';
$out[] = '        END IF;';
$out[] = '    END LOOP;';
$out[] = '    CLOSE cur_col;';
$out[] = '';
$out[] = '    /* --- indexes: add missing --- */';
$out[] = '    SET v_done = 0;';
$out[] = '    OPEN cur_idx;';
$out[] = '    idx_loop: LOOP';
$out[] = '        FETCH cur_idx INTO v_table, v_index, v_indexdef;';
$out[] = '        IF v_done = 1 THEN LEAVE idx_loop; END IF;';
$out[] = '        IF EXISTS (SELECT 1 FROM information_schema.TABLES';
$out[] = '                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)';
$out[] = '           AND NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS';
$out[] = '                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table';
$out[] = '                               AND INDEX_NAME = v_index) THEN';
$out[] = '            SET v_ddl = CONCAT(\'ALTER TABLE `\', v_table, \'` ADD \', v_indexdef);';
$out[] = '            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;';
$out[] = '        END IF;';
$out[] = '    END LOOP;';
$out[] = '    CLOSE cur_idx;';
$out[] = '';
$out[] = '    /* --- constraints: add missing foreign keys / checks --- */';
$out[] = '    SET v_done = 0;';
$out[] = '    OPEN cur_con;';
$out[] = '    con_loop: LOOP';
$out[] = '        FETCH cur_con INTO v_table, v_conname, v_contype, v_condef;';
$out[] = '        IF v_done = 1 THEN LEAVE con_loop; END IF;';
$out[] = '        IF EXISTS (SELECT 1 FROM information_schema.TABLES';
$out[] = '                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = v_table)';
$out[] = '           AND NOT EXISTS (SELECT 1 FROM information_schema.TABLE_CONSTRAINTS';
$out[] = '                             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = v_table';
$out[] = '                               AND CONSTRAINT_NAME = v_conname) THEN';
$out[] = '            SET v_ddl = CONCAT(\'ALTER TABLE `\', v_table, \'` ADD \', v_condef);';
$out[] = '            PREPARE s FROM v_ddl; EXECUTE s; DEALLOCATE PREPARE s;';
$out[] = '        END IF;';
$out[] = '    END LOOP;';
$out[] = '    CLOSE cur_con;';
$out[] = 'END$$';
$out[] = 'DELIMITER ;';
$out[] = '';
$out[] = 'CALL `_ld_schema_sync`();';
$out[] = '';
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '-- 4) CLEAN UP THE HELPER OBJECTS';
$out[] = '-- -----------------------------------------------------------------------';
$out[] = '';
$out[] = 'DROP PROCEDURE IF EXISTS `_ld_schema_sync`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_con`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_idx`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_cols`;';
$out[] = 'DROP TABLE IF EXISTS `_ld_schema_tables`;';
$out[] = '';
$out[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$out[] = '';
$out[] = "SELECT CONCAT('ld_ schema synced — ', COUNT(*), ' table(s) present.') AS result";
$out[] = "  FROM information_schema.TABLES";
$out[] = " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE 'ld\\_%';";
$out[] = '';

/* -------------------------------------------------------------------- write */

$dir = dirname($opts['out']);
if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
    fwrite(STDERR, "Cannot create output directory: {$dir}\n");
    exit(1);
}

file_put_contents($opts['out'], implode("\n", $out));

printf(
    "Wrote %s\n  source      : %s @ %s:%s db `%s`\n  tables      : %d\n  columns     : %d\n  indexes     : %d\n  constraints : %d\n  size        : %s bytes\n",
    $opts['out'],
    $serverVersion,
    $opts['host'],
    $opts['port'],
    $opts['db'],
    $counts['tables'],
    $counts['columns'],
    $counts['indexes'],
    $counts['constraints'],
    number_format((int) filesize($opts['out']))
);

if ($warnings) {
    fwrite(STDERR, "\nWarnings (" . count($warnings) . "):\n  - " . implode("\n  - ", $warnings) . "\n");
    exit(2);
}
