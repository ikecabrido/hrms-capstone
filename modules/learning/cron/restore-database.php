<?php
/**
 * Cron: restore-database.php
 * Restore the database from a backup SQL file.
 *
 * ⚠️  CLI-ONLY — This script deliberately refuses to run from a web browser.
 *     A live database restore is destructive if triggered by accident.
 *     Run manually: php C:/xampp/htdocs/itsar/modules/learning/cron/restore-database.php <backup-file>
 *
 * Usage:
 *   php restore-database.php                        # Lists available backups
 *   php restore-database.php hrms_backup_2026-08-23_12-00-00.sql   # Restores specific backup
 *   php restore-database.php --latest               # Restores the most recent backup
 *   php restore-database.php --confirm              # Must be used with --latest or a filename
 */

// ── Force CLI — refuse to run from web ─────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "FORBIDDEN: This restore script can only be run from the command line.\n";
    echo "Usage: php restore-database.php [backup-file.sql | --latest]\n";
    exit(1);
}

// ── Configuration ──────────────────────────────────────────────
$DB_HOST    = '127.0.0.1';
$DB_PORT    = '3306';
$DB_NAME    = 'hrms';
$DB_USER    = 'root';
$DB_PASS    = getenv('DB_PASSWORD') ?: '';

$BACKUP_DIR = dirname(__FILE__) . '/backups/';
$LOG_FILE   = dirname(__FILE__) . '/restore.log';

$MYSQL      = 'C:/xampp/mysql/bin/mysql.exe';

// ── Helpers ────────────────────────────────────────────────────
function logMsg(string $msg): void {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    echo $msg . PHP_EOL;
}

function listBackups(): array {
    global $BACKUP_DIR;
    $files = glob($BACKUP_DIR . 'hrms_backup_*.sql');
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a)); // newest first
    return $files;
}

function getLatestBackup(): ?string {
    $files = listBackups();
    return !empty($files) ? $files[0] : null;
}

function getBackupName(string $path): string {
    return basename($path);
}

// ── Parse arguments ────────────────────────────────────────────
$args = array_slice($argv, 1);

if (empty($args)) {
    // List available backups
    echo "=== Available Backups ===\n\n";
    $backups = listBackups();
    if (empty($backups)) {
        echo "No backups found in: $BACKUP_DIR\n";
        echo "Run backup-database.php first.\n";
    } else {
        echo str_pad('#', 4) . str_pad('File', 40) . str_pad('Size', 12) . "Date\n";
        echo str_repeat('-', 72) . "\n";
        foreach ($backups as $i => $file) {
            $num = str_pad($i + 1, 3);
            $name = str_pad(getBackupName($file), 39);
            $size = str_pad(number_format(filesize($file)) . ' B', 11);
            $date = date('Y-m-d H:i:s', filemtime($file));
            echo "$num $name $size $date\n";
        }
    }
    echo "\nUsage:\n";
    echo "  php restore-database.php <backup-file.sql>     # Restore specific backup\n";
    echo "  php restore-database.php --latest              # Restore most recent backup\n";
    exit(0);
}

// Determine backup file
$targetFile = null;

if ($args[0] === '--latest') {
    $targetFile = getLatestBackup();
    if (!$targetFile) {
        echo "ERROR: No backups found.\n";
        exit(1);
    }
    echo "Latest backup: " . getBackupName($targetFile) . "\n";
} else {
    $requested = $args[0];
    // Allow just the filename or full path
    if (is_file($requested)) {
        $targetFile = $requested;
    } elseif (is_file($BACKUP_DIR . $requested)) {
        $targetFile = $BACKUP_DIR . $requested;
    } else {
        echo "ERROR: Backup file not found: $requested\n";
        echo "Run without arguments to list available backups.\n";
        exit(1);
    }
}

// Safety confirmation
if (!in_array('--confirm', $args)) {
    echo "\n⚠️  WARNING: This will OVERWRITE the current '$DB_NAME' database!\n";
    echo "Backup to restore: " . getBackupName($targetFile) . "\n";
    echo "Backup size: " . number_format(filesize($targetFile)) . " bytes\n";
    echo "Backup date: " . date('Y-m-d H:i:s', filemtime($targetFile)) . "\n\n";
    echo "To proceed, run again with --confirm:\n";
    echo "  php restore-database.php " . getBackupName($targetFile) . " --confirm\n";
    exit(0);
}

// ── Execute Restore ────────────────────────────────────────────
logMsg('=== Restore started ===');
logMsg("Restoring from: " . getBackupName($targetFile));

// Step 1: Drop all tables in the database
logMsg("Step 1: Dropping existing tables...");
$dropCmd = sprintf(
    '"%s" --host="%s" --port="%s" --user="%s" --password="%s" --batch --skip-column-names -e "SELECT CONCAT(\'DROP TABLE IF EXISTS \', table_name, \' CASCADE;\') FROM information_schema.tables WHERE table_schema = \'%s\';" 2>/dev/null | "%s" --host="%s" --port="%s" --user="%s" --password="%s" "%s" 2>&1',
    $MYSQL, $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME,
    $MYSQL, $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME
);
exec($dropCmd, $dropOutput, $dropExit);
if ($dropExit !== 0) {
    logMsg("Warning: Drop step returned exit code $dropExit");
}

// Step 2: Restore from backup
logMsg("Step 2: Importing backup...");
$importCmd = sprintf(
    '"%s" --host="%s" --port="%s" --user="%s" --password="%s" "%s" < "%s" 2>&1',
    $MYSQL, $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME, $targetFile
);
exec($importCmd, $importOutput, $importExit);

if ($importExit !== 0) {
    $errorText = implode("\n", $importOutput);
    logMsg("ERROR: Import failed with exit code $importExit");
    logMsg("Error: $errorText");
    echo "\nRESTORE FAILED. Check $LOG_FILE for details.\n";
    exit(1);
}

logMsg("Import completed successfully.");
logMsg('=== Restore complete ===');
echo "\n✅ Database restored from: " . getBackupName($targetFile) . "\n";
echo "Log: $LOG_FILE\n";
