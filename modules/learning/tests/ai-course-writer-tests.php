<?php
/**
 * P9 writer and safe-rendering tests.
 *
 *     php modules/learning/tests/ai-course-writer-tests.php
 *
 * The test creates temporary ready jobs, applies them through AICourseWriter,
 * verifies the draft rows, and removes every created row in a finally block.
 * It never calls a provider and never creates enrollment or progress rows.
 */

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__) . '/classes/AIContentRenderer.php';
require_once dirname(__DIR__) . '/classes/AICourseWriter.php';
require_once dirname(__DIR__) . '/classes/AIAudit.php';

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

/**
 * @param array<int, int> $skillIds
 * @return array<string, mixed>
 */
function readyBlueprint(string $marker, array $skillIds, bool $qualityPassed = true): array
{
    $lessonMarkdown = "## Safe heading\n\n"
        . "**Strong** and *emphasis* with `inline code`.\n\n"
        . "<script>alert('xss')</script>\n\n"
        . "<img src=x onerror=alert(1)>\n\n"
        . "- First item\n- Second item\n\n"
        . "> Quoted text\n\n"
        . "```\ncode <b>block</b>\n```";

    $module = [
        'key' => 'module-1',
        'title' => 'Test Module',
        'description' => 'A deterministic module.',
        'order_index' => 1,
        'skill_ids' => $skillIds,
        'skills' => [['id' => $skillIds[0] ?? 0, 'name' => 'Test Skill']],
        'lessons' => [[
            'key' => 'lesson-1',
            'title' => 'Test Lesson',
            'content_markdown' => $lessonMarkdown,
            'estimated_minutes' => 20,
            'objectives' => ['Verify safe rendering', 'Verify writer mapping'],
            'order_index' => 1,
        ]],
        'quiz' => [
            'title' => 'Test Module Quiz',
            'duration_seconds' => 600,
            'passing_score' => 70,
            'max_attempts' => 2,
            'show_answers_after_submit' => true,
            'questions' => [
                [
                    'question_text' => 'Which option is correct?',
                    'question_type' => 'single_choice',
                    'order_index' => 1,
                    'options' => [
                        ['text' => 'Correct', 'is_correct' => true, 'order_index' => 1],
                        ['text' => 'Incorrect', 'is_correct' => false, 'order_index' => 2],
                    ],
                ],
                [
                    'question_text' => 'A checklist improves consistency.',
                    'question_type' => 'true_false',
                    'order_index' => 2,
                    'options' => [
                        ['text' => 'True', 'is_correct' => true, 'order_index' => 1],
                        ['text' => 'False', 'is_correct' => false, 'order_index' => 2],
                    ],
                ],
            ],
        ],
        'assumptions' => [],
        'warnings' => [],
    ];

    return [
        'version' => 1,
        'course' => [
            'title' => $marker,
            'description' => 'A deterministic writer test course.',
            'category' => 'Test',
            'delivery_mode' => 'online',
            'difficulty' => 'intermediate',
            'skill_ids' => $skillIds,
            'skills' => [['id' => $skillIds[0] ?? 0, 'name' => 'Test Skill']],
            'assumptions' => [],
            'warnings' => [],
        ],
        'modules' => [$module],
        'evaluation' => [
            'title' => 'Test Final Evaluation',
            'duration_seconds' => 1200,
            'passing_score' => 70,
            'max_attempts' => 2,
            'show_answers_after_submit' => true,
            'questions' => [[
                'question_text' => 'What should happen first?',
                'question_type' => 'single_choice',
                'order_index' => 1,
                'options' => [
                    ['text' => 'Confirm the goal', 'is_correct' => true, 'order_index' => 1],
                    ['text' => 'Skip the context', 'is_correct' => false, 'order_index' => 2],
                ],
            ]],
        ],
        'quality' => [
            'passed' => $qualityPassed,
            'score' => $qualityPassed ? 100 : 40,
            'blocking' => $qualityPassed ? [] : [['code' => 'test_blocker', 'message' => 'Blocked for test.']],
            'warnings' => [],
            'model_warnings' => [],
            'stats' => [
                'modules' => 1,
                'lessons' => 1,
                'quiz_questions' => 2,
                'evaluation_questions' => 1,
                'skills' => count($skillIds),
                'estimated_minutes' => 20,
            ],
        ],
    ];
}

/**
 * @param array<string, mixed> $blueprint
 */
function seedReadyJob(PDO $pdo, string $marker, array $blueprint): int
{
    $brief = [
        'goal' => $marker,
        'module_count' => 1,
        'lessons_per_module' => 1,
        'difficulty' => 'intermediate',
        'selected_skill_ids' => [1],
    ];
    $context = ['trainee' => ['name' => 'Writer Test'], 'warnings' => []];
    $stmt = $pdo->prepare(
        "INSERT INTO ld_ai_course_job
            (created_by, course_owner_id, learner_id, model, prompt_version, status,
             current_step, total_steps, completed_steps, brief_json, context_json, blueprint_json)
         VALUES
            (1007, 1007, 1005, 'openai/gpt-4.1-mini', 'v1', 'ready',
             'complete', 8, 8, :brief_json, :context_json, :blueprint_json)"
    );
    $stmt->execute([
        ':brief_json' => json_encode($brief, JSON_UNESCAPED_SLASHES),
        ':context_json' => json_encode($context, JSON_UNESCAPED_SLASHES),
        ':blueprint_json' => json_encode($blueprint, JSON_UNESCAPED_SLASHES),
    ]);

    return (int) $pdo->lastInsertId();
}

$pdo = (new Database())->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$marker = 'P9 Writer Test ' . date('YmdHis') . ' ' . bin2hex(random_bytes(3));
$jobIds = [];
$courseIds = [];

try {
    // Renderer checks.
    $rendered = AIContentRenderer::render("## Heading\n\n**Bold** and *italic* and `code`.\n\n- One\n- Two\n\n> Quote\n\n```\n<b>raw</b>\n```");
    check('renderer emits headings', str_contains($rendered, '<h2>Heading</h2>'), $rendered);
    check('renderer emits bold and italic', str_contains($rendered, '<strong>Bold</strong>') && str_contains($rendered, '<em>italic</em>'), $rendered);
    check('renderer emits lists', str_contains($rendered, '<ul><li>One</li><li>Two</li></ul>'), $rendered);
    check('renderer emits blockquotes', str_contains($rendered, '<blockquote><p>Quote</p></blockquote>'), $rendered);
    check('renderer escapes code', str_contains($rendered, '&lt;b&gt;raw&lt;/b&gt;'), $rendered);

    $xss = AIContentRenderer::render("<script>alert('xss')</script>\n\n<img src=x onerror=alert(1)>\n\n[link](javascript:alert(1))\n\n<iframe src=\"https://evil.example\"></iframe>");
    check('renderer removes script tags', !str_contains($xss, '<script'), $xss);
    check('renderer removes image tags', !str_contains($xss, '<img'), $xss);
    check('renderer removes iframe tags', !str_contains($xss, '<iframe'), $xss);
    check('renderer does not create javascript links', !str_contains($xss, 'href='), $xss);
    check('renderer escapes raw HTML', str_contains($xss, '&lt;script&gt;') && str_contains($xss, '&lt;img'), $xss);

    // Happy path.
    $skillId = (int) $pdo->query("SELECT id FROM ld_skill WHERE status = 'active' ORDER BY id ASC LIMIT 1")->fetchColumn();
    if ($skillId <= 0) {
        throw new RuntimeException('No active skill found for writer test.');
    }
    $jobId = seedReadyJob($pdo, $marker, readyBlueprint($marker, [$skillId]));
    $jobIds[] = $jobId;
    $writer = new AICourseWriter($pdo, new AIAudit($pdo));
    $result = $writer->apply($jobId, 1007);
    $courseId = (int) ($result['course_id'] ?? 0);
    if ($courseId > 0) {
        $courseIds[] = $courseId;
    }

    check('writer applies a ready job', ($result['success'] ?? false) === true, (string) ($result['message'] ?? ''));
    check('writer returns a course id', $courseId > 0);
    check('writer returns a builder redirect', str_contains((string) ($result['redirect'] ?? ''), 'course-structure&course_id=' . $courseId));

    $course = $pdo->prepare('SELECT * FROM ld_course WHERE id = :id');
    $course->execute([':id' => $courseId]);
    $courseRow = $course->fetch(PDO::FETCH_ASSOC) ?: [];
    check('course is created as draft', ($courseRow['status'] ?? '') === 'draft');
    check('course uses the selected owner', (int) ($courseRow['instructor_id'] ?? 0) === 1007);
    check('course title is mapped', ($courseRow['title'] ?? '') === $marker);
    check('course delivery mode is mapped', ($courseRow['delivery_mode'] ?? '') === 'online');

    $moduleCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_module WHERE course_id = ' . $courseId)->fetchColumn();
    $lessonCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = ' . $courseId)->fetchColumn();
    $quizCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_quiz q JOIN ld_module m ON m.id = q.module_id WHERE m.course_id = ' . $courseId)->fetchColumn();
    $quizQuestionCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_quiz_question qq JOIN ld_quiz q ON q.id = qq.reference_id WHERE qq.item_type = \'quiz\' AND q.module_id IN (SELECT id FROM ld_module WHERE course_id = ' . $courseId . ')')->fetchColumn();
    $optionCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_quiz_question_option o JOIN ld_quiz_question qq ON qq.id = o.question_id JOIN ld_quiz q ON q.id = qq.reference_id WHERE qq.item_type = \'quiz\' AND q.module_id IN (SELECT id FROM ld_module WHERE course_id = ' . $courseId . ')')->fetchColumn();
    $evaluationCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_evaluation WHERE course_id = ' . $courseId)->fetchColumn();
    $evaluationQuestionCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_quiz_question qq JOIN ld_evaluation e ON e.id = qq.reference_id WHERE qq.item_type = \'evaluation\' AND e.course_id = ' . $courseId)->fetchColumn();
    $courseSkillCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_course_skill WHERE course_id = ' . $courseId)->fetchColumn();
    $enrollmentCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_enrollment WHERE course_id = ' . $courseId)->fetchColumn();

    check('writer creates one module', $moduleCount === 1, 'modules=' . $moduleCount);
    check('writer creates one lesson', $lessonCount === 1, 'lessons=' . $lessonCount);
    check('writer creates one quiz', $quizCount === 1, 'quizzes=' . $quizCount);
    check('writer creates two quiz questions', $quizQuestionCount === 2, 'questions=' . $quizQuestionCount);
    check('writer creates four quiz options', $optionCount === 4, 'options=' . $optionCount);
    check('writer creates one evaluation', $evaluationCount === 1, 'evaluation=' . $evaluationCount);
    check('writer creates one evaluation question', $evaluationQuestionCount === 1, 'evaluation_questions=' . $evaluationQuestionCount);
    check('writer creates one course skill link', $courseSkillCount === 1, 'course_skills=' . $courseSkillCount);
    check('writer creates no enrollment', $enrollmentCount === 0, 'enrollments=' . $enrollmentCount);

    $lessonBody = (string) $pdo->query('SELECT l.content_body FROM ld_lesson l JOIN ld_module m ON m.id = l.module_id WHERE m.course_id = ' . $courseId . ' LIMIT 1')->fetchColumn();
    check('lesson content is rendered safely', !str_contains($lessonBody, '<script') && !str_contains($lessonBody, '<img'), $lessonBody);
    check('lesson content keeps safe formatting', str_contains($lessonBody, '<strong>Strong</strong>') && str_contains($lessonBody, '<blockquote>'), $lessonBody);

    $job = $pdo->prepare('SELECT status, course_id, applied_at FROM ld_ai_course_job WHERE id = :id');
    $job->execute([':id' => $jobId]);
    $jobRow = $job->fetch(PDO::FETCH_ASSOC) ?: [];
    check('job is marked applied', ($jobRow['status'] ?? '') === 'applied');
    check('job stores the course id', (int) ($jobRow['course_id'] ?? 0) === $courseId);
    check('job stores the applied timestamp', !empty($jobRow['applied_at']));

    // Double apply.
    $second = $writer->apply($jobId, 1007);
    $moduleCountAfter = (int) $pdo->query('SELECT COUNT(*) FROM ld_module WHERE course_id = ' . $courseId)->fetchColumn();
    check('double apply succeeds idempotently', ($second['success'] ?? false) === true);
    check('double apply returns the same course', (int) ($second['course_id'] ?? 0) === $courseId);
    check('double apply does not duplicate modules', $moduleCountAfter === 1, 'modules=' . $moduleCountAfter);

    // A database failure must roll back the whole course and leave the job ready.
    $rollbackMarker = $marker . ' Rollback';
    $rollbackJobId = seedReadyJob($pdo, $rollbackMarker, readyBlueprint($rollbackMarker, [999999]));
    $jobIds[] = $rollbackJobId;
    $rollback = $writer->apply($rollbackJobId, 1007);
    check('writer reports a failed transaction', ($rollback['success'] ?? true) === false);
    check('failed transaction uses 500', (int) ($rollback['http'] ?? 0) === 500);
    $rollbackCourseCount = (int) $pdo->query('SELECT COUNT(*) FROM ld_course WHERE title = ' . $pdo->quote($rollbackMarker))->fetchColumn();
    check('failed transaction leaves no course', $rollbackCourseCount === 0, 'courses=' . $rollbackCourseCount);
    $rollbackJob = $pdo->prepare('SELECT status, course_id FROM ld_ai_course_job WHERE id = :id');
    $rollbackJob->execute([':id' => $rollbackJobId]);
    $rollbackRow = $rollbackJob->fetch(PDO::FETCH_ASSOC) ?: [];
    check('failed transaction leaves the job ready', ($rollbackRow['status'] ?? '') === 'ready');
    check('failed transaction leaves no course id on the job', (int) ($rollbackRow['course_id'] ?? 0) === 0);

    // Blocking quality.
    $blockedJobId = seedReadyJob($pdo, $marker . ' Blocked', readyBlueprint($marker . ' Blocked', [$skillId], false));
    $jobIds[] = $blockedJobId;
    $blocked = $writer->apply($blockedJobId, 1007);
    check('blocking quality prevents apply', ($blocked['success'] ?? true) === false);
    check('blocking quality uses 422', (int) ($blocked['http'] ?? 0) === 422);
    $blockedCourseCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_course WHERE title = " . $pdo->quote($marker . ' Blocked'))->fetchColumn();
    check('blocking quality creates no course', $blockedCourseCount === 0, 'courses=' . $blockedCourseCount);
    $blockedJob = $pdo->prepare('SELECT status FROM ld_ai_course_job WHERE id = :id');
    $blockedJob->execute([':id' => $blockedJobId]);
    check('blocked job remains ready', (string) $blockedJob->fetchColumn() === 'ready');
} catch (Throwable $e) {
    check('writer test completed without an exception', false, $e->getMessage());
} finally {
    if ($courseIds !== []) {
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $pdo->prepare('DELETE FROM ld_course WHERE id IN (' . $placeholders . ')')->execute($courseIds);
    }
    if ($jobIds !== []) {
        $placeholders = implode(',', array_fill(0, count($jobIds), '?'));
        $pdo->prepare("DELETE FROM ld_ai_audit_log WHERE entity_type = 'ai_job' AND entity_id IN (" . $placeholders . ')')->execute(array_map('strval', $jobIds));
        $pdo->prepare('DELETE FROM ld_ai_course_job WHERE id IN (' . $placeholders . ')')->execute($jobIds);
    }
    echo PHP_EOL . "{$passed} passed, {$failed} failed." . PHP_EOL;
    exit($failed === 0 ? 0 : 1);
}
