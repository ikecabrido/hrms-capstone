<?php
/**
 * AIAdminSettings — validated non-secret settings for the AI assistant.
 *
 * Default/fallback model selection lives on ld_ai_model (is_default /
 * is_fallback) so there is only one source of truth. All other values live in
 * ld_ai_setting and are validated here before they are persisted.
 */
class AIAdminSettings
{
    private PDO $pdo;
    private AIAudit $audit;

    public function __construct(PDO $pdo, ?AIAudit $audit = null)
    {
        $this->pdo = $pdo;
        $this->audit = $audit ?? new AIAudit($pdo);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'base_url' => [
                'label' => 'Provider base URL',
                'type' => 'url',
                'min' => null,
                'max' => null,
                'help' => 'Must stay on openrouter.ai so the provider key cannot be sent elsewhere.',
            ],
            'model_cost_mode' => [
                'label' => 'Model cost mode',
                'type' => 'enum',
                'options' => [
                    'free_only' => 'Free models only (default)',
                    'paid_only' => 'Paid models only',
                    'mixed' => 'Mixed (free and paid)',
                ],
                'min' => null,
                'max' => null,
                'help' => 'Free models only blocks paid models from tests and generation. Paid models only blocks free models.',
            ],
            'request_timeout' => [
                'label' => 'Request timeout (seconds)',
                'type' => 'int',
                'min' => 5,
                'max' => 300,
                'help' => 'Per provider request. Keep below the web server timeout.',
            ],
            'max_retries' => [
                'label' => 'Max retries per step',
                'type' => 'int',
                'min' => 0,
                'max' => 5,
                'help' => 'Retries are used for transient provider/network errors only.',
            ],
            'temperature' => [
                'label' => 'Temperature',
                'type' => 'float',
                'min' => 0,
                'max' => 1,
                'help' => 'Lower values make course drafts more consistent.',
            ],
            'max_modules' => [
                'label' => 'Max modules',
                'type' => 'int',
                'min' => 1,
                'max' => 20,
                'help' => 'Hard cap for a generated course outline.',
            ],
            'max_lessons_per_module' => [
                'label' => 'Max lessons per module',
                'type' => 'int',
                'min' => 1,
                'max' => 20,
                'help' => 'Hard cap used by module validation.',
            ],
            'max_quiz_questions_per_module' => [
                'label' => 'Max quiz questions per module',
                'type' => 'int',
                'min' => 1,
                'max' => 30,
                'help' => 'Only single-choice and true/false are enabled for the MVP.',
            ],
            'max_evaluation_questions' => [
                'label' => 'Max evaluation questions',
                'type' => 'int',
                'min' => 1,
                'max' => 50,
                'help' => 'Hard cap for the final course evaluation.',
            ],
            'max_calls_per_job' => [
                'label' => 'Max provider calls per job',
                'type' => 'int',
                'min' => 1,
                'max' => 30,
                'help' => 'Stops a runaway resumable job.',
            ],
            'max_output_tokens_per_step' => [
                'label' => 'Max output tokens per step',
                'type' => 'int',
                'min' => 500,
                'max' => 20000,
                'help' => 'Passed to the provider as max_tokens.',
            ],
            'jobs_per_admin_per_day' => [
                'label' => 'Jobs per admin per day',
                'type' => 'int',
                'min' => 1,
                'max' => 100,
                'help' => 'Daily quota for the admin who starts a job.',
            ],
            'monthly_cost_cap_usd' => [
                'label' => 'Monthly cost cap (USD)',
                'type' => 'float',
                'min' => 0,
                'max' => 1000,
                'help' => 'Estimated spend ceiling for the current month.',
            ],
            'per_job_cost_cap_usd' => [
                'label' => 'Per-job cost cap (USD)',
                'type' => 'float',
                'min' => 0,
                'max' => 100,
                'help' => 'Stops a single job before it exceeds this estimate.',
            ],
            'job_retention_days' => [
                'label' => 'Job retention (days)',
                'type' => 'int',
                'min' => 7,
                'max' => 365,
                'help' => 'Used by the later cleanup command.',
            ],
            'allow_trainer_access' => [
                'label' => 'Allow trainer access',
                'type' => 'bool',
                'min' => null,
                'max' => null,
                'help' => 'Reserved for a later phase. Keep off for the admin-only MVP.',
            ],
            'allow_custom_prompt_editing' => [
                'label' => 'Allow custom prompt editing',
                'type' => 'bool',
                'min' => null,
                'max' => null,
                'help' => 'Reserved. Keep off until prompt versioning is hardened.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $values = AIConfig::settingDefaults();
        $stmt = $this->pdo->query('SELECT setting_key, setting_value FROM ld_ai_setting');
        $definitions = self::definitions();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['setting_key'];
            if (!isset($definitions[$key])) {
                continue;
            }
            $values[$key] = $this->castForDisplay($definitions[$key]['type'], $row['setting_value']);
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string, errors: array<int, string>, settings: array<string, mixed>}
     */
    public function save(array $input, int $employeeId): array
    {
        $definitions = self::definitions();
        $validated = [];
        $errors = [];

        foreach ($input as $key => $value) {
            if ($key === 'csrf_token' || $key === 'action') {
                continue;
            }
            if (!isset($definitions[$key])) {
                $errors[] = "Unknown setting: $key";
                continue;
            }

            $result = $this->validateValue($key, $value, $definitions[$key]);
            if (!$result['success']) {
                $errors[] = $result['error'];
                continue;
            }
            $validated[$key] = $result['value'];
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => 'Settings were not saved.',
                'errors' => $errors,
                'settings' => $this->getAll(),
            ];
        }

        if ($validated === []) {
            return [
                'success' => false,
                'message' => 'No settings were submitted.',
                'errors' => ['No settings were submitted.'],
                'settings' => $this->getAll(),
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ld_ai_setting (setting_key, setting_value, updated_by)
                 VALUES (:setting_key, :setting_value, :updated_by)
                 ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by)'
            );

            foreach ($validated as $key => $value) {
                $stmt->execute([
                    ':setting_key' => $key,
                    ':setting_value' => (string) $value,
                    ':updated_by' => $employeeId > 0 ? $employeeId : null,
                ]);
            }

            $this->audit->log($employeeId, 'settings.updated', 'settings', null, [
                'keys' => array_keys($validated),
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Settings could not be saved.',
                'errors' => ['The settings database write failed.'],
                'settings' => $this->getAll(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Settings saved.',
            'errors' => [],
            'settings' => $this->getAll(),
        ];
    }

    /**
     * @param array<string, mixed> $definition
     * @return array{success: bool, value: mixed, error: string}
     */
    private function validateValue(string $key, mixed $value, array $definition): array
    {
        $type = $definition['type'];

        if ($type === 'enum') {
            $normalized = strtolower(trim((string) $value));
            $options = is_array($definition['options'] ?? null) ? $definition['options'] : [];
            if (!isset($options[$normalized])) {
                return [
                    'success' => false,
                    'value' => null,
                    'error' => $definition['label'] . ' must be one of: ' . implode(', ', array_keys($options)) . '.',
                ];
            }

            return ['success' => true, 'value' => $normalized, 'error' => ''];
        }

        if ($type === 'bool') {
            if (is_bool($value)) {
                return ['success' => true, 'value' => $value ? 1 : 0, 'error' => ''];
            }
            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return ['success' => true, 'value' => 1, 'error' => ''];
            }
            if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
                return ['success' => true, 'value' => 0, 'error' => ''];
            }
            return ['success' => false, 'value' => null, 'error' => $definition['label'] . ' must be on or off.'];
        }

        if ($type === 'int') {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);
            if ($filtered === false) {
                return ['success' => false, 'value' => null, 'error' => $definition['label'] . ' must be a whole number.'];
            }
            if ($filtered < $definition['min'] || $filtered > $definition['max']) {
                return [
                    'success' => false,
                    'value' => null,
                    'error' => $definition['label'] . ' must be between ' . $definition['min'] . ' and ' . $definition['max'] . '.',
                ];
            }
            return ['success' => true, 'value' => $filtered, 'error' => ''];
        }

        if ($type === 'float') {
            if (!is_numeric($value)) {
                return ['success' => false, 'value' => null, 'error' => $definition['label'] . ' must be a number.'];
            }
            $number = (float) $value;
            if ($number < $definition['min'] || $number > $definition['max']) {
                return [
                    'success' => false,
                    'value' => null,
                    'error' => $definition['label'] . ' must be between ' . $definition['min'] . ' and ' . $definition['max'] . '.',
                ];
            }
            return ['success' => true, 'value' => $number, 'error' => ''];
        }

        if ($type === 'url') {
            $url = trim((string) $value);
            $parts = parse_url($url);
            if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== 'openrouter.ai') {
                return [
                    'success' => false,
                    'value' => null,
                    'error' => 'Provider base URL must be an https://openrouter.ai URL.',
                ];
            }
            $path = rtrim((string) ($parts['path'] ?? ''), '/');
            if ($path !== '/api/v1') {
                return [
                    'success' => false,
                    'value' => null,
                    'error' => 'Provider base URL must use the /api/v1 path.',
                ];
            }
            return ['success' => true, 'value' => 'https://openrouter.ai/api/v1', 'error' => ''];
        }

        return ['success' => false, 'value' => null, 'error' => 'Unsupported setting type for ' . $key . '.'];
    }

    private function castForDisplay(string $type, mixed $value): mixed
    {
        if ($type === 'bool') {
            return in_array((string) $value, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
        }
        if ($type === 'int') {
            return (int) $value;
        }
        if ($type === 'float') {
            return (float) $value;
        }
        if ($type === 'enum') {
            return (string) $value;
        }
        return (string) $value;
    }
}
