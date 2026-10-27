<?php
/**
 * AICourseGenerator integration tests.
 *
 * The tests use the local Learning database and a deterministic fake provider.
 * Jobs created by this file are removed in a finally block; no provider key or
 * network access is required.
 *
 *     php modules/learning/tests/ai-course-generator-tests.php
 */

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__) . '/classes/AICourseGenerator.php';

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

class FakeOpenRouterClient extends OpenRouterClient
{
    /** @var array<int, array<string, mixed>> */
    private array $responses;
    /** @var array<int, array<string, mixed>> */
    public array $requests = [];
    private int $index = 0;

    /**
     * @param array<int, array<string, mixed>> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
        parent::__construct(
            null,
            null,
            null,
            static fn(): string => 'test-key-fixture',
            static function (float $seconds): void {
            },
            ['max_retries' => 0]
        );
    }

    public function chat(array $messages, array $options = [], int $employeeId = 0): array
    {
        $this->requests[] = [
            'messages' => $messages,
            'options' => $options,
            'employee_id' => $employeeId,
        ];
        $response = $this->responses[$this->index] ?? [
            'success' => false,
            'message' => 'No fake provider response was configured.',
            'http_status' => 0,
            'content' => null,
            'model' => null,
            'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0],
            'attempts' => 0,
            'retries' => 0,
            'response_format_fallback' => false,
            'latency_ms' => 0,
        ];
        $this->index++;

        if (isset($response['throw'])) {
            throw new RuntimeException((string) $response['throw']);
        }

        return $response;
    }
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function providerSuccess(array $data, int $promptTokens = 120, int $completionTokens = 60): array
{
    return [
        'success' => true,
        'message' => 'Provider response received.',
        'http_status' => 200,
        'content' => json_encode($data, JSON_UNESCAPED_SLASHES),
        'model' => 'openai/gpt-4.1-mini',
        'usage' => [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ],
        'attempts' => 1,
        'retries' => 0,
        'response_format_fallback' => false,
        'latency_ms' => 5,
    ];
}

/**
 * @return array<string, mixed>
 */
function providerInvalidJson(): array
{
    return [
        'success' => true,
        'message' => 'Provider response received.',
        'http_status' => 200,
        'content' => 'This is not JSON.',
        'model' => 'openai/gpt-4.1-mini',
        'usage' => ['prompt_tokens' => 90, 'completion_tokens' => 10, 'total_tokens' => 100],
        'attempts' => 1,
        'retries' => 0,
        'response_format_fallback' => false,
        'latency_ms' => 5,
    ];
}

/**
 * @param int[] $skillIds
 * @return array<string, mixed>
 */
function outlineData(array $skillIds, int $moduleCount = 2): array
{
    $modules = [];
    for ($index = 1; $index <= $moduleCount; $index++) {
        $modules[] = [
            'key' => 'module-' . $index,
            'title' => 'Module ' . $index . ' Foundations',
            'description' => 'Practical module ' . $index . ' for the trainee role.',
            'skill_ids' => [$skillIds[($index - 1) % count($skillIds)]],
            'lessons' => [
                [
                    'key' => 'lesson-1',
                    'title' => 'Module ' . $index . ' Lesson One',
                    'objectives' => ['Explain the core idea', 'Apply it at work'],
                    'estimated_minutes' => 20,
                ],
                [
                    'key' => 'lesson-2',
                    'title' => 'Module ' . $index . ' Lesson Two',
                    'objectives' => ['Practise the workflow', 'Review common mistakes'],
                    'estimated_minutes' => 25,
                ],
            ],
            'assumptions' => [],
            'warnings' => [],
        ];
    }

    return [
        'course' => [
            'title' => 'Targeted Workplace Skills',
            'description' => 'A practical course built for the selected trainee evidence.',
            'category' => 'Professional development',
            'delivery_mode' => 'online',
            'skill_ids' => $skillIds,
            'assumptions' => [],
            'warnings' => [],
        ],
        'modules' => $modules,
    ];
}

/**
 * @param array<string, mixed> $outlineModule
 * @return array<string, mixed>
 */
function moduleData(array $outlineModule, string $questionType = 'single_choice'): array
{
    $lessons = [];
    foreach ($outlineModule['lessons'] as $lesson) {
        $lessons[] = [
            'title' => $lesson['title'],
            'content_markdown' => "## " . $lesson['title'] . "\n\n"
                . "This lesson explains the practical workflow, the decisions to make, and a short workplace example. "
                . "Use the checklist before applying the technique in a real situation.",
            'estimated_minutes' => (int) ($lesson['estimated_minutes'] ?? 20),
            'objectives' => $lesson['objectives'],
        ];
    }

    $questions = [
        [
            'question_text' => 'What is the best first step in ' . $outlineModule['title'] . '?',
            'question_type' => 'single_choice',
            'options' => [
                ['text' => 'Review the situation and confirm the goal', 'is_correct' => true],
                ['text' => 'Skip the planning step', 'is_correct' => false],
                ['text' => 'Wait for another team to decide', 'is_correct' => false],
            ],
        ],
        [
            'question_text' => 'A documented checklist improves consistency.',
            'question_type' => 'true_false',
            'options' => [
                ['text' => 'True', 'is_correct' => true],
                ['text' => 'False', 'is_correct' => false],
            ],
        ],
    ];
    if ($questionType !== 'single_choice') {
        $questions[0]['question_type'] = $questionType;
    }

    return [
        'title' => $outlineModule['title'],
        'description' => $outlineModule['description'],
        'skill_ids' => $outlineModule['skill_ids'],
        'lessons' => $lessons,
        'quiz' => [
            'title' => $outlineModule['title'] . ' check',
            'duration_seconds' => 600,
            'passing_score' => 70,
            'max_attempts' => 2,
            'show_answers_after_submit' => 1,
            'questions' => $questions,
        ],
        'assumptions' => [],
        'warnings' => [],
    ];
}

/**
 * @return array<string, mixed>
 */
function evaluationData(int $count = 5): array
{
    $questions = [];
    for ($index = 1; $index <= $count; $index++) {
        if ($index % 2 === 0) {
            $questions[] = [
                'question_text' => 'Evaluation statement number ' . $index . ' is true.',
                'question_type' => 'true_false',
                'options' => [
                    ['text' => 'True', 'is_correct' => true],
                    ['text' => 'False', 'is_correct' => false],
                ],
            ];
        } else {
            $questions[] = [
                'question_text' => 'Which action is recommended in evaluation item ' . $index . '?',
                'question_type' => 'single_choice',
                'options' => [
                    ['text' => 'Use the documented process', 'is_correct' => true],
                    ['text' => 'Ignore the evidence', 'is_correct' => false],
                    ['text' => 'Guess without checking', 'is_correct' => false],
                ],
            ];
        }
    }

    return [
        'title' => 'Final course evaluation',
        'duration_seconds' => 1200,
        'passing_score' => 70,
        'max_attempts' => 2,
        'show_answers_after_submit' => 1,
        'questions' => $questions,
        'assumptions' => [],
        'warnings' => [],
    ];
}

function cleanupJobs(PDO $pdo, string $marker): void
{
    $stmt = $pdo->prepare('SELECT id FROM ld_ai_course_job WHERE brief_json LIKE :marker');
    $stmt->execute([':marker' => '%' . $marker . '%']);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($ids === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare(
        "DELETE FROM ld_ai_audit_log WHERE entity_type = 'ai_job' AND entity_id IN ($placeholders)"
    )->execute(array_map('strval', $ids));
    $pdo->prepare(
        "DELETE FROM ld_ai_course_job WHERE id IN ($placeholders)"
    )->execute(array_map('intval', $ids));
}

$pdo = (new Database())->getConnection();
$adminId = 1007;
$ownerId = 1007;
$learnerId = 1002;
$marker = 'P7 automated test ' . bin2hex(random_bytes(5));
$jobIds = [];

$skillRows = $pdo->query(
    "SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY id ASC LIMIT 2"
)->fetchAll(PDO::FETCH_ASSOC);
$skillIds = array_map(static fn(array $row): int => (int) $row['id'], $skillRows);
if ($skillIds === []) {
    fwrite(STDERR, "No active skills are available for the generator tests.\n");
    exit(1);
}

$settings = [
    // The fixture uses a paid model to keep the provider payload deterministic.
    'model_cost_mode' => 'mixed',
    'max_modules' => 5,
    'max_lessons_per_module' => 4,
    'max_quiz_questions_per_module' => 5,
    'max_evaluation_questions' => 8,
    'max_calls_per_job' => 12,
    'max_output_tokens_per_step' => 4000,
    'jobs_per_admin_per_day' => 100,
    'monthly_cost_cap_usd' => 0,
    'per_job_cost_cap_usd' => 0,
    'temperature' => 0.3,
    'request_timeout' => 90,
];

cleanupJobs($pdo, $marker);

try {
    $outline = outlineData($skillIds, 2);
    $moduleOne = moduleData($outline['modules'][0]);
    $moduleTwo = moduleData($outline['modules'][1]);
    $evaluation = evaluationData(5);

    $client = new FakeOpenRouterClient([
        providerSuccess($outline),
        providerSuccess($moduleOne),
        providerInvalidJson(),
        providerSuccess($moduleTwo),
        providerSuccess($evaluation),
    ]);
    $generator = new AICourseGenerator($pdo, $client, $settings);

    $created = $generator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': prepare a practical course for the trainee role.',
            'source_notes' => 'Use conservative assumptions where evidence is limited.',
            'module_count' => 2,
            'lessons_per_module' => 2,
            'difficulty' => 'intermediate',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );

    check('outline job is created', ($created['success'] ?? false) === true, (string) ($created['message'] ?? ''));
    $jobId = (int) ($created['job_id'] ?? 0);
    if ($jobId > 0) {
        $jobIds[] = $jobId;
    }
    $job = $created['job'] ?? [];
    check('outline stops for review', ($job['status'] ?? '') === 'draft');
    check('outline step is done', (($job['steps'][0]['status'] ?? '') === 'done'));
    check('outline has one total step before approval', (int) ($job['total_steps'] ?? 0) === 1);
    check('outline content is stored', ($job['blueprint']['outline']['course']['title'] ?? '') === $outline['course']['title']);
    check('snapshot exposes the draft outline for review', ($job['outline']['course']['title'] ?? '') === $outline['course']['title']);
    $listedJob = null;
    foreach ($generator->listJobs(100) as $listedRow) {
        if ((int) ($listedRow['id'] ?? 0) === $jobId) {
            $listedJob = $listedRow;
            break;
        }
    }
    check('job history lists the created job', is_array($listedJob), 'job_id=' . $jobId);
    check('outline uses one provider call', count($client->requests) === 1);
    check('job usage is recorded after outline', (int) ($job['prompt_tokens'] ?? 0) > 0 && (int) ($job['completion_tokens'] ?? 0) > 0);
    check('job cost is recorded after outline', (float) ($job['estimated_cost_usd'] ?? 0) > 0);
    $createdTimestamp = strtotime((string) ($job['created_at'] ?? ''));
    $startedTimestamp = strtotime((string) ($job['steps'][0]['started_at'] ?? ''));
    check(
        'job and step timestamps share the database clock',
        $createdTimestamp !== false && $startedTimestamp !== false && abs($createdTimestamp - $startedTimestamp) < 60,
        'created=' . (string) ($job['created_at'] ?? '') . ' started=' . (string) ($job['steps'][0]['started_at'] ?? '')
    );

    $beforeDraftAdvance = count($client->requests);
    $draftAdvance = $generator->advance($jobId, $adminId);
    check('advance waits for outline approval', ($draftAdvance['success'] ?? true) === false);
    check('draft advance makes no provider call', count($client->requests) === $beforeDraftAdvance);

    $approved = $generator->approveOutline($jobId, $adminId, $job['blueprint']['outline']);
    check('outline can be approved', ($approved['success'] ?? false) === true, (string) ($approved['message'] ?? ''));
    $job = $approved['job'] ?? [];
    check('approval creates module, evaluation, and quality steps', (int) ($job['total_steps'] ?? 0) === 5);
    check('approval starts module one', ($job['current_step'] ?? '') === 'module-1');
    check('approval keeps the outline done', (int) ($job['completed_steps'] ?? 0) === 1);
    check('approval makes no provider call', count($client->requests) === 1);

    $moduleOneResult = $generator->advance($jobId, $adminId);
    check('module one completes', ($moduleOneResult['success'] ?? false) === true, (string) ($moduleOneResult['message'] ?? ''));
    $job = $moduleOneResult['job'] ?? [];
    check('module one is persisted', count($job['blueprint']['modules'] ?? []) === 1);
    check('module one moves to module two', ($job['current_step'] ?? '') === 'module-2');
    check('module one step is done', (($job['steps'][1]['status'] ?? '') === 'done'));

    $moduleTwoResult = $generator->advance($jobId, $adminId);
    check('module two failure is reported', ($moduleTwoResult['success'] ?? true) === false);
    $job = $moduleTwoResult['job'] ?? [];
    check('failed job keeps status failed', ($job['status'] ?? '') === 'failed');
    check('failed module two preserves module one', count($job['blueprint']['modules'] ?? []) === 1);
    check('failed module two step is marked failed', (($job['steps'][2]['status'] ?? '') === 'failed'));
    check('failed module two has a safe error', trim((string) ($job['error_message'] ?? '')) !== '');
    check('failure occurs after three provider calls', count($client->requests) === 3);

    $retried = $generator->retry($jobId, $adminId);
    check('retry resumes the failed module', ($retried['success'] ?? false) === true, (string) ($retried['message'] ?? ''));
    $job = $retried['job'] ?? [];
    check('retry persists module two', count($job['blueprint']['modules'] ?? []) === 2);
    check('retry moves to evaluation', ($job['current_step'] ?? '') === 'evaluation');
    check('retry uses the next provider call', count($client->requests) === 4);

    $evaluationResult = $generator->advance($jobId, $adminId);
    check('evaluation completes', ($evaluationResult['success'] ?? false) === true, (string) ($evaluationResult['message'] ?? ''));
    $job = $evaluationResult['job'] ?? [];
    check('evaluation is persisted', count($job['blueprint']['evaluation']['questions'] ?? []) === 5);
    check('evaluation moves to quality checks', ($job['current_step'] ?? '') === 'quality');
    check('evaluation uses the next provider call', count($client->requests) === 5);

    $qualityResult = $generator->advance($jobId, $adminId);
    check('quality checks complete', ($qualityResult['success'] ?? false) === true, (string) ($qualityResult['message'] ?? ''));
    $job = $qualityResult['job'] ?? [];
    check('quality job is ready', ($job['status'] ?? '') === 'ready');
    check('quality report passes', ($job['blueprint']['quality']['passed'] ?? false) === true);
    check('quality uses no provider call', count($client->requests) === 5);
    check('all five steps are complete', (int) ($job['completed_steps'] ?? 0) === 5);
    check('job records prompt and completion usage', (int) ($job['prompt_tokens'] ?? 0) > 0 && (int) ($job['completion_tokens'] ?? 0) > 0);
    check('job records a positive estimated cost', (float) ($job['estimated_cost_usd'] ?? 0) > 0);
    check('all steps are done', count(array_filter(
        $job['steps'] ?? [],
        static fn(array $step): bool => ($step['status'] ?? '') !== 'done'
    )) === 0);
    check('job context omits employee email', !str_contains(
        (string) json_encode($job['context'] ?? [], JSON_UNESCAPED_SLASHES),
        (string) $pdo->query("SELECT email FROM em_employees WHERE employee_id = 1002")->fetchColumn()
    ));

    $requestsBeforeReadyAdvance = count($client->requests);
    $readyAdvance = $generator->advance($jobId, $adminId);
    check('ready job advance is idempotent', ($readyAdvance['success'] ?? false) === true);
    check('ready job makes no provider call', count($client->requests) === $requestsBeforeReadyAdvance);

    // Direct validation checks for unsupported types and archived skills.
    $validator = new AICourseBlueprint($settings, $skillRows, [
        'module_count' => 2,
        'lessons_per_module' => 2,
        'difficulty' => 'intermediate',
        'selected_skill_ids' => $skillIds,
    ]);
    $validOutline = $validator->validateOutline($outline);
    check('valid outline passes the blueprint validator', ($validOutline['success'] ?? false) === true);
    $invalidModule = moduleData($outline['modules'][0], 'multiple_choice');
    $invalidModuleResult = $validator->validateModule($invalidModule, $outline['modules'][0], $outline['course']);
    check('multiple choice is rejected', ($invalidModuleResult['success'] ?? true) === false);
    check('multiple choice rejection is explained', (bool) array_filter(
        $invalidModuleResult['errors'] ?? [],
        static fn(string $error): bool => str_contains($error, 'single_choice')
    ));
    $unknownSkillOutline = $outline;
    $unknownSkillOutline['course']['skill_ids'] = [999999];
    $unknownSkillResult = $validator->validateOutline($unknownSkillOutline);
    check('unknown skill id is rejected', ($unknownSkillResult['success'] ?? true) === false);
    check('unknown skill rejection is explained', (bool) array_filter(
        $unknownSkillResult['errors'] ?? [],
        static fn(string $error): bool => str_contains($error, 'unknown or archived')
    ));

    $unrelatedSkill = $pdo->query(
        "SELECT id, name FROM ld_skill
          WHERE status = 'active' AND id NOT IN (" . implode(',', array_map('intval', $skillIds)) . ")
          ORDER BY id ASC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    if (is_array($unrelatedSkill)) {
        $scopedValidator = new AICourseBlueprint(
            $settings,
            array_merge($skillRows, [$unrelatedSkill]),
            [
                'module_count' => 2,
                'lessons_per_module' => 2,
                'difficulty' => 'intermediate',
                'selected_skill_ids' => $skillIds,
            ],
            $skillIds
        );
        $unrelatedOutline = $outline;
        $unrelatedOutline['course']['skill_ids'] = [(int) $unrelatedSkill['id']];
        $unrelatedResult = $scopedValidator->validateOutline($unrelatedOutline);
        check('active but unrelated skill id is rejected', ($unrelatedResult['success'] ?? true) === false);
        check('unrelated skill rejection is explained', (bool) array_filter(
            $unrelatedResult['errors'] ?? [],
            static fn(string $error): bool => str_contains($error, 'not a current gap or selected skill')
        ));
    } else {
        check('active but unrelated skill fixture is available', false, 'no unrelated active skill found');
    }

    // Acceptance: a five-module course completes through seven provider calls.
    $fiveOutline = outlineData($skillIds, 5);
    $fiveResponses = [providerSuccess($fiveOutline)];
    for ($index = 0; $index < 5; $index++) {
        $fiveResponses[] = providerSuccess(moduleData($fiveOutline['modules'][$index]));
    }
    $fiveResponses[] = providerSuccess(evaluationData(8));
    $fiveClient = new FakeOpenRouterClient($fiveResponses);
    $fiveGenerator = new AICourseGenerator($pdo, $fiveClient, array_merge($settings, [
        'max_modules' => 5,
        'max_evaluation_questions' => 8,
    ]));
    $fiveCreate = $fiveGenerator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': five-module acceptance check.',
            'module_count' => 5,
            'lessons_per_module' => 2,
            'difficulty' => 'intermediate',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );
    $fiveJobId = (int) ($fiveCreate['job_id'] ?? 0);
    if ($fiveJobId > 0) {
        $jobIds[] = $fiveJobId;
    }
    $fiveJob = $fiveCreate['job'] ?? [];
    $fiveApprove = $fiveGenerator->approveOutline($fiveJobId, $adminId, $fiveJob['blueprint']['outline'] ?? []);
    check('five-module outline is approved', ($fiveApprove['success'] ?? false) === true);
    for ($index = 0; $index < 5; $index++) {
        $fiveStep = $fiveGenerator->advance($fiveJobId, $adminId);
        check('five-module course module ' . ($index + 1) . ' completes', ($fiveStep['success'] ?? false) === true);
    }
    $fiveEvaluation = $fiveGenerator->advance($fiveJobId, $adminId);
    check('five-module course evaluation completes', ($fiveEvaluation['success'] ?? false) === true);
    $fiveQuality = $fiveGenerator->advance($fiveJobId, $adminId);
    $fiveJob = $fiveQuality['job'] ?? [];
    check('five-module course reaches ready', ($fiveJob['status'] ?? '') === 'ready');
    check('five-module course uses seven provider calls', count($fiveClient->requests) === 7);
    check('five-module course completes eight steps', (int) ($fiveJob['completed_steps'] ?? 0) === 8);

    // Per-job token limit is checked before a provider call.
    $tokenClient = new FakeOpenRouterClient([
        providerSuccess(outlineData($skillIds, 1), 120, 6000),
    ]);
    $tokenGenerator = new AICourseGenerator($pdo, $tokenClient, array_merge($settings, [
        'max_calls_per_job' => 12,
        'max_output_tokens_per_step' => 500,
    ]));
    $tokenCreate = $tokenGenerator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': token limit check.',
            'module_count' => 1,
            'lessons_per_module' => 2,
            'difficulty' => 'intermediate',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );
    $tokenJobId = (int) ($tokenCreate['job_id'] ?? 0);
    if ($tokenJobId > 0) {
        $jobIds[] = $tokenJobId;
    }
    $tokenJob = $tokenCreate['job'] ?? [];
    $tokenApprove = $tokenGenerator->approveOutline($tokenJobId, $adminId, $tokenJob['blueprint']['outline'] ?? []);
    check('token limit test outline is approved', ($tokenApprove['success'] ?? false) === true);
    $tokenAdvance = $tokenGenerator->advance($tokenJobId, $adminId);
    check('completion token limit is enforced', ($tokenAdvance['success'] ?? true) === false);
    check('completion token limit is explained', str_contains((string) ($tokenAdvance['message'] ?? ''), 'completion-token limit'));
    check('token limit rejection makes no extra provider call', count($tokenClient->requests) === 1);

    // Per-job call limit is checked before approving an oversized outline.
    $limitedClient = new FakeOpenRouterClient([providerSuccess(outlineData($skillIds, 1))]);
    $limitedGenerator = new AICourseGenerator($pdo, $limitedClient, array_merge($settings, [
        'max_calls_per_job' => 2,
    ]));
    $limitedCreate = $limitedGenerator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': call limit check.',
            'module_count' => 1,
            'lessons_per_module' => 2,
            'difficulty' => 'intermediate',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );
    $limitedJobId = (int) ($limitedCreate['job_id'] ?? 0);
    if ($limitedJobId > 0) {
        $jobIds[] = $limitedJobId;
    }
    $limitedJob = $limitedCreate['job'] ?? [];
    $limitedApprove = $limitedGenerator->approveOutline(
        $limitedJobId,
        $adminId,
        $limitedJob['blueprint']['outline'] ?? []
    );
    check('outline above the call limit is rejected', ($limitedApprove['success'] ?? true) === false);
    check('call limit rejection happens before expansion', count($limitedClient->requests) === 1);

    // Per-admin daily quota is checked before a new job is inserted.
    $quotaClient = new FakeOpenRouterClient([]);
    $quotaGenerator = new AICourseGenerator($pdo, $quotaClient, array_merge($settings, [
        'jobs_per_admin_per_day' => 1,
    ]));
    $quotaResult = $quotaGenerator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': daily quota check.',
            'module_count' => 1,
            'lessons_per_module' => 1,
            'difficulty' => 'beginner',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );
    check('daily job quota is enforced', ($quotaResult['success'] ?? true) === false);
    check('daily quota rejection uses HTTP 429', (int) ($quotaResult['http_status'] ?? 0) === 429);
    check('daily quota rejection makes no provider call', count($quotaClient->requests) === 0);

    // Stale-running recovery and retry.
    $staleClient = new FakeOpenRouterClient([
        providerSuccess(outlineData($skillIds, 1)),
        providerSuccess(moduleData(outlineData($skillIds, 1)['modules'][0])),
    ]);
    $staleGenerator = new AICourseGenerator($pdo, $staleClient, $settings);
    $staleCreate = $staleGenerator->createJob(
        $adminId,
        $ownerId,
        $learnerId,
        [
            'goal' => $marker . ': stale recovery check.',
            'module_count' => 1,
            'lessons_per_module' => 2,
            'difficulty' => 'intermediate',
            'model' => 'openai/gpt-4.1-mini',
        ],
        $skillIds
    );
    $staleJobId = (int) ($staleCreate['job_id'] ?? 0);
    if ($staleJobId > 0) {
        $jobIds[] = $staleJobId;
    }
    $staleJob = $staleCreate['job'] ?? [];
    $staleApprove = $staleGenerator->approveOutline($staleJobId, $adminId, $staleJob['blueprint']['outline'] ?? []);
    check('stale test outline is approved', ($staleApprove['success'] ?? false) === true);

    $pdo->prepare(
        "UPDATE ld_ai_course_job_step
            SET status = 'running', started_at = :started_at
          WHERE job_id = :job_id AND step_key = 'module-1'"
    )->execute([
        ':started_at' => date('Y-m-d H:i:s', time() - 86400),
        ':job_id' => $staleJobId,
    ]);
    $pdo->prepare(
        "UPDATE ld_ai_course_job SET status = 'generating', current_step = 'module-1' WHERE id = :job_id"
    )->execute([':job_id' => $staleJobId]);

    $staleAdvance = $staleGenerator->advance($staleJobId, $adminId);
    check('stale running step is recovered', ($staleAdvance['success'] ?? true) === false);
    check('stale recovery marks the job failed', (($staleAdvance['job']['status'] ?? '') === 'failed'));
    check('stale recovery makes no provider call', count($staleClient->requests) === 1);
    $staleRetry = $staleGenerator->retry($staleJobId, $adminId);
    check('stale job can be retried', ($staleRetry['success'] ?? false) === true, (string) ($staleRetry['message'] ?? ''));
    check('stale retry resumes the module', (($staleRetry['job']['current_step'] ?? '') === 'evaluation'));
    $staleCancel = $staleGenerator->cancel($staleJobId, $adminId);
    check('generating job can be cancelled', ($staleCancel['success'] ?? false) === true);
    check('cancelled job is marked cancelled', (($staleCancel['job']['status'] ?? '') === 'cancelled'));
} finally {
    cleanupJobs($pdo, $marker);
}

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
