<?php
/**
 * Cron: materialize-engagement-snapshots.php
 * Computes per-learner engagement & turnover-risk snapshots into ld_engagement_snapshot.
 * Runs via OS cron (daily): php <app root>/modules/learning/cron/materialize-engagement-snapshots.php
 *
 * Implements Gap 1 of ld-tables-gaps-improvements.md:
 *   - L&D had no table storing engagement/turnover-risk signals.
 *   - This snapshot feeds wfa_risk_assessment / wfa_at_risk_employees_summary via the
 *     outbound get-workforce-analytics.php endpoint.
 *
 * Risk score (0-100, capped):
 *   +30  no activity in 30+ days
 *   +15  more than 2 courses in progress
 *   +15  abandoned quizzes (>24h old, still in_progress)
 *   +15  expired active certificates
 *   +15  average grade below 70
 *   +10  no learning activity at all (cannot be measured)
 *
 * Idempotent per (learner_id, snapshot_date) — safe to run multiple times a day.
 */

// ── Configuration ──────────────────────────────────────────────
$LOG_FILE = dirname(__FILE__) . '/engagement-snapshot.log';

// ── Helpers ────────────────────────────────────────────────────
function logMsg(string $msg): void {
    global $LOG_FILE;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    }
}

// ── Connect to DB ──────────────────────────────────────────────
require_once dirname(__FILE__, 4) . '/database/db.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    logMsg('ERROR: Database connection failed: ' . $e->getMessage());
    exit(1);
}

logMsg('=== Engagement snapshot cron started ===');

// Learners = anyone with any enrollment activity
$learners = $pdo->query('SELECT DISTINCT learner_id FROM ld_enrollment')->fetchAll(PDO::FETCH_COLUMN);

if (empty($learners)) {
    logMsg('No learners with enrollment activity — nothing to snapshot.');
    exit(0);
}

$today     = date('Y-m-d');
$ytdStart  = date('Y-01-01');
$nowMinus1d = date('Y-m-d H:i:s', strtotime('-24 hours'));

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
        $daysSinceLastActivity = max(0, (int) floor((time() - strtotime($lastActivity)) / 86400));
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

logMsg("Upserted engagement snapshots for $processed learners on $today");
logMsg('=== Engagement snapshot cron complete ===');