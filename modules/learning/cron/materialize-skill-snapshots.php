<?php
/**
 * Cron: materialize-skill-snapshots.php
 * Materializes per-learner skill snapshots into ld_skill_snapshot.
 * Runs via OS cron (daily): php <app root>/modules/learning/cron/materialize-skill-snapshots.php
 *
 * Implements Gap 7 (+ Gap 6) of ld-tables-gaps-improvements.md:
 *   - ld_skill_snapshot existed in schema but was never populated.
 *   - Adds position_id / department_id context from em_employees.
 *
 * Semantics per learner (learner_id present in ld_enrollment):
 *   acquired  = skills on COMPLETED enrollments (course skills) + skills of COMPLETED modules (progress)
 *   gap       = all skills of the learner's courses/modules MINUS acquired
 *   total     = distinct skills across all of the learner's enrollments
 * History is preserved: one row per (learner_id, snapshot_date); running on a new day adds a row.
 */

// ── Configuration ──────────────────────────────────────────────
$LOG_FILE = dirname(__FILE__) . '/skill-snapshot.log';

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
require_once dirname(__FILE__, 2) . '/classes/progress.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    logMsg('ERROR: Database connection failed: ' . $e->getMessage());
    exit(1);
}

logMsg('=== Skill snapshot cron started ===');

// Learners = anyone with any enrollment activity
$learners = $pdo->query('SELECT DISTINCT learner_id FROM ld_enrollment')->fetchAll(PDO::FETCH_COLUMN);

if (empty($learners)) {
    logMsg('No learners with enrollment activity — nothing to snapshot.');
    exit(0);
}

$snapshotDate = date('Y-m-d');
$inserted = 0;

// Prepared statements (reused per learner)
$courseSkillsAcquired = $pdo->prepare(
    "SELECT DISTINCT s.id, s.name
     FROM ld_enrollment e
     JOIN ld_course_skill cs ON cs.course_id = e.course_id
     JOIN ld_skill s ON s.id = cs.skill_id
     WHERE e.learner_id = :lid AND e.status = 'completed'"
);
// Module-derived acquired skills. A module is complete when all of its active
// lessons/quizzes are complete — there is no ld_progress row with item_type = 'module',
// so the previous version of this query could never return a row.
$moduleCompletedSql = Progress::sqlModuleCompleted('m', 'e.id');
$moduleSkillsAcquired = $pdo->prepare(
    "SELECT DISTINCT s.id, s.name
     FROM ld_enrollment e
     JOIN ld_module m ON m.course_id = e.course_id AND m.status = 'active'
     JOIN ld_module_skill ms ON ms.module_id = m.id
     JOIN ld_skill s ON s.id = ms.skill_id
     WHERE e.learner_id = :lid
       AND " . $moduleCompletedSql
);
$courseSkillsAll = $pdo->prepare(
    "SELECT DISTINCT s.id, s.name
     FROM ld_enrollment e
     JOIN ld_course_skill cs ON cs.course_id = e.course_id
     JOIN ld_skill s ON s.id = cs.skill_id
     WHERE e.learner_id = :lid"
);
$moduleSkillsAll = $pdo->prepare(
    "SELECT DISTINCT s.id, s.name
     FROM ld_enrollment e
     JOIN ld_module m ON m.course_id = e.course_id AND m.status = 'active'
     JOIN ld_module_skill ms ON ms.module_id = m.id
     JOIN ld_skill s ON s.id = ms.skill_id
     WHERE e.learner_id = :lid"
);
$employeeContext = $pdo->prepare(
    'SELECT department_id, position_id FROM em_employees WHERE employee_id = :lid LIMIT 1'
);
// External skills from Employee Management (proficiency-tagged) — matched to ld_skill by name.
// These count toward both total and acquired: an externally-held skill is a real acquired skill.
$externalSkills = $pdo->prepare(
    "SELECT s.id, s.name
     FROM employee_skills es
     JOIN ld_skill s ON LOWER(s.name) = LOWER(es.skill_name)
     WHERE es.employee_id = :lid"
);

// ld_skill_snapshot has UNIQUE (learner_id, snapshot_date) — upsert so re-runs replace today's row
$insertStmt = $pdo->prepare(
    'INSERT INTO ld_skill_snapshot
        (learner_id, position_id, department_id, total_skills, acquired_count, gap_count,
         acquired_skills, gap_skills, snapshot_date)
     VALUES
        (:learner_id, :position_id, :department_id, :total_skills, :acquired_count, :gap_count,
         :acquired_skills, :gap_skills, :snapshot_date)
     ON DUPLICATE KEY UPDATE
        position_id = VALUES(position_id),
        department_id = VALUES(department_id),
        total_skills = VALUES(total_skills),
        acquired_count = VALUES(acquired_count),
        gap_count = VALUES(gap_count),
        acquired_skills = VALUES(acquired_skills),
        gap_skills = VALUES(gap_skills)'
);

foreach ($learners as $learnerId) {
    $learnerId = (int) $learnerId;

    $courseSkillsAcquired->execute([':lid' => $learnerId]);
    $moduleSkillsAcquired->execute([':lid' => $learnerId]);
    $courseSkillsAll->execute([':lid' => $learnerId]);
    $moduleSkillsAll->execute([':lid' => $learnerId]);
    $externalSkills->execute([':lid' => $learnerId]);

    // id => name maps
    $acquired = [];
    foreach (array_merge($courseSkillsAcquired->fetchAll(), $moduleSkillsAcquired->fetchAll()) as $row) {
        $acquired[(int) $row['id']] = $row['name'];
    }
    $all = [];
    foreach (array_merge($courseSkillsAll->fetchAll(), $moduleSkillsAll->fetchAll()) as $row) {
        $all[(int) $row['id']] = $row['name'];
    }

    // Merge external skills into both total and acquired (E1 — consume employee_skills)
    foreach ($externalSkills->fetchAll() as $row) {
        $all[(int) $row['id']] = $row['name'];
        $acquired[(int) $row['id']] = $row['name'];
    }

    $gap = array_diff_key($all, $acquired);

    $employeeContext->execute([':lid' => $learnerId]);
    $emp = $employeeContext->fetch(PDO::FETCH_ASSOC);

    $insertStmt->execute([
        ':learner_id'      => $learnerId,
        ':position_id'     => $emp['position_id'] ?? null,
        ':department_id'   => $emp['department_id'] ?? null,
        ':total_skills'    => count($all),
        ':acquired_count'  => count($acquired),
        ':gap_count'       => count($gap),
        ':acquired_skills' => json_encode(array_values($acquired)),
        ':gap_skills'      => json_encode(array_values($gap)),
        ':snapshot_date'   => $snapshotDate,
    ]);
    $inserted++;
}

logMsg("Inserted $inserted skill snapshots for date $snapshotDate");
logMsg('=== Skill snapshot cron complete ===');