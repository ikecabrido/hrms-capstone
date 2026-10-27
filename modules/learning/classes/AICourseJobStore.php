<?php
/**
 * AICourseJobStore - persistence helpers for staged AI course generation.
 *
 * Provider calls are never made while a database transaction is open. The
 * generator uses this store to lock the job, reserve the next step, release the
 * lock, call the provider, and then persist the result in a short transaction.
 */

require_once __DIR__ . '/AIConfig.php';

class AICourseJobStore
{
    /** Sentinel used to store the database clock, avoiding PHP/MySQL timezone drift. */
    public const NOW = '__AI_NOW__';

    private PDO $pdo;
    private string $costMode;

    public function __construct(PDO $pdo, ?string $costMode = null)
    {
        $this->pdo = $pdo;
        $this->costMode = AIConfig::normalizeModelCostMode(
            $costMode ?? AIConfig::modelCostMode($pdo)
        );
    }

    public function costMode(): string
    {
        return $this->costMode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $jobId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM ld_ai_course_job WHERE id = :id LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(int $jobId, bool $forUpdate = false): array
    {
        $sql = 'SELECT * FROM ld_ai_course_job_step WHERE job_id = :job_id ORDER BY id ASC';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':job_id' => $jobId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function step(int $jobId, string $stepKey, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM ld_ai_course_job_step
                 WHERE job_id = :job_id AND step_key = :step_key
                 LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':job_id' => $jobId, ':step_key' => $stepKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function runningStep(int $jobId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM ld_ai_course_job_step
                 WHERE job_id = :job_id AND status = 'running'
                 ORDER BY id ASC
                 LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':job_id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nextPendingStep(int $jobId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM ld_ai_course_job_step
                 WHERE job_id = :job_id AND status = 'pending'
                 ORDER BY id ASC
                 LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':job_id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestFailedStep(int $jobId, bool $forUpdate = false): ?array
    {
        $sql = "SELECT * FROM ld_ai_course_job_step
                 WHERE job_id = :job_id AND status = 'failed'
                 ORDER BY id DESC
                 LIMIT 1";
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':job_id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Create a job and its initial step list. The caller owns the transaction.
     *
     * @param array<string, mixed> $job
     * @param array<int, array{key: string, type: string, status?: string}> $steps
     */
    public function create(array $job, array $steps): int
    {
        $briefJson = $this->encode($job['brief'] ?? []);
        $contextJson = $this->encode($job['context'] ?? []);

        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_ai_course_job
                (created_by, course_owner_id, learner_id, provider, model,
                 prompt_version, status, current_step, total_steps, completed_steps,
                 brief_json, context_json)
             VALUES
                (:created_by, :course_owner_id, :learner_id, 'openrouter', :model,
                 :prompt_version, :status, :current_step, :total_steps, 0,
                 :brief_json, :context_json)"
        );
        $stmt->execute([
            ':created_by' => (int) ($job['created_by'] ?? 0),
            ':course_owner_id' => (int) ($job['course_owner_id'] ?? 0) ?: null,
            ':learner_id' => (int) ($job['learner_id'] ?? 0) ?: null,
            ':model' => (string) ($job['model'] ?? ''),
            ':prompt_version' => (string) ($job['prompt_version'] ?? 'v1'),
            ':status' => (string) ($job['status'] ?? 'generating'),
            ':current_step' => $job['current_step'] ?? null,
            ':total_steps' => (int) ($job['total_steps'] ?? 1),
            ':brief_json' => $briefJson,
            ':context_json' => $contextJson,
        ]);

        $jobId = (int) $this->pdo->lastInsertId();
        foreach ($steps as $step) {
            $this->insertStep(
                $jobId,
                (string) ($step['key'] ?? ''),
                (string) ($step['type'] ?? 'outline'),
                (string) ($step['status'] ?? 'pending')
            );
        }

        return $jobId;
    }

    public function insertStep(int $jobId, string $stepKey, string $stepType, string $status = 'pending'): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ld_ai_course_job_step (job_id, step_key, step_type, status)
             VALUES (:job_id, :step_key, :step_type, :status)
             ON DUPLICATE KEY UPDATE
                step_type = VALUES(step_type),
                status = VALUES(status),
                attempt_count = 0,
                request_json = NULL,
                response_json = NULL,
                prompt_tokens = 0,
                completion_tokens = 0,
                estimated_cost_usd = 0,
                error_message = NULL,
                started_at = NULL,
                finished_at = NULL"
        );
        $stmt->execute([
            ':job_id' => $jobId,
            ':step_key' => $stepKey,
            ':step_type' => $stepType,
            ':status' => $status,
        ]);
    }

    public function deleteStepsExcept(int $jobId, string $keepStepKey): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM ld_ai_course_job_step WHERE job_id = :job_id AND step_key <> :keep_step_key'
        );
        $stmt->execute([':job_id' => $jobId, ':keep_step_key' => $keepStepKey]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateJob(int $jobId, array $fields): void
    {
        $allowed = [
            'status', 'current_step', 'total_steps', 'completed_steps',
            'blueprint_json', 'prompt_tokens', 'completion_tokens',
            'estimated_cost_usd', 'error_message', 'applied_at', 'course_id',
        ];
        $this->update('ld_ai_course_job', 'id', $jobId, $fields, $allowed);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function updateStep(int $stepId, array $fields): void
    {
        $allowed = [
            'status', 'attempt_count', 'request_json', 'response_json',
            'prompt_tokens', 'completion_tokens', 'estimated_cost_usd',
            'error_message', 'started_at', 'finished_at',
        ];
        $this->update('ld_ai_course_job_step', 'id', $stepId, $fields, $allowed);
    }

    public function stepAgeSeconds(string $startedAt): int
    {
        $stmt = $this->pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, :started_at, NOW())');
        $stmt->execute([':started_at' => $startedAt]);
        $seconds = $stmt->fetchColumn();

        return $seconds === false || $seconds === null ? PHP_INT_MAX : (int) $seconds;
    }

    public function callsUsed(int $jobId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(attempt_count), 0) FROM ld_ai_course_job_step WHERE job_id = :job_id'
        );
        $stmt->execute([':job_id' => $jobId]);

        return (int) $stmt->fetchColumn();
    }

    public function dailyJobCount(int $adminId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM ld_ai_course_job
              WHERE created_by = :created_by AND created_at >= CURDATE()'
        );
        $stmt->execute([':created_by' => $adminId]);

        return (int) $stmt->fetchColumn();
    }

    public function monthlyCost(): float
    {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(estimated_cost_usd), 0)
               FROM ld_ai_course_job
              WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );

        return (float) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function modelBySlug(string $slug): ?array
    {
        $where = AIConfig::modelModeWhere($this->costMode);
        $stmt = $this->pdo->prepare(
            "SELECT id, model_slug, display_name, context_length, prompt_price, completion_price,
                    enabled, is_default, is_fallback
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND model_slug = :slug AND enabled = 1 AND {$where}
              LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function defaultModel(): ?array
    {
        $where = AIConfig::modelModeWhere($this->costMode);
        $stmt = $this->pdo->query(
            "SELECT id, model_slug, display_name, context_length, prompt_price, completion_price,
                    enabled, is_default, is_fallback
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1 AND {$where}
              ORDER BY is_default DESC, is_fallback DESC, id ASC
              LIMIT 1"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function activeSkills(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name FROM ld_skill WHERE status = 'active' ORDER BY name ASC"
        );
        $skills = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['name'] ?? ''));
            if ($id > 0 && $name !== '') {
                $skills[] = ['id' => $id, 'name' => $name];
            }
        }

        return $skills;
    }

    /**
     * Prices in ld_ai_model are per token.
     *
     * @param array<string, mixed> $model
     */
    public function costForUsage(array $model, int $promptTokens, int $completionTokens): float
    {
        $promptPrice = (float) ($model['prompt_price'] ?? 0);
        $completionPrice = (float) ($model['completion_price'] ?? 0);
        $cost = (max(0, $promptTokens) * $promptPrice)
            + (max(0, $completionTokens) * $completionPrice);

        return round(max(0.0, $cost), 6);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function snapshot(int $jobId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT j.*,
                    CONCAT(l.first_name, ' ', l.last_name) AS learner_name,
                    CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
                    CONCAT(c.first_name, ' ', c.last_name) AS creator_name
               FROM ld_ai_course_job j
               LEFT JOIN em_employees l ON l.employee_id = j.learner_id
               LEFT JOIN em_employees o ON o.employee_id = j.course_owner_id
               LEFT JOIN em_employees c ON c.employee_id = j.created_by
              WHERE j.id = :id
              LIMIT 1"
        );
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $steps = [];
        $outline = null;
        foreach ($this->steps($jobId) as $step) {
            if ((string) $step['step_key'] === 'outline') {
                $outline = $this->decode($step['response_json']);
            }
            $steps[] = [
                'id' => (int) $step['id'],
                'key' => (string) $step['step_key'],
                'type' => (string) $step['step_type'],
                'status' => (string) $step['status'],
                'attempt_count' => (int) $step['attempt_count'],
                'prompt_tokens' => (int) $step['prompt_tokens'],
                'completion_tokens' => (int) $step['completion_tokens'],
                'estimated_cost_usd' => (float) $step['estimated_cost_usd'],
                'error_message' => $step['error_message'] !== null ? (string) $step['error_message'] : null,
                'started_at' => $step['started_at'],
                'finished_at' => $step['finished_at'],
            ];
        }

        return [
            'id' => (int) $row['id'],
            'status' => (string) $row['status'],
            'model' => (string) $row['model'],
            'prompt_version' => (string) $row['prompt_version'],
            'current_step' => $row['current_step'] !== null ? (string) $row['current_step'] : null,
            'total_steps' => (int) $row['total_steps'],
            'completed_steps' => (int) $row['completed_steps'],
            'prompt_tokens' => (int) $row['prompt_tokens'],
            'completion_tokens' => (int) $row['completion_tokens'],
            'estimated_cost_usd' => (float) $row['estimated_cost_usd'],
            'error_message' => $row['error_message'] !== null ? (string) $row['error_message'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'applied_at' => $row['applied_at'],
            'course_id' => $row['course_id'] !== null ? (int) $row['course_id'] : null,
            'learner' => [
                'id' => $row['learner_id'] !== null ? (int) $row['learner_id'] : null,
                'name' => trim((string) ($row['learner_name'] ?? '')),
            ],
            'owner' => [
                'id' => $row['course_owner_id'] !== null ? (int) $row['course_owner_id'] : null,
                'name' => trim((string) ($row['owner_name'] ?? '')),
            ],
            'creator' => [
                'id' => $row['created_by'] !== null ? (int) $row['created_by'] : null,
                'name' => trim((string) ($row['creator_name'] ?? '')),
            ],
            'brief' => $this->decode($row['brief_json']),
            'context' => $this->decode($row['context_json']),
            'outline' => $outline,
            'blueprint' => $this->decode($row['blueprint_json']),
            'steps' => $steps,
            'can_retry' => (string) $row['status'] === 'failed',
            'can_cancel' => in_array((string) $row['status'], ['draft', 'generating', 'failed', 'ready'], true),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listJobs(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->query(
            "SELECT j.id, j.status, j.model, j.estimated_cost_usd, j.prompt_tokens,
                    j.completion_tokens, j.total_steps, j.completed_steps,
                    j.created_at, j.updated_at, j.learner_id, j.course_owner_id,
                    CONCAT(l.first_name, ' ', l.last_name) AS learner_name,
                    CONCAT(o.first_name, ' ', o.last_name) AS owner_name
               FROM ld_ai_course_job j
               LEFT JOIN em_employees l ON l.employee_id = j.learner_id
               LEFT JOIN em_employees o ON o.employee_id = j.course_owner_id
              ORDER BY j.id DESC
              LIMIT {$limit}"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function encode(mixed $value): ?string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? null : $json;
    }

    public function decode(?string $json): mixed
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $fields
     * @param string[] $allowed
     */
    private function update(string $table, string $idColumn, int $id, array $fields, array $allowed): void
    {
        $sets = [];
        $params = [':row_id' => $id];
        foreach ($fields as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }
            if ($value === self::NOW) {
                $sets[] = "`{$column}` = NOW()";
                continue;
            }
            $placeholder = ':f_' . $column;
            $sets[] = "`{$column}` = {$placeholder}";
            $params[$placeholder] = $value;
        }
        if ($sets === []) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE `{$idColumn}` = :row_id"
        );
        foreach ($params as $placeholder => $value) {
            if ($value === null) {
                $stmt->bindValue($placeholder, null, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($placeholder, (string) $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
    }
}
