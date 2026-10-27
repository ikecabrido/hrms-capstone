<?php
/**
 * AICourseGenerator - resumable, staged course generation.
 *
 * Sequence:
 *   outline -> module 1..N -> final evaluation -> deterministic quality checks
 *
 * A job is persisted before the first provider call. Each step is reserved in a
 * short transaction, the provider call runs outside any transaction, and the
 * result is persisted in a second short transaction. This makes partial
 * failures recoverable and prevents long row locks during provider latency.
 */

require_once __DIR__ . '/AIConfig.php';
require_once __DIR__ . '/AIAudit.php';
require_once __DIR__ . '/AICourseContext.php';
require_once __DIR__ . '/AICourseBlueprint.php';
require_once __DIR__ . '/AICoursePrompts.php';
require_once __DIR__ . '/AICourseJobStore.php';
require_once __DIR__ . '/OpenRouterClient.php';

class AICourseGenerator
{
    private PDO $pdo;
    private AICourseJobStore $store;
    private OpenRouterClient $client;
    private AIAudit $audit;
    /** @var array<string, mixed> */
    private array $settings;

    public function __construct(
        PDO $pdo,
        ?OpenRouterClient $client = null,
        ?array $settings = null,
        ?AIAudit $audit = null
    ) {
        $this->pdo = $pdo;
        $this->audit = $audit ?? new AIAudit($pdo);
        $this->settings = AIConfig::settingDefaults();

        foreach ($this->settings as $key => $default) {
            $value = AIConfig::runtimeValue($pdo, $key, $default);
            if ($value !== null) {
                $this->settings[$key] = $value;
            }
        }
        if ($settings !== null) {
            $this->settings = array_merge($this->settings, $settings);
        }

        $this->store = new AICourseJobStore(
            $pdo,
            (string) ($this->settings['model_cost_mode'] ?? AIConfig::DEFAULT_MODEL_COST_MODE)
        );
        $this->client = $client ?? new OpenRouterClient($pdo, null, null, null, null, $this->settings);
    }

    /**
     * Create a job, persist it, and run the outline step.
     *
     * @param array<string, mixed> $brief
     * @param int[] $selectedSkillIds
     * @param string[] $excludedEvidence
     * @return array<string, mixed>
     */
    public function createJob(
        int $adminId,
        int $ownerId,
        int $learnerId,
        array $brief,
        array $selectedSkillIds = [],
        array $excludedEvidence = []
    ): array {
        if ($adminId <= 0) {
            return $this->error('An admin session is required.', 401);
        }
        if ($ownerId <= 0) {
            $ownerId = $adminId;
        }
        if ($this->activeEmployee($ownerId) === null) {
            return $this->error('Choose an active course owner.', 422);
        }
        if ($learnerId <= 0) {
            return $this->error('Choose a trainee first.', 422);
        }

        $brief = $this->normalizeBrief($brief, $selectedSkillIds, $excludedEvidence);
        if (isset($brief['error'])) {
            return $this->error((string) $brief['error'], 422);
        }

        $activeSkills = $this->store->activeSkills();
        $activeIds = [];
        foreach ($activeSkills as $skill) {
            $activeIds[(int) $skill['id']] = true;
        }
        foreach ($brief['selected_skill_ids'] as $skillId) {
            if (!isset($activeIds[(int) $skillId])) {
                return $this->error('A selected skill is unknown or archived. Refresh the trainee context and try again.', 422);
            }
        }

        try {
            $contextResult = (new AICourseContext($this->pdo))->build(
                $learnerId,
                $brief['selected_skill_ids'],
                $brief['excluded_evidence']
            );
        } catch (Throwable $e) {
            return $this->error('The trainee context could not be prepared.', 500);
        }
        if (!$contextResult['success']) {
            return $this->error((string) ($contextResult['message'] ?? 'The trainee context is unavailable.'), 422);
        }
        $context = $contextResult['context'];

        $model = null;
        if ($brief['model'] !== '') {
            $model = $this->store->modelBySlug($brief['model']);
        } else {
            $model = $this->store->defaultModel();
        }
        if ($model === null) {
            return $this->error(
                'Choose an enabled model allowed by the current model cost mode ('
                    . AIConfig::modelCostModeLabel($this->store->costMode())
                    . ') before generating a course.',
                422
            );
        }

        $dailyLimit = $this->intSetting('jobs_per_admin_per_day', 20, 1, 100);
        if ($this->store->dailyJobCount($adminId) >= $dailyLimit) {
            return $this->error('The daily job limit for this admin has been reached.', 429);
        }
        $monthlyCap = $this->floatSetting('monthly_cost_cap_usd', 25.0, 0.0, 1000.0);
        if ($monthlyCap > 0 && $this->store->monthlyCost() >= $monthlyCap) {
            return $this->error('The monthly AI cost cap has been reached.', 429);
        }

        $lockName = 'ld_ai_create_' . $adminId;
        $locked = false;
        $jobId = 0;
        try {
            $lockStmt = $this->pdo->prepare('SELECT GET_LOCK(?, 5)');
            $lockStmt->execute([$lockName]);
            $locked = (int) $lockStmt->fetchColumn() === 1;
            if (!$locked) {
                return $this->error('The AI job queue is busy. Try again.', 429);
            }

            $this->pdo->beginTransaction();
            if ($this->store->dailyJobCount($adminId) >= $dailyLimit) {
                $this->pdo->rollBack();
                return $this->error('The daily job limit for this admin has been reached.', 429);
            }
            if ($monthlyCap > 0 && $this->store->monthlyCost() >= $monthlyCap) {
                $this->pdo->rollBack();
                return $this->error('The monthly AI cost cap has been reached.', 429);
            }

            $jobId = $this->store->create([
                'created_by' => $adminId,
                'course_owner_id' => $ownerId,
                'learner_id' => $learnerId,
                'model' => (string) $model['model_slug'],
                'prompt_version' => AICoursePrompts::VERSION,
                'status' => 'generating',
                'current_step' => 'outline',
                'total_steps' => 1,
                'brief' => $brief,
                'context' => $context,
            ], [
                ['key' => 'outline', 'type' => 'outline', 'status' => 'pending'],
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The AI job could not be created.', 500);
        } finally {
            if ($locked) {
                try {
                    $this->pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
                } catch (Throwable $e) {
                    // The connection releases the lock when the request ends.
                }
            }
        }

        $this->audit($adminId, 'ai_job.created', $jobId, [
            'model' => (string) $model['model_slug'],
            'learner_id' => $learnerId,
            'owner_id' => $ownerId,
        ]);

        $advance = $this->advance($jobId, $adminId);
        if (!$advance['success']) {
            $advance['job_id'] = $jobId;
            return $advance;
        }

        return [
            'success' => true,
            'message' => 'The outline is ready for review.',
            'job_id' => $jobId,
            'job' => $advance['job'] ?? $this->store->snapshot($jobId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listJobs(int $limit = 25): array
    {
        return $this->store->listJobs($limit);
    }

    /**
     * Generate the next pending step.
     *
     * @return array<string, mixed>
     */
    public function advance(int $jobId, int $adminId): array
    {
        if ($jobId <= 0) {
            return $this->error('A valid job id is required.', 422);
        }

        $step = null;
        $messages = [];
        $schema = null;
        $schemaName = '';
        $maxTokens = 0;

        try {
            $this->pdo->beginTransaction();
            $job = $this->store->get($jobId, true);
            if ($job === null) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }

            $status = (string) $job['status'];
            if (in_array($status, ['ready', 'applied', 'cancelled'], true)) {
                $this->pdo->commit();
                return [
                    'success' => true,
                    'message' => $status === 'ready' ? 'The course is ready for review.' : 'The job is already finished.',
                    'job' => $this->store->snapshot($jobId),
                ];
            }
            if ($status === 'draft') {
                $this->pdo->commit();
                return $this->error('Approve or edit the outline before expanding the course.', 409);
            }
            if ($status === 'failed') {
                $this->pdo->commit();
                return $this->error('Retry the failed step before advancing the job.', 409);
            }

            $running = $this->store->runningStep($jobId, true);
            if ($running !== null) {
                if (!$this->isStale($running)) {
                    $this->pdo->commit();
                    return [
                        'success' => false,
                        'busy' => true,
                        'message' => 'A step is already running. Wait for it to finish.',
                        'job' => $this->store->snapshot($jobId),
                    ];
                }

                $message = 'The previous step was interrupted. Retry the failed step.';
                $this->store->updateStep((int) $running['id'], [
                    'status' => 'failed',
                    'error_message' => $message,
                    'finished_at' => $this->now(),
                ]);
                $this->store->updateJob($jobId, [
                    'status' => 'failed',
                    'error_message' => $message,
                    'current_step' => (string) $running['step_key'],
                ]);
                $this->pdo->commit();
                $this->audit($adminId, 'ai_job.step.stale', $jobId, [
                    'step' => (string) $running['step_key'],
                    'type' => (string) $running['step_type'],
                ]);
                return [
                    'success' => false,
                    'message' => $message,
                    'http_status' => 409,
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            $step = $this->store->nextPendingStep($jobId, true);
            if ($step === null) {
                $this->store->updateJob($jobId, [
                    'status' => 'ready',
                    'current_step' => 'complete',
                    'completed_steps' => (int) $job['total_steps'],
                    'error_message' => null,
                ]);
                $this->pdo->commit();
                $this->audit($adminId, 'ai_job.ready', $jobId, ['model' => (string) $job['model']]);
                return [
                    'success' => true,
                    'message' => 'The course is ready for review.',
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            if ((string) $step['step_type'] !== 'quality') {
                $prepared = $this->prepareStep($job, $step);
                if (isset($prepared['error'])) {
                    $message = (string) $prepared['error'];
                    $this->store->updateStep((int) $step['id'], [
                        'status' => 'failed',
                        'error_message' => $message,
                        'finished_at' => $this->now(),
                    ]);
                    $this->store->updateJob($jobId, [
                        'status' => 'failed',
                        'current_step' => (string) $step['step_key'],
                        'error_message' => $message,
                    ]);
                    $this->pdo->commit();
                    $this->audit($adminId, 'ai_job.step.failed', $jobId, [
                        'step' => (string) $step['step_key'],
                        'type' => (string) $step['step_type'],
                        'reason' => 'preflight',
                    ]);
                    return [
                        'success' => false,
                        'message' => $message,
                        'http_status' => 422,
                        'job' => $this->store->snapshot($jobId),
                    ];
                }

                $messages = $prepared['messages'];
                $schema = $prepared['schema'];
                $schemaName = $prepared['schema_name'];
                $maxTokens = (int) $prepared['max_tokens'];
            }

            $this->store->updateStep((int) $step['id'], [
                'status' => 'running',
                'attempt_count' => (int) $step['attempt_count'] + 1,
                'started_at' => $this->now(),
                'error_message' => null,
            ]);
            $this->store->updateJob($jobId, [
                'status' => 'generating',
                'current_step' => (string) $step['step_key'],
                'error_message' => null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The next generation step could not be started.', 500);
        }

        if ($step === null) {
            return $this->error('No generation step is available.', 500);
        }

        if ((string) $step['step_type'] === 'quality') {
            $result = $this->runQuality($jobId);
        } else {
            $result = $this->callModel($jobId, $step, $messages, $schema, $schemaName, $maxTokens, $adminId);
        }

        return $this->finishStep($jobId, (int) $step['id'], (string) $step['step_key'], $result, $adminId);
    }

    /**
     * Accept or replace the outline and create module/evaluation/quality steps.
     *
     * @param array<string, mixed> $outline
     * @return array<string, mixed>
     */
    public function approveOutline(int $jobId, int $adminId, array $outline): array
    {
        try {
            $this->pdo->beginTransaction();
            $job = $this->store->get($jobId, true);
            if ($job === null) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }
            if ((string) $job['status'] !== 'draft') {
                $this->pdo->rollBack();
                return $this->error('Only a job waiting for outline review can be approved.', 409);
            }

            $outlineStep = $this->store->step($jobId, 'outline', true);
            if ($outlineStep === null || (string) $outlineStep['status'] !== 'done') {
                $this->pdo->rollBack();
                return $this->error('The outline has not been generated yet.', 409);
            }

            $brief = $this->store->decode($job['brief_json']);
            if (!is_array($brief)) {
                $this->pdo->rollBack();
                return $this->error('The job brief is unavailable.', 500);
            }
            $context = $this->store->decode($job['context_json']);

            $validator = new AICourseBlueprint(
                $this->settings,
                $this->store->activeSkills(),
                $brief,
                $this->allowedSkillIdsFromContext($context)
            );
            $validation = $validator->validateOutline($outline);
            if (!$validation['success']) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => $this->validationMessage($validation),
                    'http_status' => 422,
                    'errors' => $validation['errors'] ?? [],
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            $validated = $validation['outline'];
            $moduleCount = count($validated['modules']);
            $requiredCalls = 1 + $moduleCount + 1;
            $maxCalls = $this->intSetting('max_calls_per_job', 12, 1, 30);
            if ($requiredCalls > $maxCalls) {
                $this->pdo->rollBack();
                return $this->error(
                    "This outline needs {$requiredCalls} provider calls, above the configured maximum of {$maxCalls}.",
                    422
                );
            }

            $this->store->deleteStepsExcept($jobId, 'outline');
            for ($index = 1; $index <= $moduleCount; $index++) {
                $this->store->insertStep($jobId, 'module-' . $index, 'module', 'pending');
            }
            $this->store->insertStep($jobId, 'evaluation', 'evaluation', 'pending');
            $this->store->insertStep($jobId, 'quality', 'quality', 'pending');

            $blueprint = [
                'version' => 1,
                'outline' => $validated,
                'course' => $validated['course'],
                'modules' => [],
                'evaluation' => null,
                'quality' => null,
                'outline_warnings' => $validated['warnings'] ?? [],
            ];

            $this->store->updateStep((int) $outlineStep['id'], [
                'response_json' => $this->store->encode($validated),
            ]);
            $this->store->updateJob($jobId, [
                'status' => 'generating',
                'current_step' => 'module-1',
                'total_steps' => 1 + $moduleCount + 2,
                'completed_steps' => 1,
                'blueprint_json' => $this->store->encode($blueprint),
                'error_message' => null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The outline could not be approved.', 500);
        }

        $this->audit($adminId, 'ai_job.approved', $jobId, [
            'modules' => $moduleCount,
            'model' => (string) $job['model'],
        ]);

        return [
            'success' => true,
            'message' => 'Outline approved. Module generation can start.',
            'job' => $this->store->snapshot($jobId),
        ];
    }

    /**
     * Reset the latest failed step and immediately try it again.
     *
     * @return array<string, mixed>
     */
    public function retry(int $jobId, int $adminId): array
    {
        try {
            $this->pdo->beginTransaction();
            $job = $this->store->get($jobId, true);
            if ($job === null) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }
            if ((string) $job['status'] !== 'failed') {
                $this->pdo->rollBack();
                return $this->error('Only a failed job can be retried.', 409);
            }

            $failed = $this->store->latestFailedStep($jobId, true);
            if ($failed === null) {
                $this->pdo->rollBack();
                return $this->error('No failed step was found to retry.', 409);
            }

            $this->store->updateStep((int) $failed['id'], [
                'status' => 'pending',
                'response_json' => null,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'estimated_cost_usd' => 0,
                'error_message' => null,
                'started_at' => null,
                'finished_at' => null,
            ]);
            $this->store->updateJob($jobId, [
                'status' => 'generating',
                'current_step' => (string) $failed['step_key'],
                'error_message' => null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The failed step could not be reset.', 500);
        }

        $this->audit($adminId, 'ai_job.retried', $jobId, [
            'step' => (string) $failed['step_key'],
            'type' => (string) $failed['step_type'],
        ]);

        return $this->advance($jobId, $adminId);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(int $jobId, int $adminId): array
    {
        try {
            $this->pdo->beginTransaction();
            $job = $this->store->get($jobId, true);
            if ($job === null) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }
            $status = (string) $job['status'];
            if ($status === 'applied') {
                $this->pdo->rollBack();
                return $this->error('An applied job cannot be cancelled.', 409);
            }
            if ($status === 'cancelled') {
                $this->pdo->commit();
                return [
                    'success' => true,
                    'message' => 'The job is already cancelled.',
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            $running = $this->store->runningStep($jobId, true);
            if ($running !== null && !$this->isStale($running)) {
                $this->pdo->rollBack();
                return $this->error('Wait for the current step to finish before cancelling.', 409);
            }
            if ($running !== null) {
                $this->store->updateStep((int) $running['id'], [
                    'status' => 'failed',
                    'error_message' => 'Cancelled after an interrupted step.',
                    'finished_at' => $this->now(),
                ]);
            }

            foreach ($this->store->steps($jobId, true) as $step) {
                if (in_array((string) $step['status'], ['pending', 'running'], true)) {
                    $this->store->updateStep((int) $step['id'], [
                        'status' => 'skipped',
                        'finished_at' => $this->now(),
                    ]);
                }
            }
            $this->store->updateJob($jobId, [
                'status' => 'cancelled',
                'current_step' => 'cancelled',
                'error_message' => null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The job could not be cancelled.', 500);
        }

        $this->audit($adminId, 'ai_job.cancelled', $jobId);
        return [
            'success' => true,
            'message' => 'The job was cancelled.',
            'job' => $this->store->snapshot($jobId),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJob(int $jobId): ?array
    {
        return $this->store->snapshot($jobId);
    }

    /**
     * The context catalog contains only current gaps and explicitly selected
     * skills. A missing catalog means this is an older job, so validation falls
     * back to the full active skill list.
     *
     * @return array<int, int>|null
     */
    private function allowedSkillIdsFromContext(mixed $context): ?array
    {
        if (!is_array($context)) {
            return null;
        }
        $skills = is_array($context['skills'] ?? null) ? $context['skills'] : [];
        if (!array_key_exists('catalog', $skills) || !is_array($skills['catalog'])) {
            return null;
        }

        $ids = [];
        foreach ($skills['catalog'] as $skill) {
            if (!is_array($skill)) {
                continue;
            }
            $id = (int) ($skill['skill_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<string, mixed> $job
     * @param array<string, mixed> $step
     * @return array<string, mixed>
     */
    private function prepareStep(array $job, array $step): array
    {
        $brief = $this->store->decode($job['brief_json']);
        $context = $this->store->decode($job['context_json']);
        $blueprint = $this->store->decode($job['blueprint_json']);
        if (!is_array($brief) || !is_array($context)) {
            return ['error' => 'The job context is unavailable.'];
        }
        if (!is_array($blueprint)) {
            $blueprint = [];
        }

        $type = (string) $step['step_type'];
        $messages = [];
        $schema = null;
        $schemaName = '';
        if ($type === 'outline') {
            $messages = AICoursePrompts::outlineMessages($brief, $context);
            $schema = AICoursePrompts::outlineSchema();
            $schemaName = 'course_outline';
        } elseif ($type === 'module') {
            $outline = is_array($blueprint['outline'] ?? null) ? $blueprint['outline'] : [];
            $modules = is_array($outline['modules'] ?? null) ? $outline['modules'] : [];
            $index = $this->moduleIndex((string) $step['step_key']);
            if (!isset($modules[$index])) {
                return ['error' => 'The module outline is missing.'];
            }
            $prior = [];
            foreach (array_slice(is_array($blueprint['modules'] ?? null) ? $blueprint['modules'] : [], 0, $index) as $module) {
                if (is_array($module)) {
                    $prior[] = [
                        'title' => (string) ($module['title'] ?? ''),
                        'lessons' => array_map(
                            static fn($lesson): string => is_array($lesson) ? (string) ($lesson['title'] ?? '') : '',
                            is_array($module['lessons'] ?? null) ? $module['lessons'] : []
                        ),
                    ];
                }
            }
            $messages = AICoursePrompts::moduleMessages(
                $brief,
                $context,
                is_array($outline['course'] ?? null) ? $outline['course'] : [],
                $modules[$index],
                $prior
            );
            $schema = AICoursePrompts::moduleSchema();
            $schemaName = 'course_module';
        } elseif ($type === 'evaluation') {
            $outline = is_array($blueprint['outline'] ?? null) ? $blueprint['outline'] : [];
            $messages = AICoursePrompts::evaluationMessages(
                $brief,
                $context,
                is_array($outline['course'] ?? null) ? $outline['course'] : [],
                is_array($blueprint['modules'] ?? null) ? array_values($blueprint['modules']) : []
            );
            $schema = AICoursePrompts::evaluationSchema();
            $schemaName = 'course_evaluation';
        } else {
            return ['error' => 'That job step does not call the provider.'];
        }

        $maxTokens = $this->preflight($job, $messages);
        if (isset($maxTokens['error'])) {
            return ['error' => $maxTokens['error']];
        }

        return [
            'messages' => $messages,
            'schema' => $schema,
            'schema_name' => $schemaName,
            'max_tokens' => $maxTokens['max_tokens'],
        ];
    }

    /**
     * @param array<string, mixed> $job
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{max_tokens: int}|array{error: string}
     */
    private function preflight(array $job, array $messages): array
    {
        $maxCalls = $this->intSetting('max_calls_per_job', 12, 1, 30);
        $callsUsed = $this->store->callsUsed((int) $job['id']);
        if ($callsUsed >= $maxCalls) {
            return ['error' => 'This job has reached its provider call limit.'];
        }

        $maxOutput = $this->intSetting('max_output_tokens_per_step', 6000, 500, 20000);
        $completionUsed = (int) $job['completion_tokens'];
        $completionCeiling = $maxCalls * $maxOutput;
        if ($completionUsed >= $completionCeiling) {
            return ['error' => 'This job has reached its completion-token limit.'];
        }
        $maxTokens = min($maxOutput, $completionCeiling - $completionUsed);
        if ($maxTokens < 500) {
            return ['error' => 'This job has too little token budget left for another step.'];
        }

        $model = $this->store->modelBySlug((string) $job['model']);
        if ($model === null) {
            return ['error' => 'The job model is no longer enabled or is blocked by the current model cost mode.'];
        }

        $promptCharacters = 0;
        foreach ($messages as $message) {
            $promptCharacters += mb_strlen((string) ($message['content'] ?? ''));
        }
        $estimatedPromptTokens = (int) ceil($promptCharacters / 4);
        $estimatedCost = $this->store->costForUsage($model, $estimatedPromptTokens, $maxTokens);

        $perJobCap = $this->floatSetting('per_job_cost_cap_usd', 0.50, 0.0, 100.0);
        if ($perJobCap > 0 && ((float) $job['estimated_cost_usd'] + $estimatedCost) > $perJobCap) {
            return ['error' => 'The next step would exceed the per-job cost cap.'];
        }

        $monthlyCap = $this->floatSetting('monthly_cost_cap_usd', 25.0, 0.0, 1000.0);
        if ($monthlyCap > 0 && ($this->store->monthlyCost() + $estimatedCost) > $monthlyCap) {
            return ['error' => 'The next step would exceed the monthly cost cap.'];
        }

        return ['max_tokens' => $maxTokens];
    }

    /**
     * @param array<string, mixed> $step
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed>|null $schema
     * @return array<string, mixed>
     */
    private function callModel(
        int $jobId,
        array $step,
        array $messages,
        ?array $schema,
        string $schemaName,
        int $maxTokens,
        int $adminId
    ): array {
        $job = $this->store->get($jobId);
        if ($job === null) {
            return ['success' => false, 'message' => 'Job not found.', 'usage' => $this->zeroUsage()];
        }

        $options = [
            'model' => (string) $job['model'],
            'max_tokens' => $maxTokens,
            'temperature' => $this->floatSetting('temperature', 0.3, 0.0, 1.0),
        ];
        if ($schema !== null) {
            $options['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ];
        }

        try {
            $response = $this->client->chat($messages, $options, $adminId);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'The provider request failed. Retry the step.',
                'usage' => $this->zeroUsage(),
                'model' => (string) $job['model'],
            ];
        }

        $usage = is_array($response['usage'] ?? null) ? $response['usage'] : $this->zeroUsage();
        $model = (string) ($response['model'] ?? $job['model']);
        if (!($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($response['message'] ?? 'The provider request failed. Retry the step.'),
                'usage' => $usage,
                'model' => $model,
            ];
        }

        $content = $response['content'] ?? '';
        if (!is_string($content)) {
            $content = '';
        }
        $decoded = $this->decodeContent($content);
        if ($decoded === null) {
            return [
                'success' => false,
                'message' => 'The model returned unreadable JSON. Retry the step.',
                'usage' => $usage,
                'model' => $model,
            ];
        }

        $brief = $this->store->decode($job['brief_json']);
        $context = $this->store->decode($job['context_json']);
        $blueprint = $this->store->decode($job['blueprint_json']);
        if (!is_array($brief)) {
            $brief = [];
        }
        if (!is_array($blueprint)) {
            $blueprint = [];
        }
        $validator = new AICourseBlueprint(
            $this->settings,
            $this->store->activeSkills(),
            $brief,
            $this->allowedSkillIdsFromContext($context)
        );

        $type = (string) $step['step_type'];
        if ($type === 'outline') {
            $validation = $validator->validateOutline($decoded);
        } elseif ($type === 'module') {
            $outline = is_array($blueprint['outline'] ?? null) ? $blueprint['outline'] : [];
            $modules = is_array($outline['modules'] ?? null) ? $outline['modules'] : [];
            $index = $this->moduleIndex((string) $step['step_key']);
            if (!isset($modules[$index])) {
                return [
                    'success' => false,
                    'message' => 'The module outline is missing. Retry the job.',
                    'usage' => $usage,
                    'model' => $model,
                ];
            }
            $validation = $validator->validateModule(
                $decoded,
                $modules[$index],
                is_array($outline['course'] ?? null) ? $outline['course'] : []
            );
        } else {
            $outline = is_array($blueprint['outline'] ?? null) ? $blueprint['outline'] : [];
            $validation = $validator->validateEvaluation(
                $decoded,
                is_array($outline['course'] ?? null) ? $outline['course'] : [],
                is_array($blueprint['modules'] ?? null) ? array_values($blueprint['modules']) : []
            );
        }

        if (!$validation['success']) {
            return [
                'success' => false,
                'message' => $this->validationMessage($validation),
                'usage' => $usage,
                'model' => $model,
            ];
        }

        $data = $type === 'outline'
            ? $validation['outline']
            : ($type === 'module' ? $validation['module'] : $validation['evaluation']);

        return [
            'success' => true,
            'message' => 'Step generated.',
            'data' => $data,
            'usage' => $usage,
            'model' => $model,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runQuality(int $jobId): array
    {
        $job = $this->store->get($jobId);
        if ($job === null) {
            return ['success' => false, 'message' => 'Job not found.', 'usage' => $this->zeroUsage()];
        }
        $brief = $this->store->decode($job['brief_json']);
        $context = $this->store->decode($job['context_json']);
        $blueprint = $this->store->decode($job['blueprint_json']);
        if (!is_array($brief) || !is_array($context) || !is_array($blueprint)) {
            return ['success' => false, 'message' => 'The course blueprint is unavailable.', 'usage' => $this->zeroUsage()];
        }

        $validator = new AICourseBlueprint(
            $this->settings,
            $this->store->activeSkills(),
            $brief,
            $this->allowedSkillIdsFromContext($context)
        );
        $quality = $validator->quality($blueprint, $context);

        return [
            'success' => true,
            'message' => 'Quality checks completed.',
            'data' => $quality,
            'usage' => $this->zeroUsage(),
            'model' => (string) $job['model'],
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function finishStep(int $jobId, int $stepId, string $stepKey, array $result, int $adminId): array
    {
        $usage = is_array($result['usage'] ?? null) ? $result['usage'] : $this->zeroUsage();
        $promptTokens = max(0, (int) ($usage['prompt_tokens'] ?? 0));
        $completionTokens = max(0, (int) ($usage['completion_tokens'] ?? 0));
        $modelSlug = (string) ($result['model'] ?? '');
        $model = $modelSlug !== '' ? $this->store->modelBySlug($modelSlug) : null;

        try {
            $this->pdo->beginTransaction();
            $job = $this->store->get($jobId, true);
            if ($job === null) {
                $this->pdo->rollBack();
                return $this->error('Job not found.', 404);
            }
            $step = $this->store->step($jobId, $stepKey, true);
            if ($step === null) {
                $this->pdo->rollBack();
                return $this->error('The generation step no longer exists.', 409);
            }
            if ((string) $step['status'] !== 'running') {
                $this->pdo->rollBack();
                return $this->error('The generation step is no longer running.', 409);
            }

            if ($model === null) {
                $model = $this->store->modelBySlug((string) $job['model']);
            }
            $cost = $model !== null ? $this->store->costForUsage($model, $promptTokens, $completionTokens) : 0.0;
            $totalPrompt = (int) $job['prompt_tokens'] + $promptTokens;
            $totalCompletion = (int) $job['completion_tokens'] + $completionTokens;
            $totalCost = (float) $job['estimated_cost_usd'] + $cost;

            if ((string) $job['status'] === 'cancelled') {
                $this->store->updateStep($stepId, [
                    'status' => 'skipped',
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'estimated_cost_usd' => $cost,
                    'error_message' => 'Cancelled before this step finished.',
                    'finished_at' => $this->now(),
                ]);
                $this->store->updateJob($jobId, [
                    'prompt_tokens' => $totalPrompt,
                    'completion_tokens' => $totalCompletion,
                    'estimated_cost_usd' => $totalCost,
                ]);
                $this->pdo->commit();
                return [
                    'success' => false,
                    'message' => 'The job was cancelled before this step finished.',
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            if (!($result['success'] ?? false)) {
                $message = (string) ($result['message'] ?? 'The generation step failed.');
                $this->store->updateStep($stepId, [
                    'status' => 'failed',
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'estimated_cost_usd' => $cost,
                    'error_message' => $message,
                    'finished_at' => $this->now(),
                ]);
                $this->store->updateJob($jobId, [
                    'status' => 'failed',
                    'current_step' => $stepKey,
                    'prompt_tokens' => $totalPrompt,
                    'completion_tokens' => $totalCompletion,
                    'estimated_cost_usd' => $totalCost,
                    'error_message' => $message,
                ]);
                $this->pdo->commit();
                $this->audit($adminId, 'ai_job.step.failed', $jobId, [
                    'step' => $stepKey,
                    'type' => (string) $step['step_type'],
                    'model' => $modelSlug !== '' ? $modelSlug : (string) $job['model'],
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                ]);
                return [
                    'success' => false,
                    'message' => $message,
                    'http_status' => 200,
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            $blueprint = $this->store->decode($job['blueprint_json']);
            if (!is_array($blueprint)) {
                $blueprint = [];
            }
            $applied = $this->applyStepResult($job, $step, $result['data'] ?? [], $blueprint);
            if (isset($applied['error'])) {
                $message = (string) $applied['error'];
                $this->store->updateStep($stepId, [
                    'status' => 'failed',
                    'prompt_tokens' => $promptTokens,
                    'completion_tokens' => $completionTokens,
                    'estimated_cost_usd' => $cost,
                    'error_message' => $message,
                    'finished_at' => $this->now(),
                ]);
                $this->store->updateJob($jobId, [
                    'status' => 'failed',
                    'current_step' => $stepKey,
                    'prompt_tokens' => $totalPrompt,
                    'completion_tokens' => $totalCompletion,
                    'estimated_cost_usd' => $totalCost,
                    'error_message' => $message,
                ]);
                $this->pdo->commit();
                return [
                    'success' => false,
                    'message' => $message,
                    'http_status' => 200,
                    'job' => $this->store->snapshot($jobId),
                ];
            }

            $this->store->updateStep($stepId, [
                'status' => 'done',
                'response_json' => $this->store->encode($result['data'] ?? []),
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'estimated_cost_usd' => $cost,
                'error_message' => null,
                'finished_at' => $this->now(),
            ]);
            $this->store->updateJob($jobId, [
                'status' => $applied['status'],
                'current_step' => $applied['current_step'],
                'total_steps' => $applied['total_steps'],
                'completed_steps' => $applied['completed_steps'],
                'blueprint_json' => $this->store->encode($applied['blueprint']),
                'prompt_tokens' => $totalPrompt,
                'completion_tokens' => $totalCompletion,
                'estimated_cost_usd' => $totalCost,
                'error_message' => null,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return $this->error('The generated step could not be saved.', 500);
        }

        $this->audit($adminId, 'ai_job.step.completed', $jobId, [
            'step' => $stepKey,
            'type' => (string) $step['step_type'],
            'model' => $modelSlug !== '' ? $modelSlug : (string) $job['model'],
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ]);
        if ($applied['status'] === 'ready') {
            $this->audit($adminId, 'ai_job.ready', $jobId, [
                'model' => (string) $job['model'],
                'blocking' => count($applied['blueprint']['quality']['blocking'] ?? []),
            ]);
        }

        return [
            'success' => true,
            'message' => $applied['status'] === 'ready'
                ? 'Generation complete. Review the course and quality report.'
                : 'Step completed.',
            'job' => $this->store->snapshot($jobId),
        ];
    }

    /**
     * @param array<string, mixed> $job
     * @param array<string, mixed> $step
     * @param array<string, mixed> $blueprint
     * @return array{blueprint: array<string, mixed>, status: string, current_step: string, completed_steps: int, total_steps: int}|array{error: string}
     */
    private function applyStepResult(array $job, array $step, mixed $data, array $blueprint): array
    {
        $type = (string) $step['step_type'];
        $completed = (int) $job['completed_steps'];
        $total = (int) $job['total_steps'];

        if ($type === 'outline') {
            if (!is_array($data)) {
                return ['error' => 'The outline result is invalid.'];
            }
            $blueprint = [
                'version' => 1,
                'outline' => $data,
                'course' => $data['course'] ?? [],
                'modules' => [],
                'evaluation' => null,
                'quality' => null,
                'outline_warnings' => $data['warnings'] ?? [],
            ];
            return [
                'blueprint' => $blueprint,
                'status' => 'draft',
                'current_step' => 'outline-review',
                'completed_steps' => 1,
                'total_steps' => 1,
            ];
        }

        if ($type === 'module') {
            if (!is_array($data)) {
                return ['error' => 'The module result is invalid.'];
            }
            $outline = is_array($blueprint['outline'] ?? null) ? $blueprint['outline'] : [];
            $outlineModules = is_array($outline['modules'] ?? null) ? $outline['modules'] : [];
            $index = $this->moduleIndex((string) $step['step_key']);
            if (!isset($outlineModules[$index])) {
                return ['error' => 'The module outline is missing.'];
            }
            $modules = is_array($blueprint['modules'] ?? null) ? $blueprint['modules'] : [];
            $modules[$index] = $data;
            ksort($modules);
            $blueprint['modules'] = array_values($modules);
            $completed++;
            $next = $index + 1 < count($outlineModules) ? 'module-' . ($index + 2) : 'evaluation';

            return [
                'blueprint' => $blueprint,
                'status' => 'generating',
                'current_step' => $next,
                'completed_steps' => $completed,
                'total_steps' => $total,
            ];
        }

        if ($type === 'evaluation') {
            if (!is_array($data)) {
                return ['error' => 'The evaluation result is invalid.'];
            }
            $blueprint['evaluation'] = $data;
            return [
                'blueprint' => $blueprint,
                'status' => 'generating',
                'current_step' => 'quality',
                'completed_steps' => $completed + 1,
                'total_steps' => $total,
            ];
        }

        if ($type === 'quality') {
            if (!is_array($data)) {
                return ['error' => 'The quality report is invalid.'];
            }
            $blueprint['quality'] = $data;
            return [
                'blueprint' => $blueprint,
                'status' => 'ready',
                'current_step' => 'complete',
                'completed_steps' => $completed + 1,
                'total_steps' => $total,
            ];
        }

        return ['error' => 'Unknown generation step type.'];
    }

    /**
     * @param array<string, mixed> $brief
     * @param int[] $selectedSkillIds
     * @param string[] $excludedEvidence
     * @return array<string, mixed>
     */
    private function normalizeBrief(array $brief, array $selectedSkillIds, array $excludedEvidence): array
    {
        $goal = trim((string) ($brief['goal'] ?? ''));
        if (mb_strlen($goal) < 10) {
            return ['error' => 'Describe the course goal in at least 10 characters.'];
        }
        if (mb_strlen($goal) > 1000) {
            $goal = mb_substr($goal, 0, 1000);
        }

        $sourceNotes = trim((string) ($brief['source_notes'] ?? ''));
        if (mb_strlen($sourceNotes) > 4000) {
            $sourceNotes = mb_substr($sourceNotes, 0, 4000);
        }

        $maxModules = $this->intSetting('max_modules', 8, 1, 20);
        $maxLessons = $this->intSetting('max_lessons_per_module', 6, 1, 20);
        $maxEvaluation = $this->intSetting('max_evaluation_questions', 20, 1, 50);

        $moduleCount = max(1, min($maxModules, (int) ($brief['module_count'] ?? min(4, $maxModules))));
        $lessonsPerModule = max(1, min($maxLessons, (int) ($brief['lessons_per_module'] ?? min(3, $maxLessons))));
        $difficulty = strtolower(trim((string) ($brief['difficulty'] ?? 'intermediate')));
        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) {
            $difficulty = 'intermediate';
        }

        $skills = [];
        foreach (array_merge($selectedSkillIds, is_array($brief['selected_skill_ids'] ?? null) ? $brief['selected_skill_ids'] : []) as $id) {
            if (is_int($id) || (is_string($id) && ctype_digit($id))) {
                $skills[(int) $id] = (int) $id;
            }
        }

        $excluded = [];
        $allowedExcluded = [
            'external_skills', 'completed_courses', 'current_courses',
            'quiz_performance', 'program_assessments', 'snapshot',
        ];
        foreach ($excludedEvidence as $key) {
            if (is_string($key) && in_array($key, $allowedExcluded, true)) {
                $excluded[$key] = $key;
            }
        }

        $model = trim((string) ($brief['model'] ?? ''));
        if ($model !== '' && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{1,159}$/', $model) !== 1) {
            return ['error' => 'The selected model is not valid.'];
        }

        return [
            'goal' => $goal,
            'source_notes' => $sourceNotes,
            'module_count' => $moduleCount,
            'lessons_per_module' => $lessonsPerModule,
            'difficulty' => $difficulty,
            'model' => $model,
            'selected_skill_ids' => array_values(array_slice($skills, 0, 50)),
            'excluded_evidence' => array_values($excluded),
            'evaluation_question_count' => max(5, min($maxEvaluation, $moduleCount * 2)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function activeEmployee(int $employeeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT employee_id, first_name, last_name
               FROM em_employees
              WHERE employee_id = :employee_id
                AND is_archived = 0
                AND employment_status = 'Active'
              LIMIT 1"
        );
        $stmt->execute([':employee_id' => $employeeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function moduleIndex(string $stepKey): int
    {
        if (preg_match('/^module-(\d+)$/', $stepKey, $matches) === 1) {
            return max(0, (int) $matches[1] - 1);
        }
        return 0;
    }

    private function isStale(array $step): bool
    {
        $started = $step['started_at'] ?? null;
        if ($started === null || $started === '') {
            return true;
        }
        $timeout = $this->intSetting('request_timeout', 90, 5, 300);
        $threshold = max(120, ($timeout * 2) + 30);

        return $this->store->stepAgeSeconds((string) $started) > $threshold;
    }

    private function decodeContent(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function validationMessage(array $validation): string
    {
        $errors = is_array($validation['errors'] ?? null) ? $validation['errors'] : [];
        if ($errors !== []) {
            return mb_substr(implode(' ', array_slice($errors, 0, 3)), 0, 500);
        }
        return (string) ($validation['message'] ?? 'The generated content did not pass validation.');
    }

    /**
     * @return array{prompt_tokens: int, completion_tokens: int, total_tokens: int}
     */
    private function zeroUsage(): array
    {
        return ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
    }

    private function now(): string
    {
        return AICourseJobStore::NOW;
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = $this->settings[$key] ?? $default;
        return max($min, min($max, (int) $value));
    }

    private function floatSetting(string $key, float $default, float $min, float $max): float
    {
        $value = $this->settings[$key] ?? $default;
        if (!is_numeric($value)) {
            return $default;
        }
        return max($min, min($max, (float) $value));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function audit(int $employeeId, string $action, int $jobId, array $metadata = []): void
    {
        try {
            $this->audit->log($employeeId, $action, 'ai_job', (string) $jobId, $metadata);
        } catch (Throwable $e) {
            // Audit failure must not roll back a completed generation step.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $message, int $status): array
    {
        return [
            'success' => false,
            'message' => $message,
            'http_status' => $status,
        ];
    }
}
