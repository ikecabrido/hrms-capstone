<?php
/**
 * P10 analytics tests.
 *
 *     php modules/learning/tests/ai-analytics-tests.php
 *
 * Creates temporary jobs, verifies the read-only summaries and export rows,
 * and removes every created job in a finally block.
 */

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__) . '/classes/AIAnalytics.php';

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
    echo "FAIL  {$name}" . ($detail !== '' ? " - {$detail}" : '') . "\n";
}

$pdo = (new Database())->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$marker = 'analytics/' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
$jobIds = [];

try {
    $stmt = $pdo->prepare(
        "INSERT INTO ld_ai_course_job
            (created_by, course_owner_id, learner_id, model, prompt_version, status,
             current_step, total_steps, completed_steps, prompt_tokens, completion_tokens,
             estimated_cost_usd, error_message, created_at)
         VALUES
            (1007, 1007, 1005, :model, 'v1', :status, 'complete', 8, 8, :prompt_tokens,
             :completion_tokens, :cost, :error_message, NOW())"
    );

    $rows = [
        ['status' => 'ready', 'prompt_tokens' => 100, 'completion_tokens' => 50, 'cost' => 0.0012, 'error' => null],
        ['status' => 'applied', 'prompt_tokens' => 200, 'completion_tokens' => 80, 'cost' => 0.0025, 'error' => null],
        ['status' => 'failed', 'prompt_tokens' => 30, 'completion_tokens' => 0, 'cost' => 0.0003, 'error' => 'Provider request failed safely.'],
    ];
    foreach ($rows as $index => $row) {
        $stmt->execute([
            ':model' => $marker . '-model-' . $index,
            ':status' => $row['status'],
            ':prompt_tokens' => $row['prompt_tokens'],
            ':completion_tokens' => $row['completion_tokens'],
            ':cost' => $row['cost'],
            ':error_message' => $row['error'],
        ]);
        $jobIds[] = (int) $pdo->lastInsertId();
    }

    $analytics = new AIAnalytics($pdo);
    $dashboard = $analytics->dashboard(30);
    check('dashboard returns the normalized range', (int) ($dashboard['days'] ?? 0) === 30);
    check('dashboard labels the range', ($dashboard['range_label'] ?? '') === 'Last 30 days');
    check('dashboard includes test jobs', (int) ($dashboard['jobs'] ?? 0) >= 3, 'jobs=' . (int) ($dashboard['jobs'] ?? 0));
    check('dashboard includes test cost', (float) ($dashboard['cost'] ?? 0) >= 0.0039, 'cost=' . (float) ($dashboard['cost'] ?? 0));
    check('dashboard includes test tokens', (int) ($dashboard['tokens'] ?? 0) >= 460, 'tokens=' . (int) ($dashboard['tokens'] ?? 0));
    check('dashboard includes applied jobs', (int) ($dashboard['applied'] ?? 0) >= 1);
    check('dashboard includes failures', (int) ($dashboard['failures'] ?? 0) >= 1);
    check('daily series has one row per day', count($dashboard['daily'] ?? []) === 30, 'rows=' . count($dashboard['daily'] ?? []));
    check('daily series ends today', (($dashboard['daily'] ?? [])[29]['day'] ?? '') === date('Y-m-d'));
    check('daily series includes today\'s jobs', (int) (($dashboard['daily'] ?? [])[29]['jobs'] ?? 0) >= 3);
    check('status funnel counts ready', (int) ($dashboard['statuses']['ready'] ?? 0) >= 1);
    check('status funnel counts applied', (int) ($dashboard['statuses']['applied'] ?? 0) >= 1);
    check('status funnel counts failed', (int) ($dashboard['statuses']['failed'] ?? 0) >= 1);
    check('status funnel includes all statuses', count($dashboard['statuses'] ?? []) === 6);
    check('model usage includes test model', count($dashboard['models'] ?? []) >= 1);
    check('recent failures includes the test failure', count($dashboard['recent_failures'] ?? []) >= 1);
    $failureText = '';
    foreach ($dashboard['recent_failures'] as $failure) {
        if ((int) ($failure['id'] ?? 0) === $jobIds[2]) {
            $failureText = (string) ($failure['error_message'] ?? '');
        }
    }
    check('recent failures keeps the safe error', $failureText === 'Provider request failed safely.');

    $invalidRange = $analytics->dashboard(123);
    check('invalid range falls back to 30 days', (int) ($invalidRange['days'] ?? 0) === 30);

    $export = $analytics->exportRows(30);
    $exportIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $export);
    check('export includes test jobs', count(array_intersect($jobIds, $exportIds)) === 3);
    check('export omits prompt and context bodies', !array_key_exists('context_json', $export[0] ?? []) && !array_key_exists('brief_json', $export[0] ?? []));
    check('export includes learner and owner names', array_key_exists('learner_name', $export[0] ?? []) && array_key_exists('owner_name', $export[0] ?? []));
} catch (Throwable $e) {
    check('analytics test completed without an exception', false, $e->getMessage());
} finally {
    if ($jobIds !== []) {
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $pdo->prepare('DELETE FROM ld_ai_course_job WHERE id IN (' . $placeholders . ')')->execute($jobIds);
    }
    echo PHP_EOL . "{$passed} passed, {$failed} failed." . PHP_EOL;
    exit($failed === 0 ? 0 : 1);
}
