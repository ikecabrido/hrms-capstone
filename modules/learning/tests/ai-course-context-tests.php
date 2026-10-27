<?php
/**
 * AICourseContext integration tests.
 *
 * These tests use the local Learning database and run inside a transaction, so
 * temporary skill snapshots are rolled back. They exercise the existing seed
 * trainees (1001 has no enrollments; 1002 and 1006 have course evidence).
 *
 *     php modules/learning/tests/ai-course-context-tests.php
 */

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__) . '/classes/AICourseContext.php';

$passed = 0;
$failed = 0;

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS  {$name}\n";
        return;
    }

    $failed++;
    echo "FAIL  {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

function collectKeys(mixed $value, array &$keys): void
{
    if (!is_array($value)) {
        return;
    }
    foreach ($value as $key => $child) {
        if (is_string($key)) {
            $keys[$key] = true;
        }
        collectKeys($child, $keys);
    }
}

function hasAnyKey(array $value, array $forbidden): bool
{
    $keys = [];
    collectKeys($value, $keys);
    foreach ($forbidden as $key) {
        if (isset($keys[$key])) {
            return true;
        }
    }

    return false;
}

function insertSnapshot(PDO $pdo, int $learnerId, string $date, array $acquired, array $gaps): void
{
    $pdo->prepare(
        'DELETE FROM ld_skill_snapshot WHERE learner_id = :learner_id AND snapshot_date = :snapshot_date'
    )->execute([':learner_id' => $learnerId, ':snapshot_date' => $date]);

    $stmt = $pdo->prepare(
        'INSERT INTO ld_skill_snapshot
            (learner_id, snapshot_date, total_skills, acquired_count, gap_count, acquired_skills, gap_skills)
         VALUES
            (:learner_id, :snapshot_date, :total_skills, :acquired_count, :gap_count, :acquired_skills, :gap_skills)'
    );
    $stmt->execute([
        ':learner_id' => $learnerId,
        ':snapshot_date' => $date,
        ':total_skills' => count($acquired) + count($gaps),
        ':acquired_count' => count($acquired),
        ':gap_count' => count($gaps),
        ':acquired_skills' => json_encode(array_values($acquired)),
        ':gap_skills' => json_encode(array_values($gaps)),
    ]);
}

$pdo = (new Database())->getConnection();
$context = new AICourseContext($pdo);
$pdo->beginTransaction();

try {
    // Search is active-only and omits contact fields.
    $search = $context->searchTrainees('Cheska');
    $trainee = $search['trainees'][0] ?? [];
    check('search returns an active trainee', ($trainee['id'] ?? 0) === 1005);
    check('search omits email', !array_key_exists('email', $trainee));
    check('search omits phone', !array_key_exists('mobile_no', $trainee) && !array_key_exists('phone_no', $trainee));
    check('search omits employee code', !array_key_exists('employee_code', $trainee));
    check('search exposes a profile link', str_contains((string) ($trainee['profile_url'] ?? ''), 'id=1005'));

    $shortSearch = $context->searchTrainees('C');
    check('short search returns no rows', $shortSearch['trainees'] === []);
    check('short search explains the minimum', str_contains($shortSearch['message'], 'two characters'));

    $missingSearch = $context->searchTrainees('zzzz-no-such-trainee');
    check('unknown search returns no rows', $missingSearch['trainees'] === []);
    check('unknown search is explained', $missingSearch['message'] !== '');

    // A trainee with no enrollments must produce limited evidence, not invented gaps.
    $empty = $context->build(1001);
    check('empty trainee context succeeds', $empty['success'] === true);
    check('empty trainee is marked limited', $empty['context']['limited_evidence'] === true);
    check('empty trainee has no gaps', $empty['context']['skills']['gaps'] === []);
    check('empty trainee has no acquired skills', $empty['context']['skills']['acquired'] === []);
    check('empty trainee warns about missing enrollments', (bool) array_filter(
        $empty['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, 'No course enrollment evidence')
    ));
    check('empty trainee warns about missing quiz evidence', (bool) array_filter(
        $empty['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, 'No quiz or evaluation attempts')
    ));

    // Live course evidence, duplicate seed enrollments, and gap calculation.
    $live = $context->build(1002);
    check('live context succeeds', $live['success'] === true);
    check('live context uses live skill data', $live['context']['skills']['source'] === 'live');
    check('live context is not limited', $live['context']['limited_evidence'] === false);
    check('live context finds Coaching gap', in_array('Coaching', $live['context']['skills']['gaps'], true));
    check('live context finds Leadership gap', in_array('Leadership', $live['context']['skills']['gaps'], true));
    check('live context finds Data Privacy as acquired', in_array('Data Privacy', $live['context']['skills']['acquired'], true));
    $catalogByName = [];
    foreach (($live['context']['skills']['catalog'] ?? []) as $catalogSkill) {
        if (is_array($catalogSkill) && isset($catalogSkill['name'])) {
            $catalogByName[(string) $catalogSkill['name']] = (int) ($catalogSkill['skill_id'] ?? 0);
        }
    }
    check('skill catalog includes the Coaching gap id', ($catalogByName['Coaching'] ?? 0) > 0);
    check('skill catalog includes the Leadership gap id', ($catalogByName['Leadership'] ?? 0) > 0);
    check('skill catalog omits acquired-only skills', !isset($catalogByName['Data Privacy']));
    check('duplicate completed courses are merged', count($live['context']['completed_courses']) === 1);
    check('duplicate current enrollments are merged', count($live['context']['current_courses']) === 1);
    check('merged current course keeps both skills', count($live['context']['current_courses'][0]['skills']) === 2);
    check('profile link is present', str_contains((string) $live['meta']['trainee']['profile_url'], 'id=1002'));
    check('skill options expose active ids', (int) ($live['meta']['skill_options'][0]['id'] ?? 0) > 0);

    // Prompt-safe context must not carry identifiers or contact/salary fields.
    $employeeEmail = (string) $pdo->query('SELECT email FROM em_employees WHERE employee_id = 1002')->fetchColumn();
    $contextJson = json_encode($live['context'], JSON_UNESCAPED_SLASHES);
    $forbiddenKeys = [
        'employee_id', 'employee_code', 'id', 'email', 'mobile_no', 'phone_no',
        'current_address', 'permanent_address', 'salary', 'birth_date', 'performance_rating',
    ];
    check('prompt context omits forbidden keys', !hasAnyKey($live['context'], $forbiddenKeys));
    check('prompt context omits the employee email value', $employeeEmail === '' || !str_contains($contextJson, $employeeEmail));
    check('quiz context omits session ids', !hasAnyKey($live['context']['quiz_performance'] ?? [], ['id', 'reference_id', 'question_order']));
    check('program context omits feedback', !hasAnyKey($live['context']['program_assessments'] ?? [], ['feedback', 'recommendations']));

    // Selected skills are validated against active ld_skill rows.
    $selected = $context->build(1002, [26, 999999]);
    check('selected active skill is accepted', in_array('Coaching', $selected['context']['skills']['selected'], true));
    check('invalid selected skill is warned', (bool) array_filter(
        $selected['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, '999999')
    ));

    $duplicateSelection = $context->build(1002, [26, 970110]);
    check('duplicate selected skill names are deduplicated', $duplicateSelection['context']['skills']['selected'] === ['Coaching']);

    $nonGapSelection = $context->build(1002, [17]);
    check('non-gap selection is warned', (bool) array_filter(
        $nonGapSelection['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, 'not a current gap')
    ));

    // Evidence exclusion must remove the section and flag it in meta.
    $withoutQuiz = $context->build(1002, [], ['quiz_performance']);
    check('excluded quiz section is null', $withoutQuiz['context']['quiz_performance'] === null);
    $quizEvidence = array_values(array_filter(
        $withoutQuiz['meta']['evidence'],
        static fn(array $item): bool => $item['key'] === 'quiz_performance'
    ));
    check('excluded quiz evidence is marked included=false', ($quizEvidence[0]['included'] ?? true) === false);

    $withoutCurrent = $context->build(1002, [], ['current_courses']);
    check('excluded current courses are empty', $withoutCurrent['context']['current_courses'] === []);
    check('excluded current courses keep completed courses', count($withoutCurrent['context']['completed_courses']) === 1);

    $withoutCourses = $context->build(1002, [], ['completed_courses', 'current_courses']);
    check('excluding both course sources leaves limited evidence', $withoutCourses['context']['limited_evidence'] === true);
    check('excluding both course sources leaves no course rows', $withoutCourses['context']['completed_courses'] === []
        && $withoutCourses['context']['current_courses'] === []);

    $unknownExclusion = $context->build(1002, [], ['not_a_source']);
    check('unknown exclusion is warned', (bool) array_filter(
        $unknownExclusion['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, 'Unknown evidence exclusion')
    ));

    // Snapshot fallback and stale-snapshot handling.
    insertSnapshot($pdo, 1001, '2020-01-01', ['Communication Skills'], ['Data Privacy']);
    $snapshot = $context->build(1001);
    check('snapshot fallback is used without live skills', $snapshot['context']['skills']['source'] === 'snapshot');
    check('snapshot fallback exposes gap', in_array('Data Privacy', $snapshot['context']['skills']['gaps'], true));
    check('snapshot fallback exposes acquired skill', in_array('Communication Skills', $snapshot['context']['skills']['acquired'], true));
    check('stale snapshot is warned', (bool) array_filter(
        $snapshot['context']['warnings'],
        static fn(string $warning): bool => str_contains($warning, 'more than 30 days old')
    ));
    check('snapshot fallback remains limited evidence', $snapshot['context']['limited_evidence'] === true);

    insertSnapshot($pdo, 1002, '2020-01-01', ['Communication Skills'], ['Data Privacy']);
    $liveWins = $context->build(1002);
    check('live data ignores stale snapshot', $liveWins['context']['skills']['source'] === 'live');
    check('live data does not use snapshot gaps', !in_array('Data Privacy', $liveWins['context']['skills']['gaps'], true));

    // Quiz and evaluation aggregation uses sessions and attempts. Derive the
    // expected values from the live fixture instead of hardcoding a snapshot,
    // because a trainee may take another quiz while the suite is running.
    $quiz = $context->build(1006);
    $quizSessionRows = $pdo->query(
        "SELECT status, score
           FROM ld_quiz_session
          WHERE learner_id = 1006
            AND status IN ('submitted', 'expired')
          ORDER BY submitted_at DESC, id DESC
          LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);
    $expectedSessionCount = count($quizSessionRows);
    $expectedScores = [];
    foreach ($quizSessionRows as $sessionRow) {
        if ($sessionRow['status'] === 'submitted' && $sessionRow['score'] !== null) {
            $expectedScores[] = (float) $sessionRow['score'];
        }
    }
    $expectedAverage = $expectedScores !== []
        ? round(array_sum($expectedScores) / count($expectedScores), 1)
        : null;
    $expectedAttemptCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM ld_quiz_attempt WHERE learner_id = 1006'
    )->fetchColumn();
    check(
        'quiz summary counts sessions',
        ($quiz['context']['quiz_performance']['summary']['attempts'] ?? 0) === $expectedSessionCount,
        'expected ' . $expectedSessionCount
    );
    check(
        'quiz summary averages submitted scores',
        ($quiz['context']['quiz_performance']['summary']['average_score'] ?? null) === $expectedAverage,
        'expected ' . var_export($expectedAverage, true)
    );
    check('quiz recent list is bounded', count($quiz['context']['quiz_performance']['recent'] ?? []) <= 5);
    check(
        'quiz attempts are summarized',
        ($quiz['context']['quiz_performance']['quiz_attempts']['count'] ?? 0) === $expectedAttemptCount,
        'expected ' . $expectedAttemptCount
    );

    // Missing or inactive trainees fail cleanly.
    $notFound = $context->build(999999);
    check('unknown trainee is rejected', $notFound['success'] === false);
    $invalidId = $context->build(0);
    check('zero trainee id is rejected', $invalidId['success'] === false);
} finally {
    $pdo->rollBack();
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
