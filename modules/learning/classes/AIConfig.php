<?php
/**
 * AIConfig — AI Course Assistant configuration and schema readiness.
 *
 * The provider key and mutable admin settings are added in later phases. This
 * class starts with the database contract so the module can detect a missing
 * migration without throwing a fatal error.
 */
class AIConfig
{
    public const REQUIRED_TABLES = [
        'ld_ai_course_job',
        'ld_ai_course_job_step',
        'ld_ai_api_key',
        'ld_ai_model',
        'ld_ai_setting',
        'ld_ai_prompt_template',
        'ld_ai_audit_log',
    ];

    public const DEFAULT_PROVIDER = 'openrouter';
    public const DEFAULT_BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * Free models are the default. Paid defaults are kept only for installs
     * that explicitly select paid_only.
     */
    public const DEFAULT_MODEL = 'google/gemma-4-31b-it:free';
    public const DEFAULT_FALLBACK_MODEL = 'nvidia/nemotron-3-super-120b-a12b:free';
    public const DEFAULT_PAID_MODEL = 'openai/gpt-4.1-mini';
    public const DEFAULT_PAID_FALLBACK_MODEL = 'google/gemini-2.5-flash';

    public const MODEL_COST_MODES = ['free_only', 'paid_only', 'mixed'];
    public const DEFAULT_MODEL_COST_MODE = 'free_only';

    /** Optional file outside the webroot. It must return an array. */
    public const LOCAL_CONFIG_ENV = 'LD_AI_CONFIG_FILE';

    private static ?array $localConfigCache = null;

    /**
     * Read a local configuration file without ever putting secrets in the
     * repository. Precedence is environment first, then local file, then the
     * caller's default.
     *
     * @return mixed
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $envName = 'LD_AI_' . strtoupper($key);
        $envValue = getenv($envName);
        if ($envValue === false && isset($_SERVER[$envName])) {
            $envValue = $_SERVER[$envName];
        }
        if ($envValue !== false && $envValue !== null && $envValue !== '') {
            return $envValue;
        }

        $local = self::localConfig();
        if (array_key_exists($key, $local) && $local[$key] !== '') {
            return $local[$key];
        }

        return $default;
    }

    /**
     * Local config is optional and deliberately outside the webroot.
     *
     * @return array<string, mixed>
     */
    public static function localConfig(): array
    {
        if (self::$localConfigCache !== null) {
            return self::$localConfigCache;
        }

        $candidates = [];
        $configured = getenv(self::LOCAL_CONFIG_ENV);
        if ($configured === false && isset($_SERVER[self::LOCAL_CONFIG_ENV])) {
            $configured = $_SERVER[self::LOCAL_CONFIG_ENV];
        }
        if (is_string($configured) && $configured !== '') {
            $candidates[] = $configured;
        }

        // XAMPP/local layout: C:\xampp\hrms-capzy-ai-config.php. On other
        // layouts this is simply absent; deployments should set
        // LD_AI_CONFIG_FILE instead.
        $candidates[] = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'hrms-capzy-ai-config.php';

        foreach ($candidates as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            try {
                $config = require $file;
            } catch (Throwable $e) {
                // A malformed local file must not take the admin page down.
                continue;
            }

            if (is_array($config)) {
                self::$localConfigCache = $config;
                return self::$localConfigCache;
            }
        }

        self::$localConfigCache = [];
        return self::$localConfigCache;
    }

    /**
     * Non-secret defaults. Default/fallback model selection is stored on the
     * model rows so there is only one source of truth.
     *
     * @return array<string, mixed>
     */
    public static function settingDefaults(): array
    {
        return [
            'base_url' => self::DEFAULT_BASE_URL,
            'model_cost_mode' => self::DEFAULT_MODEL_COST_MODE,
            'request_timeout' => 90,
            'max_retries' => 2,
            'temperature' => 0.3,
            'max_modules' => 8,
            'max_lessons_per_module' => 6,
            'max_quiz_questions_per_module' => 10,
            'max_evaluation_questions' => 20,
            'max_calls_per_job' => 12,
            'max_output_tokens_per_step' => 6000,
            'jobs_per_admin_per_day' => 20,
            'monthly_cost_cap_usd' => 25,
            'per_job_cost_cap_usd' => 0.50,
            'job_retention_days' => 90,
            'allow_trainer_access' => 0,
            'allow_custom_prompt_editing' => 0,
        ];
    }

    public static function provider(): string
    {
        $provider = (string) self::value('provider', self::DEFAULT_PROVIDER);
        return $provider !== '' ? $provider : self::DEFAULT_PROVIDER;
    }

    public static function normalizeModelCostMode(mixed $mode): string
    {
        $normalized = strtolower(trim((string) $mode));

        return in_array($normalized, self::MODEL_COST_MODES, true)
            ? $normalized
            : self::DEFAULT_MODEL_COST_MODE;
    }

    /**
     * The effective mode. Environment/local config wins, then the validated
     * admin setting, then the free-only default.
     */
    public static function modelCostMode(?PDO $pdo = null): string
    {
        return self::normalizeModelCostMode(
            self::runtimeValue($pdo, 'model_cost_mode', self::DEFAULT_MODEL_COST_MODE)
        );
    }

    public static function modelCostModeLabel(string $mode): string
    {
        return match (self::normalizeModelCostMode($mode)) {
            'paid_only' => 'Paid models only',
            'mixed' => 'Mixed (free and paid)',
            default => 'Free models only',
        };
    }

    /**
     * A model is free only when both prices are zero and the slug carries the
     * canonical OpenRouter free marker. Zero-priced routers (for example
     * openrouter/auto) can route to paid models and are deliberately excluded.
     *
     * @param array<string, mixed> $model
     */
    public static function isFreeModel(array $model): bool
    {
        $slug = strtolower(trim((string) ($model['model_slug'] ?? '')));

        return (float) ($model['prompt_price'] ?? 0) <= 0
            && (float) ($model['completion_price'] ?? 0) <= 0
            && self::hasFreeMarker($slug);
    }

    /**
     * @param array<string, mixed> $model
     */
    public static function modelAllowedForMode(array $model, string $mode): bool
    {
        $mode = self::normalizeModelCostMode($mode);
        if ($mode === 'mixed') {
            return true;
        }

        return $mode === 'free_only' ? self::isFreeModel($model) : !self::isFreeModel($model);
    }

    /**
     * SQL predicate for a single-table ld_ai_model query. The mode is normalized
     * to a fixed value, so the fragment is safe to interpolate.
     */
    public static function modelModeWhere(string $mode): string
    {
        $free = "(prompt_price <= 0 AND completion_price <= 0
                  AND (LOWER(model_slug) LIKE '%:free' OR LOWER(model_slug) = 'openrouter/free'))";

        return match (self::normalizeModelCostMode($mode)) {
            'paid_only' => "NOT {$free}",
            'mixed' => '1=1',
            default => $free,
        };
    }

    private static function hasFreeMarker(string $slug): bool
    {
        return $slug !== ''
            && (str_ends_with($slug, ':free') || $slug === 'openrouter/free');
    }

    /**
     * Resolve a runtime value with environment/local-config precedence, then the
     * admin setting stored in ld_ai_setting, then the supplied default.
     *
     * This keeps deployment overrides working while making the validated admin
     * settings the source of truth for normal operation.
     */
    public static function runtimeValue(?PDO $pdo, string $key, mixed $default = null): mixed
    {
        $value = self::value($key, null);
        if ($value !== null && $value !== '') {
            return $value;
        }

        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT setting_value FROM ld_ai_setting WHERE setting_key = :setting_key LIMIT 1'
                );
                $stmt->execute([':setting_key' => $key]);
                $stored = $stmt->fetchColumn();
                if ($stored !== false && $stored !== null && $stored !== '') {
                    return $stored;
                }
            } catch (Throwable $e) {
                // Schema readiness is handled by the caller. A missing setting
                // table must not take a provider request down.
            }
        }

        return $default;
    }

    /**
     * The provider URL is deliberately pinned to OpenRouter. A misconfigured
     * environment variable or local config must never redirect a bearer key to
     * another host, so an invalid value falls back to the canonical endpoint.
     */
    public static function baseUrl(?PDO $pdo = null): string
    {
        $url = rtrim((string) self::runtimeValue($pdo, 'base_url', self::DEFAULT_BASE_URL), '/');
        $parts = parse_url($url);

        if (
            !is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== 'openrouter.ai'
            || rtrim((string) ($parts['path'] ?? ''), '/') !== '/api/v1'
            || isset($parts['query'])
            || isset($parts['fragment'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)
        ) {
            return self::DEFAULT_BASE_URL;
        }

        return $url;
    }

    /**
     * Return the AI tables that do not exist in the current database.
     *
     * @return string[]
     */
    public static function missingTables(PDO $pdo): array
    {
        $placeholders = implode(',', array_fill(0, count(self::REQUIRED_TABLES), '?'));
        $stmt = $pdo->prepare(
            "SELECT TABLE_NAME
               FROM information_schema.tables
              WHERE table_schema = DATABASE()
                AND TABLE_NAME IN ($placeholders)"
        );
        $stmt->execute(self::REQUIRED_TABLES);

        $found = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $found = array_map('strval', $found);

        return array_values(array_diff(self::REQUIRED_TABLES, $found));
    }

    public static function schemaReady(PDO $pdo): bool
    {
        return self::missingTables($pdo) === [];
    }

    /**
     * @return array{ready: bool, required: string[], missing: string[]}
     */
    public static function schemaStatus(PDO $pdo): array
    {
        $missing = self::missingTables($pdo);

        return [
            'ready' => $missing === [],
            'required' => self::REQUIRED_TABLES,
            'missing' => $missing,
        ];
    }
}
