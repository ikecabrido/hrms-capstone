<?php
/**
 * Cron: run-due.php — the scheduler's entry point.
 *
 * Command line:
 *   php <app root>/modules/learning/cron/run-due.php               # every job that is due
 *   php <app root>/modules/learning/cron/run-due.php --status      # what is scheduled, what last ran
 *   php <app root>/modules/learning/cron/run-due.php --all         # run everything now, due or not
 *   php <app root>/modules/learning/cron/run-due.php --job=<id>    # one job
 *
 * Wire one line into the OS scheduler (cron, or Task Scheduler on Windows) and the
 * module's jobs keep their own slots — each job decides whether it is due from its
 * ld_cron_run history, so running this every few minutes is the intended usage:
 *
 *   * * * * * php <app root>/modules/learning/cron/run-due.php >> <app root>/modules/learning/cron/run-due.log 2>&1
 *
 * The same check also runs from the admin Workforce Integration page, so the schedule
 * still holds on a deployment where no scheduler was configured.
 * Exit code 0 when every job that ran succeeded, 1 otherwise.
 */

// Command line only — the authenticated web path is
// pages/admin/ajax/workforce-integration-run-cron.php.
if (!empty($_SERVER['REQUEST_METHOD'])) {
    http_response_code(404);
    exit;
}

require_once dirname(__FILE__, 2) . '/classes/cron.php';

$job = null;
$all = false;
$statusOnly = false;

foreach (array_slice($argv ?? [], 1) as $argument) {
    if ($argument === '--all') {
        $all = true;
    } elseif ($argument === '--status') {
        $statusOnly = true;
    } elseif (str_starts_with($argument, '--job=')) {
        $job = substr($argument, 6);
    }
}

try {
    $cron = new LearningCron();
} catch (Throwable $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

/** One operator-readable line per run. */
function describeRun(string $job, array $result): string
{
    $last = $result['status']['last'] ?? null;
    $parts = [
        $job,
        $result['success'] ? 'ok' : 'FAILED',
        (string) $result['message'],
    ];

    if ($last !== null) {
        $parts[] = $last['status'] . ' at ' . $last['started_at'];
    }
    if (!empty($result['duration_ms'])) {
        $parts[] = $result['duration_ms'] . ' ms';
    }

    return implode(' | ', $parts);
}

if ($statusOnly) {
    foreach (LearningCron::jobIds() as $jobId) {
        $scheduled = $cron->getStatus($jobId);
        echo $jobId . ' | ' . $scheduled['schedule']
            . ' | last: ' . ($scheduled['last'] !== null
                ? $scheduled['last']['status'] . ' at ' . $scheduled['last']['started_at']
                  . ($scheduled['last']['records_processed'] !== null ? ' (' . $scheduled['last']['records_processed'] . ' records)' : '')
                : 'never run')
            . ' | ' . ($scheduled['due'] ? 'DUE now' : 'next due ' . (string) $scheduled['next_due'])
            . PHP_EOL;
    }

    exit(0);
}

if ($job !== null && !LearningCron::isJob($job)) {
    fwrite(STDERR, 'Unknown job "' . $job . '". Known jobs: ' . implode(', ', LearningCron::jobIds()) . PHP_EOL);
    exit(1);
}

$failed = 0;

if ($all) {
    $jobIds = $job !== null ? [$job] : LearningCron::jobIds();
    foreach ($jobIds as $jobId) {
        $result = $cron->run($jobId, 'scheduled');
        echo describeRun($jobId, $result) . PHP_EOL;
        if (!$result['success']) {
            $failed++;
        }
    }
} else {
    foreach ($cron->runDue('scheduled') as $entry) {
        if ($job !== null && $entry['job'] !== $job) {
            continue;
        }
        if (!$entry['due']) {
            echo $entry['job'] . ' | not due' . PHP_EOL;
            continue;
        }
        echo describeRun($entry['job'], $entry['result']) . PHP_EOL;
        if (empty($entry['result']['success'])) {
            $failed++;
        }
    }
}

exit($failed === 0 ? 0 : 1);
