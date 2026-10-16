#!/usr/bin/env php
<?php

date_default_timezone_set('Asia/Manila');

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

$opts = getopt('', ['dry-run', 'from::']);
$dryRun = array_key_exists('dry-run', $opts);
$from = isset($opts['from']) ? trim((string)$opts['from']) : null;

require_once __DIR__ . '/../app/core/TimeDatabase.php';
require_once __DIR__ . '/../app/helpers/LeaveAccrualRunner.php';
require_once __DIR__ . '/../app/helpers/LeaveYearlyAllocationRunner.php';

function writeCronLog(string $message): void
{
    $logPath = __DIR__ . '/../logs/leave_accrual_cron.log';
    $dir = dirname($logPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $timestamp = date('Y-m-d H:i:s P');
    $line = '[' . $timestamp . '] ' . $message . PHP_EOL;
    $existing = @file_get_contents($logPath);
    $content = ($existing !== false ? $existing : '') . $line;

    if (strlen($content) > 1048576) {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_values(array_filter($lines, static fn ($lineItem) => trim((string)$lineItem) !== ''));
        $keepCount = max(1, (int)ceil(count($lines) / 2));
        $content = implode(PHP_EOL, array_slice($lines, -$keepCount)) . PHP_EOL;
    }

    @file_put_contents($logPath, $content, LOCK_EX);
}

function formatResultSummary(string $label, array $result): string
{
    $status = !empty($result['success']) ? 'OK' : 'FAIL';
    $count = count($result['credited'] ?? []);
    $monthsBackfilled = (int)($result['months_backfilled'] ?? 0);
    $error = !empty($result['error']) ? ' error=' . $result['error'] : '';
    $skippedReason = !empty($result['skipped_reason']) ? ' skipped_reason=' . $result['skipped_reason'] : '';

    return sprintf('%s %s credited=%d months_backfilled=%d%s%s', $label, $status, $count, $monthsBackfilled, $error, $skippedReason);
}

try {
    $monthly = LeaveAccrualRunner::run([
        'dry_run' => $dryRun,
        'from' => $from,
        'now' => time(),
    ]);

    $yearly = LeaveYearlyAllocationRunner::run([
        'dry_run' => $dryRun,
        'now' => time(),
    ]);

    $summary = [
        formatResultSummary('monthly', $monthly),
        formatResultSummary('yearly', $yearly),
    ];

    $out = implode(PHP_EOL, $summary);
    echo $out . PHP_EOL;
    writeCronLog($out);

    $success = !empty($monthly['success']) && !empty($yearly['success']);

    if (!$dryRun && $success) {
        @file_put_contents(__DIR__ . '/../logs/last_leave_accrual_run.txt', (string)time());
        @file_put_contents(__DIR__ . '/../logs/last_leave_yearly_allocation_run.txt', (string)time());
    }

    exit($success ? 0 : 1);
} catch (Throwable $e) {
    $message = 'cron_failed: ' . $e->getMessage();
    echo $message . PHP_EOL;
    writeCronLog($message);
    exit(1);
}
