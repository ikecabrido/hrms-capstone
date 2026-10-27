<?php
/**
 * EngagementSnapshot — computes the per-learner engagement / turnover-risk
 * snapshot that lands in ld_engagement_snapshot.
 *
 * Implements Gap 1 of rainz/integration mds/ld-tables-gaps-improvements.md:
 *   - L&D had no table storing engagement/turnover-risk signals.
 *   - This snapshot feeds wfa_risk_assessment / wfa_at_risk_employees_summary via the
 *     outbound api/outbound/get-workforce-analytics.php endpoint, and the risk panel on
 *     both Workforce Integration pages reads it straight back.
 *
 * Risk score (0-100, capped):
 *   +30  no activity in 30+ days
 *   +15  more than 2 courses in progress
 *   +15  abandoned quizzes (>24h old, still in_progress)
 *   +15  expired active certificates
 *   +15  average grade below 70
 *   +10  no learning activity at all (cannot be measured)
 *   +20  an exit case is filed against the employee (Exit module)
 *
 * Idempotent per (learner_id, snapshot_date) — safe to run multiple times a day.
 *
 * The work used to live in cron/materialize-engagement-snapshots.php, which is now a
 * thin wrapper, so the same code can be run three ways without a second copy:
 *   cron/materialize-engagement-snapshots.php   the documented CLI entry point
 *   cron/run-due.php                            any job that is due (OS schedule)
 *   LearningCron::run('engagement_snapshot')    the admin "run now" / auto-run path
 * The clock comes from the database (NOW()) so a snapshot_date and the run's row in
 * ld_cron_run cannot land on different days when PHP and MySQL are set to different
 * time zones.
 */
require_once dirname(__FILE__, 4) . '/database/db.php';

class EngagementSnapshot
{
    /** Where the human-readable run log lives (appended, never rotated here). */
    public static function logFile(): string
    {
        return dirname(__FILE__, 2) . '/cron/engagement-snapshot.log';
    }

    /**
     * Build the snapshot for every learner with enrolment activity.
     *
     * @param PDO|null $pdo  Reuse a connection; one is opened when omitted.
     * @return array{date: string, processed: int, message: string}
     * @throws Throwable when the database cannot be reached or read — the caller
     *                   (LearningCron) records that as a failed run.
     */
    public static function run(?PDO $pdo = null, ?string $clock = null): array
    {
        if (!$pdo instanceof PDO) {
            $pdo = (new Database())->getConnection();
        }

        // One clock for the whole run — the database's, which is what created_at uses.
        $clock = $clock ?? self::clock($pdo);
        $today = substr($clock, 0, 10);

        self::log('=== Engagement snapshot cron started ===', $clock, true);

        // Learners = anyone with any enrollment activity
        $learners = $pdo->query('SELECT DISTINCT learner_id FROM ld_enrollment')->fetchAll(PDO::FETCH_COLUMN);

        if (empty($learners)) {
            $message = 'No learners with enrollment activity — nothing to snapshot.';
            self::log($message, $clock, true);

            return ['date' => $today, 'processed' => 0, 'message' => $message];
        }

        $ytdStart   = substr($clock, 0, 4) . '-01-01';
        $nowMinus1d = date('Y-m-d H:i:s', strtotime($clock) - 86400);

        // Prepared statements (reused per learner)
        $stmtActiveDays = $pdo->prepare(
            "SELECT COUNT(DISTINCT d) FROM (
                SELECT DATE(started_at) AS d FROM ld_quiz_session
                 WHERE learner_id = :lid AND started_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION
                SELECT DATE(joined_at) AS d FROM ld_conference_attendance
                 WHERE learner_id = :lid AND joined_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                UNION
                SELECT DATE(created_at) AS d FROM ld_enrollment
                 WHERE learner_id = :lid AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             ) t"
        );
        $stmtInProgress = $pdo->prepare(
            "SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status IN ('enrolled','in_progress')"
        );
        $stmtCompletedYtd = $pdo->prepare(
            "SELECT COUNT(*) FROM ld_enrollment WHERE learner_id = :lid AND status = 'completed' AND completed_at >= :ytd"
        );
        $stmtAvgScore = $pdo->prepare(
            'SELECT AVG(final_score) FROM ld_grade WHERE learner_id = :lid'
        );
        $stmtQuizzesStarted = $pdo->prepare(
            "SELECT COUNT(*) FROM ld_quiz_session WHERE learner_id = :lid AND status IN ('submitted','expired')"
        );
        $stmtQuizzesAbandoned = $pdo->prepare(
            "SELECT COUNT(*) FROM ld_quiz_session WHERE learner_id = :lid AND status = 'in_progress' AND started_at < :cutoff"
        );
        $stmtExpiredCerts = $pdo->prepare(
            "SELECT COUNT(*) FROM ld_certificate WHERE learner_id = :lid AND status = 'active' AND valid_until < CURDATE()"
        );
        // Exit cases filed against the employee (X1 — turnover risk from the Exit module)
        $stmtExitCase = $pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM exit_resignations
                  WHERE employee_id = :lid AND status NOT IN ('rejected','cancelled','archived')) +
                (SELECT COUNT(*) FROM exit_terminations
                  WHERE employee_id = :lid AND status NOT IN ('rejected','cancelled','archived')) +
                (SELECT COUNT(*) FROM exit_interviews
                  WHERE employee_id = :lid AND status NOT IN ('cancelled','archived'))
             AS exit_cases"
        );
        $stmtLastActivity = $pdo->prepare(
            "SELECT GREATEST(
                COALESCE(MAX(e.last_accessed_at), '1970-01-01'),
                COALESCE(MAX(e.completed_at),    '1970-01-01'),
                COALESCE(MAX(e.created_at),      '1970-01-01'),
                COALESCE(MAX(qs.started_at),     '1970-01-01'),
                COALESCE(MAX(qs.submitted_at),   '1970-01-01'),
                COALESCE(MAX(ca.joined_at),      '1970-01-01'),
                COALESCE(MAX(ce.issued_at),      '1970-01-01')
             ) AS last_activity
             FROM ld_enrollment e
             LEFT JOIN ld_quiz_session qs ON qs.learner_id = e.learner_id
             LEFT JOIN ld_conference_attendance ca ON ca.learner_id = e.learner_id
             LEFT JOIN ld_certificate ce ON ce.learner_id = e.learner_id
             WHERE e.learner_id = :lid"
        );

        $upsertStmt = $pdo->prepare(
            'INSERT INTO ld_engagement_snapshot
                (learner_id, snapshot_date, active_days_30, courses_in_progress, courses_completed_ytd,
                 avg_score, quizzes_started, quizzes_abandoned, days_since_last_activity,
                 expired_certificates, risk_score, risk_factors)
             VALUES
                (:learner_id, :snapshot_date, :active_days_30, :courses_in_progress, :courses_completed_ytd,
                 :avg_score, :quizzes_started, :quizzes_abandoned, :days_since_last_activity,
                 :expired_certificates, :risk_score, :risk_factors)
             ON DUPLICATE KEY UPDATE
                active_days_30 = VALUES(active_days_30),
                courses_in_progress = VALUES(courses_in_progress),
                courses_completed_ytd = VALUES(courses_completed_ytd),
                avg_score = VALUES(avg_score),
                quizzes_started = VALUES(quizzes_started),
                quizzes_abandoned = VALUES(quizzes_abandoned),
                days_since_last_activity = VALUES(days_since_last_activity),
                expired_certificates = VALUES(expired_certificates),
                risk_score = VALUES(risk_score),
                risk_factors = VALUES(risk_factors)'
        );

        $processed = 0;
        $now = strtotime($clock);

        foreach ($learners as $learnerId) {
            $learnerId = (int) $learnerId;

            $stmtActiveDays->execute([':lid' => $learnerId]);
            $activeDays30 = (int) $stmtActiveDays->fetchColumn();

            $stmtInProgress->execute([':lid' => $learnerId]);
            $coursesInProgress = (int) $stmtInProgress->fetchColumn();

            $stmtCompletedYtd->execute([':lid' => $learnerId, ':ytd' => $ytdStart]);
            $coursesCompletedYtd = (int) $stmtCompletedYtd->fetchColumn();

            $stmtAvgScore->execute([':lid' => $learnerId]);
            $avgScoreRaw = $stmtAvgScore->fetchColumn();
            $avgScore = $avgScoreRaw !== null ? round((float) $avgScoreRaw, 2) : null;

            $stmtQuizzesStarted->execute([':lid' => $learnerId]);
            $quizzesStarted = (int) $stmtQuizzesStarted->fetchColumn();

            $stmtQuizzesAbandoned->execute([':lid' => $learnerId, ':cutoff' => $nowMinus1d]);
            $quizzesAbandoned = (int) $stmtQuizzesAbandoned->fetchColumn();

            $stmtExpiredCerts->execute([':lid' => $learnerId]);
            $expiredCerts = (int) $stmtExpiredCerts->fetchColumn();

            $stmtExitCase->execute([':lid' => $learnerId]);
            $exitCases = (int) $stmtExitCase->fetchColumn();

            $stmtLastActivity->execute([':lid' => $learnerId]);
            $lastActivityRaw = $stmtLastActivity->fetchColumn();
            $lastActivity = ($lastActivityRaw && $lastActivityRaw !== '1970-01-01 00:00:00' && $lastActivityRaw !== '1970-01-01')
                ? $lastActivityRaw : null;

            $daysSinceLastActivity = null;
            if ($lastActivity !== null) {
                $daysSinceLastActivity = max(0, (int) floor(($now - strtotime($lastActivity)) / 86400));
            }

            // ── Risk score ────────────────────────────────────────────
            $factors = [];
            $risk = 0;

            if ($lastActivity === null) {
                $risk += 10;
                $factors[] = 'no_learning_activity';
            } elseif ($daysSinceLastActivity > 30) {
                $risk += 30;
                $factors[] = 'inactive_30_days';
            }
            if ($coursesInProgress > 2) {
                $risk += 15;
                $factors[] = 'many_courses_in_progress';
            }
            if ($quizzesAbandoned > 0) {
                $risk += 15;
                $factors[] = 'abandoned_quizzes';
            }
            if ($expiredCerts > 0) {
                $risk += 15;
                $factors[] = 'expired_certificates';
            }
            if ($avgScore !== null && $avgScore < 70) {
                $risk += 15;
                $factors[] = 'below_passing_average';
            }
            if ($exitCases > 0) {
                $risk += 20;
                $factors[] = 'exit_case_filed';
            }

            $riskScore = min(100, $risk);

            $upsertStmt->execute([
                ':learner_id'              => $learnerId,
                ':snapshot_date'           => $today,
                ':active_days_30'          => $activeDays30,
                ':courses_in_progress'     => $coursesInProgress,
                ':courses_completed_ytd'   => $coursesCompletedYtd,
                ':avg_score'               => $avgScore,
                ':quizzes_started'         => $quizzesStarted,
                ':quizzes_abandoned'       => $quizzesAbandoned,
                ':days_since_last_activity'=> $daysSinceLastActivity,
                ':expired_certificates'    => $expiredCerts,
                ':risk_score'              => $riskScore,
                ':risk_factors'            => json_encode($factors),
            ]);
            $processed++;
        }

        $message = "Upserted engagement snapshots for {$processed} learners on {$today}";
        self::log($message, $clock, true);
        self::log('=== Engagement snapshot cron complete ===', $clock, true);

        return ['date' => $today, 'processed' => $processed, 'message' => $message];
    }

    /** The database's current time, so the run and its rows share one clock. */
    public static function clock(?PDO $pdo = null): string
    {
        try {
            if (!$pdo instanceof PDO) {
                $pdo = (new Database())->getConnection();
            }

            return (string) $pdo->query('SELECT NOW()')->fetchColumn();
        } catch (Throwable $e) {
            // No clock to borrow — the PHP one is still better than no timestamp.
            return date('Y-m-d H:i:s');
        }
    }

    /** Append one run log line; CLI callers also see it on stdout. */
    private static function log(string $message, ?string $clock = null, bool $echo = false): void
    {
        $line = '[' . ($clock ?? date('Y-m-d H:i:s')) . '] ' . $message . PHP_EOL;

        @file_put_contents(self::logFile(), $line, FILE_APPEND | LOCK_EX);

        if ($echo && PHP_SAPI === 'cli') {
            echo $message . PHP_EOL;
        }
    }
}
