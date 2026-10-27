<?php
/**
 * LearningCron — the scheduler behind the module's cron scripts.
 *
 * Until now the scripts in cron/ were run by hand: nothing recorded whether they ran,
 * when, or with what result, and nothing could tell a caller that one was due. This
 * class is the single place that answers those three questions:
 *
 *   which jobs exist            JOBS (label, schedule, description, runner)
 *   is a job due right now      isDue() / getStatus()
 *   what happened last time     lastRun() / recentRuns() — one row per run in ld_cron_run
 *
 * Three ways in, all of them booked the same way:
 *   cron/materialize-engagement-snapshots.php   the documented per-job CLI entry point
 *   cron/run-due.php                            every due job (wire it to a real scheduler)
 *   pages/admin/ajax/workforce-integration-run-cron.php
 *                                               the admin page's "run now", and the
 *                                               auto-run it fires when a job is due
 * The last one is what makes the schedule hold on a deployment with no OS scheduler
 * configured — the admin page runs the job the first time it is opened after the slot.
 *
 * The clock is the database's (NOW()), never PHP's time(), because that is the clock
 * every timestamp in the schema uses. A run is due when the most recent scheduled slot
 * has passed without a booked run since, and it is never started twice at once: a row
 * left in 'running' past a grace period is treated as a crashed process and marked
 * failed rather than blocking the job forever.
 *
 * Failures here must not take a page down: an unmigrated ld_cron_run is reported as
 * 'available' => false in getStatus() and captured through DbError.
 */
require_once dirname(__FILE__, 4) . '/database/db.php';
require_once __DIR__ . '/dberror.php';
require_once __DIR__ . '/engagementsnapshot.php';

class LearningCron
{
    /** A 'running' row older than this is a dead process, not a live run. */
    private const STALE_RUN_SECONDS = 3600;

    /** How many past runs getStatus() reports. */
    private const STATUS_RUNS = 5;

    /**
     * Every job the module can schedule.
     *
     * 'script' is kept here so an operator reading the page can find the manual entry
     * point, and 'runner' is the callable that actually does the work — a job must not
     * be runnable from the web only by shelling out to a CLI file.
     */
    public const JOBS = [
        'engagement_snapshot' => [
            'label'       => 'Engagement / turnover-risk snapshot',
            'script'      => 'cron/materialize-engagement-snapshots.php',
            'runner'      => [EngagementSnapshot::class, 'run'],
            'schedule'    => ['frequency' => 'daily', 'at' => '06:00'],
            'description' => 'Scores every learner with enrolment activity into '
                . 'ld_engagement_snapshot — the risk bands and at-risk list on the turnover-risk tab, '
                . 'and the risk_summary the outbound analytics endpoint serves to Workforce.',
        ],
    ];

    /** Default job when a caller names none. */
    public const DEFAULT_JOB = 'engagement_snapshot';

    private PDO $conn;

    public function __construct($pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->conn = $pdo;
            return;
        }

        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // ─────────────────────────────────────────────────────────────
    // Job registry
    // ─────────────────────────────────────────────────────────────

    /** @return array<int, string> */
    public static function jobIds(): array
    {
        return array_keys(self::JOBS);
    }

    public static function isJob(string $job): bool
    {
        return isset(self::JOBS[$job]);
    }

    public static function jobLabel(string $job): string
    {
        return self::JOBS[$job]['label'] ?? ucwords(str_replace('_', ' ', $job));
    }

    public static function jobScript(string $job): ?string
    {
        return self::JOBS[$job]['script'] ?? null;
    }

    /** "Daily at 06:00 (server time)" for the job's configured slot. */
    public static function scheduleLabel(string $job): string
    {
        $schedule = self::JOBS[$job]['schedule'] ?? null;
        if (!is_array($schedule)) {
            return 'Not scheduled';
        }

        $frequency = (string) ($schedule['frequency'] ?? 'daily');
        $at = (string) ($schedule['at'] ?? '');

        return ucfirst($frequency) . ($at !== '' ? ' at ' . $at : '') . ' (server time)';
    }

    /**
     * The job's next scheduled slot strictly after $now, as 'Y-m-d H:i:s'.
     * Slots are wall-clock times in the database's zone, like every other timestamp.
     */
    public static function nextSlotAfter(string $job, string $now): ?string
    {
        $schedule = self::JOBS[$job]['schedule'] ?? null;
        if (!is_array($schedule) || empty($schedule['at'])) {
            return null;
        }

        $today = substr($now, 0, 10);
        $slot = $today . ' ' . self::slotTime((string) $schedule['at']);

        return $now < $slot ? $slot : date('Y-m-d H:i:s', strtotime($slot . ' +1 day'));
    }

    /** The most recent slot at or before $now — the one a due job is catching up on. */
    public static function currentSlot(string $job, string $now): ?string
    {
        $at = (string) (self::JOBS[$job]['schedule']['at'] ?? '');
        if ($at === '') {
            return null;
        }

        $slot = substr($now, 0, 10) . ' ' . self::slotTime($at);

        return $now >= $slot ? $slot : date('Y-m-d H:i:s', strtotime($slot . ' -1 day'));
    }

    /** "06:00" / "6" → "06:00:00", so every slot compares and sorts as a datetime string. */
    private static function slotTime(string $at): string
    {
        $parts = explode(':', trim($at));
        $hour = str_pad((string) (int) ($parts[0] ?? 0), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) (int) ($parts[1] ?? 0), 2, '0', STR_PAD_LEFT);
        $second = str_pad((string) (int) ($parts[2] ?? 0), 2, '0', STR_PAD_LEFT);

        return $hour . ':' . $minute . ':' . $second;
    }

    // ─────────────────────────────────────────────────────────────
    // Due-ness and status
    // ─────────────────────────────────────────────────────────────

    /** The database clock — the only "now" this class trusts. */
    public function now(): string
    {
        try {
            $now = $this->conn->query('SELECT NOW()')->fetchColumn();
            if ($now) {
                return (string) $now;
            }
        } catch (Throwable $e) {
            DbError::capture($e, 'learning cron: clock');
        }

        return date('Y-m-d H:i:s');
    }

    /** Is the job's current slot still unrun? A live or finished run in the slot closes it. */
    public function isDue(string $job): bool
    {
        if (!self::isJob($job)) {
            return false;
        }

        $slot = self::currentSlot($job, $this->now());
        if ($slot === null) {
            return false;
        }

        $last = $this->lastRun($job);

        return $last === null || (string) $last['started_at'] < $slot;
    }

    /**
     * Everything the admin page shows about a job: its schedule, whether it is due, and
     * how the last runs went.
     *
     * @return array{job: string, label: string, script: string|null, description: string,
     *               schedule: string, due: bool, slot: string|null, next_due: string|null,
     *               last: array|null, last_success: array|null, recent: array, running: bool,
     *               available: bool, error: string|null}
     */
    public function getStatus(string $job): array
    {
        $status = [
            'job'          => $job,
            'label'        => self::jobLabel($job),
            'script'       => self::jobScript($job),
            'description'  => (string) (self::JOBS[$job]['description'] ?? ''),
            'schedule'     => self::scheduleLabel($job),
            'due'          => false,
            'slot'         => null,
            'next_due'     => null,
            'last'         => null,
            'last_success' => null,
            'recent'       => [],
            'running'      => false,
            'available'    => true,
            'error'        => null,
        ];

        if (!self::isJob($job)) {
            $status['available'] = false;
            $status['error'] = 'Unknown job.';

            return $status;
        }

        try {
            $now = $this->now();
            $status['slot'] = self::currentSlot($job, $now);
            $status['next_due'] = self::nextSlotAfter($job, $now);
            $status['recent'] = $this->recentRuns($job, self::STATUS_RUNS);
            $status['last'] = $status['recent'][0] ?? null;
            $status['last_success'] = $this->lastRun($job, 'success');
            $status['running'] = $status['last'] !== null && (string) $status['last']['status'] === 'running';
            $status['due'] = $status['slot'] !== null
                && ($status['last'] === null || (string) $status['last']['started_at'] < $status['slot']);
        } catch (Throwable $e) {
            // Almost always the migration has not been run on this database yet.
            DbError::capture($e, "learning cron: status of {$job}");
            $status['available'] = false;
            $status['error'] = 'Run history is unavailable (is ld_cron_run migrated?).';
        }

        return $status;
    }

    /**
     * The most recent booked run, optionally the most recent one with a given status.
     *
     * @return array<string, mixed>|null
     */
    public function lastRun(string $job, ?string $status = null): ?array
    {
        $runs = $this->recentRuns($job, 1, $status);

        return $runs[0] ?? null;
    }

    /**
     * Recent runs, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentRuns(string $job, int $limit = 10, ?string $status = null): array
    {
        $limit = max(1, min($limit, 100));
        $where = $status !== null ? ' AND status = :status' : '';

        try {
            $stmt = $this->conn->prepare(
                'SELECT id, job, trigger_type, status, started_at, finished_at, duration_ms,'
                . ' records_processed, message, actor_id'
                . ' FROM ld_cron_run WHERE job = :job' . $where
                . ' ORDER BY started_at DESC, id DESC LIMIT ' . $limit
            );
            $stmt->execute($status !== null
                ? [':job' => $job, ':status' => $status]
                : [':job' => $job]);
        } catch (Throwable $e) {
            DbError::capture($e, "learning cron: runs of {$job}");
            return [];
        }

        $runs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $runs[] = [
                'id'                => (int) $row['id'],
                'job'               => (string) $row['job'],
                'trigger_type'      => (string) $row['trigger_type'],
                'status'            => (string) $row['status'],
                'started_at'        => (string) $row['started_at'],
                'finished_at'       => $row['finished_at'] !== null ? (string) $row['finished_at'] : null,
                'duration_ms'       => $row['duration_ms'] !== null ? (int) $row['duration_ms'] : null,
                'records_processed' => $row['records_processed'] !== null ? (int) $row['records_processed'] : null,
                'message'           => $row['message'] !== null ? (string) $row['message'] : null,
                'actor_id'          => $row['actor_id'] !== null ? (int) $row['actor_id'] : null,
            ];
        }

        return $runs;
    }

    // ─────────────────────────────────────────────────────────────
    // Running
    // ─────────────────────────────────────────────────────────────

    /**
     * Run a job and book it in ld_cron_run.
     *
     * @param string $trigger 'scheduled' | 'manual' | 'cli'
     * @param int    $actorId employee who asked for a manual run, 0 for a scheduled one
     * @return array{success: bool, message: string, records_processed: int|null,
     *               duration_ms: int|null, run_id: int|null, status: array}
     */
    public function run(string $job, string $trigger = 'scheduled', int $actorId = 0): array
    {
        if (!self::isJob($job)) {
            return [
                'success' => false,
                'message' => 'Unknown scheduled job.',
                'records_processed' => null,
                'duration_ms' => null,
                'run_id' => null,
                'status' => $this->getStatus($job),
            ];
        }

        $trigger = in_array($trigger, ['scheduled', 'manual', 'cli'], true) ? $trigger : 'manual';
        $now = $this->now();

        // Never two runs at once. A row that has sat in 'running' past the grace period
        // belongs to a process that died, so it is closed out instead of blocking the job.
        $active = $this->lastRun($job, 'running');
        if ($active !== null) {
            if ((string) $active['started_at'] >= date('Y-m-d H:i:s', strtotime($now) - self::STALE_RUN_SECONDS)) {
                return [
                    'success' => false,
                    'message' => self::jobLabel($job) . ' is already running.',
                    'records_processed' => null,
                    'duration_ms' => null,
                    'run_id' => (int) $active['id'],
                    'status' => $this->getStatus($job),
                ];
            }

            $this->closeRun((int) $active['id'], 'failed', 0, null, 'Interrupted — the process that started it never finished.');
        }

        $runId = $this->startRun($job, $trigger, $actorId, $now);
        $startedAt = microtime(true);

        try {
            $result = $this->execute($job);
            $processed = isset($result['processed']) ? (int) $result['processed'] : null;
            $message = (string) ($result['message'] ?? 'Completed.');
            $duration = $this->durationMs($startedAt);

            $this->closeRun($runId, 'success', $duration, $processed, $message);

            return [
                'success' => true,
                'message' => $message,
                'records_processed' => $processed,
                'duration_ms' => $duration,
                'run_id' => $runId,
                'status' => $this->getStatus($job),
            ];
        } catch (Throwable $e) {
            $duration = $this->durationMs($startedAt);
            $this->closeRun($runId, 'failed', $duration, null, 'Failed: ' . $e->getMessage());
            error_log('[learning] cron job ' . $job . ' failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => self::jobLabel($job) . ' failed: ' . $e->getMessage(),
                'records_processed' => null,
                'duration_ms' => $duration,
                'run_id' => $runId,
                'status' => $this->getStatus($job),
            ];
        }
    }

    /**
     * Run every job that is due, as cron/run-due.php does.
     *
     * @return array<int, array{job: string, due: bool, result: array|null}>
     */
    public function runDue(string $trigger = 'scheduled'): array
    {
        $out = [];

        foreach (self::jobIds() as $job) {
            $due = $this->isDue($job);
            $out[] = [
                'job' => $job,
                'due' => $due,
                'result' => $due ? $this->run($job, $trigger) : null,
            ];
        }

        return $out;
    }

    /** Dispatch to the job's runner. Kept separate so run() only books and reports. */
    private function execute(string $job): array
    {
        $runner = self::JOBS[$job]['runner'] ?? null;
        if (!is_callable($runner)) {
            throw new RuntimeException('No runner is registered for ' . $job . '.');
        }

        $result = $runner($this->conn);

        return is_array($result) ? $result : [];
    }

    private function startRun(string $job, string $trigger, int $actorId, string $now): ?int
    {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO ld_cron_run (job, trigger_type, status, started_at, actor_id)'
                . " VALUES (:job, :trigger, 'running', :started_at, :actor_id)"
            );
            $stmt->execute([
                ':job'        => $job,
                ':trigger'    => $trigger,
                ':started_at' => $now,
                ':actor_id'   => $actorId > 0 ? $actorId : null,
            ]);

            return (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            // Booking is best-effort: the snapshot itself still has to run.
            DbError::capture($e, "learning cron: start {$job}");
            error_log('[learning] cron run could not be booked: ' . $e->getMessage());

            return null;
        }
    }

    private function closeRun(?int $runId, string $status, int $duration, ?int $processed, string $message): void
    {
        if ($runId === null || $runId <= 0) {
            return;
        }

        try {
            $stmt = $this->conn->prepare(
                'UPDATE ld_cron_run SET status = :status, finished_at = NOW(), duration_ms = :duration,'
                . ' records_processed = :processed, message = :message WHERE id = :id'
            );
            $stmt->execute([
                ':status'    => $status,
                ':duration'  => $duration,
                ':processed' => $processed,
                ':message'   => mb_substr($message, 0, 255),
                ':id'        => $runId,
            ]);
        } catch (Throwable $e) {
            DbError::capture($e, 'learning cron: finish run');
            error_log('[learning] cron run could not be closed: ' . $e->getMessage());
        }
    }

    private function durationMs(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }
}
