<?php
/**
 * Cron: backup-database.php
 * Full database backup using mysqldump.
 * Runs via OS cron (every 30 min): php C:/xampp/htdocs/itsar/modules/learning/cron/backup-database.php
 *
 * - Creates timestamped SQL dumps in cron/backups/
 * - Keeps the last 30 backups, auto-deletes older ones
 * - Logs activity to cron/backup.log
 * - Web-accessible but protected by a secret key check
 */

// ── Configuration ──────────────────────────────────────────────
$DB_HOST    = '127.0.0.1';
$DB_PORT    = '3306';
$DB_NAME    = 'hrms';
$DB_USER    = 'root';
$DB_PASS    = getenv('DB_PASSWORD') ?: '';  // Set DB_PASSWORD env var or edit here

$BACKUP_DIR = dirname(__FILE__) . '/backups/';
$LOG_FILE   = dirname(__FILE__) . '/backup.log';
$KEEP_COUNT = 30;  // Maximum number of backups to retain

$MYSQLDUMP  = 'C:/xampp/mysql/bin/mysqldump.exe';

// ── Secret key for web-triggered backups ───────────────────────
// When run from CLI, no key needed. When triggered via browser, requires ?key=<secret>
$SECRET_KEY = 'itsar-backup-2026';
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    header('Content-Type: application/json; charset=utf-8');
    $providedKey = $_GET['key'] ?? '';
    if ($providedKey !== $SECRET_KEY) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden — invalid or missing key']);
        exit;
    }
}

// ── Helpers ────────────────────────────────────────────────────
function logMsg(string $msg): void {
    global $LOG_FILE, $isCLI;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if ($isCLI) {
        echo $msg . PHP_EOL;
    }
}

function removeOldBackups(string $dir, int $keep): void {
    $files = glob($dir . 'hrms_backup_*.sql');
    if (count($files) <= $keep) return;

    // Sort by modification time (oldest first)
    usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

    $toRemove = array_slice($files, 0, count($files) - $keep);
    foreach ($toRemove as $file) {
        if (unlink($file)) {
            logMsg('Deleted old backup: ' . basename($file));
        }
    }
}

// ── Main ───────────────────────────────────────────────────────
logMsg('=== Backup started ===');

// Ensure backup directory exists
if (!is_dir($BACKUP_DIR)) {
    if (!mkdir($BACKUP_DIR, 0755, true)) {
        logMsg('ERROR: Could not create backup directory: ' . $BACKUP_DIR);
        if (!$isCLI) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Could not create backup directory']);
        }
        exit(1);
    }
}

// Build mysqldump command
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $BACKUP_DIR . "hrms_backup_{$timestamp}.sql";

$cmd = sprintf(
    '"%s" --host="%s" --port="%s" --user="%s" --password="%s" --single-transaction --routines --triggers --events "%s" > "%s" 2>&1',
    $MYSQLDUMP,
    $DB_HOST,
    $DB_PORT,
    $DB_USER,
    $DB_PASS,
    $DB_NAME,
    $backupFile
);

logMsg("Running: mysqldump -> " . basename($backupFile));

// Execute
exec($cmd, $output, $exitCode);

if ($exitCode !== 0) {
    $errorOutput = implode("\n", $output);
    logMsg("ERROR: mysqldump failed with exit code $exitCode");
    logMsg("Error output: $errorOutput");

    // Clean up empty/failed file
    if (file_exists($backupFile) && filesize($backupFile) < 100) {
        unlink($backupFile);
    }

    if (!$isCLI) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'mysqldump failed', 'details' => $errorOutput]);
    }
    exit(1);
}

// Verify backup file
if (!file_exists($backupFile) || filesize($backupFile) < 100) {
    logMsg('ERROR: Backup file is missing or too small');
    if (!$isCLI) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Backup file is missing or empty']);
    }
    exit(1);
}

$fileSize = filesize($backupFile);
logMsg("Backup created: " . basename($backupFile) . " (" . number_format($fileSize) . " bytes)");

// Prune old backups
removeOldBackups($BACKUP_DIR, $KEEP_COUNT);

$totalBackups = count(glob($BACKUP_DIR . 'hrms_backup_*.sql'));
logMsg("Total backups retained: $totalBackups");
logMsg('=== Backup complete ===');

if (!$isCLI) {
    echo json_encode([
        'success' => true,
        'file' => basename($backupFile),
        'size' => $fileSize,
        'total_backups' => $totalBackups,
    ]);
}
