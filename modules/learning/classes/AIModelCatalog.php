<?php
/**
 * AIModelCatalog — OpenRouter model sync and default/fallback selection.
 *
 * Newly synced models are disabled by default. Only enabled models can be
 * selected as the primary or fallback model.
 */
class AIModelCatalog
{
    private PDO $pdo;
    private AIAudit $audit;

    public function __construct(PDO $pdo, ?AIAudit $audit = null)
    {
        $this->pdo = $pdo;
        $this->audit = $audit ?? new AIAudit($pdo);
    }

    public function costMode(): string
    {
        return AIConfig::modelCostMode($this->pdo);
    }

    /**
     * @param array<string, mixed> $model
     */
    public function allows(array $model): bool
    {
        return AIConfig::modelAllowedForMode($model, $this->costMode());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, provider, model_slug, display_name, context_length,
                    prompt_price, completion_price, enabled, is_default, is_fallback,
                    last_synced_at
               FROM ld_ai_model
              WHERE provider = 'openrouter'
              ORDER BY enabled DESC, is_default DESC, is_fallback DESC, display_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listEnabled(): array
    {
        $where = AIConfig::modelModeWhere($this->costMode());
        $stmt = $this->pdo->query(
            "SELECT id, model_slug, display_name, context_length, prompt_price, completion_price
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1 AND {$where}
              ORDER BY is_default DESC, is_fallback DESC, display_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, provider, model_slug, display_name, context_length,
                    prompt_price, completion_price, enabled, is_default, is_fallback,
                    last_synced_at
               FROM ld_ai_model
              WHERE provider = 'openrouter' AND model_slug = :slug
              LIMIT 1"
        );
        $stmt->execute([':slug' => trim($slug)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array{success: bool, message: string, count: int, inserted: int, updated: int}
     */
    public function sync(int $employeeId): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not available on this server.', 'count' => 0, 'inserted' => 0, 'updated' => 0];
        }

        $ch = curl_init(AIConfig::baseUrl($this->pdo) . '/models');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Learning-AI-Assistant/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $curlError !== '') {
            return ['success' => false, 'message' => 'The model catalog could not be reached.', 'count' => 0, 'inserted' => 0, 'updated' => 0];
        }
        if ($httpStatus !== 200) {
            return ['success' => false, 'message' => 'The model catalog returned HTTP ' . $httpStatus . '.', 'count' => 0, 'inserted' => 0, 'updated' => 0];
        }

        $decoded = json_decode((string) $body, true);
        $models = is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])
            ? $decoded['data']
            : null;
        if ($models === null || $models === []) {
            return ['success' => false, 'message' => 'The model catalog response was empty or invalid.', 'count' => 0, 'inserted' => 0, 'updated' => 0];
        }

        $inserted = 0;
        $updated = 0;
        $seen = 0;

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ld_ai_model
                    (provider, model_slug, display_name, context_length,
                     prompt_price, completion_price, enabled, is_default, is_fallback, last_synced_at)
                 VALUES
                    ('openrouter', :slug, :name, :context_length,
                     :prompt_price, :completion_price, 0, 0, 0, NOW())
                 ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    context_length = VALUES(context_length),
                    prompt_price = VALUES(prompt_price),
                    completion_price = VALUES(completion_price),
                    last_synced_at = NOW()"
            );

            foreach ($models as $model) {
                if (!is_array($model)) {
                    continue;
                }
                $slug = trim((string) ($model['id'] ?? ''));
                if ($slug === '' || strlen($slug) > 160 || preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{1,159}$/', $slug) !== 1) {
                    continue;
                }

                $name = trim((string) ($model['name'] ?? $slug));
                if ($name === '') {
                    $name = $slug;
                }
                $name = mb_substr($name, 0, 160);

                $contextLength = (int) ($model['context_length'] ?? 0);
                if ($contextLength < 0) {
                    $contextLength = 0;
                }
                if ($contextLength > 4294967295) {
                    $contextLength = 4294967295;
                }

                $pricing = is_array($model['pricing'] ?? null) ? $model['pricing'] : [];
                $promptPrice = $this->normalizePrice($pricing['prompt'] ?? 0);
                $completionPrice = $this->normalizePrice($pricing['completion'] ?? 0);

                $stmt->execute([
                    ':slug' => $slug,
                    ':name' => $name,
                    ':context_length' => $contextLength,
                    ':prompt_price' => $promptPrice,
                    ':completion_price' => $completionPrice,
                ]);

                $rowCount = $stmt->rowCount();
                if ($rowCount === 1) {
                    $inserted++;
                } elseif ($rowCount === 2) {
                    $updated++;
                }
                $seen++;
            }

            $this->seedDefaultsIfEmpty();

            $this->audit->log($employeeId, 'models.synced', 'model', null, [
                'seen' => $seen,
                'inserted' => $inserted,
                'updated' => $updated,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The model catalog could not be saved.', 'count' => 0, 'inserted' => 0, 'updated' => 0];
        }

        return [
            'success' => true,
            'message' => 'Model catalog synced. New models start disabled.',
            'count' => $seen,
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    /**
     * @return array{success: bool, message: string, model: array<string, mixed>|null}
     */
    public function addManual(
        string $slug,
        string $name,
        int $contextLength,
        bool $enabled,
        float $promptPricePerMillion,
        float $completionPricePerMillion,
        int $employeeId
    ): array
    {
        $slug = trim($slug);
        $name = trim($name);
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{1,159}$/', $slug) !== 1) {
            return ['success' => false, 'message' => 'Use a valid OpenRouter model slug, for example google/gemma-4-31b-it:free.', 'model' => null];
        }
        if ($name === '') {
            $name = $slug;
        }
        $name = mb_substr($name, 0, 160);
        $contextLength = max(0, min(4294967295, $contextLength));

        if (
            !is_finite($promptPricePerMillion)
            || !is_finite($completionPricePerMillion)
            || $promptPricePerMillion < 0
            || $completionPricePerMillion < 0
            || $promptPricePerMillion > 100000
            || $completionPricePerMillion > 100000
        ) {
            return ['success' => false, 'message' => 'Prices must be between 0 and 100000 USD per million tokens.', 'model' => null];
        }

        $promptPrice = round($promptPricePerMillion / 1000000, 12);
        $completionPrice = round($completionPricePerMillion / 1000000, 12);
        $priceRow = [
            'prompt_price' => $promptPrice,
            'completion_price' => $completionPrice,
        ];
        if ($enabled && !AIConfig::modelAllowedForMode($priceRow, $this->costMode())) {
            return [
                'success' => false,
                'message' => 'This model is blocked by the current model cost mode ('
                    . AIConfig::modelCostModeLabel($this->costMode())
                    . '). Add it disabled or change the mode first.',
                'model' => null,
            ];
        }

        $exists = $this->pdo->prepare(
            "SELECT id FROM ld_ai_model WHERE provider = 'openrouter' AND model_slug = :slug LIMIT 1"
        );
        $exists->execute([':slug' => $slug]);
        if ($exists->fetchColumn()) {
            return ['success' => false, 'message' => 'That model is already in the catalog.', 'model' => null];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ld_ai_model
                    (provider, model_slug, display_name, context_length, prompt_price, completion_price,
                     enabled, is_default, is_fallback, last_synced_at)
                 VALUES ('openrouter', :slug, :name, :context_length, :prompt_price, :completion_price,
                         :enabled, 0, 0, NULL)"
            );
            $stmt->execute([
                ':slug' => $slug,
                ':name' => $name,
                ':context_length' => $contextLength,
                ':prompt_price' => $promptPrice,
                ':completion_price' => $completionPrice,
                ':enabled' => $enabled ? 1 : 0,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->audit->log($employeeId, 'model.created', 'model', (string) $id, [
                'slug' => $slug,
                'enabled' => $enabled ? 1 : 0,
                'prompt_price' => $promptPrice,
                'completion_price' => $completionPrice,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The model could not be added.', 'model' => null];
        }

        return ['success' => true, 'message' => 'Model added.', 'model' => $this->findModel($id)];
    }

    /**
     * @return array{success: bool, message: string, model: array<string, mixed>|null}
     */
    public function setEnabled(int $id, bool $enabled, int $employeeId): array
    {
        $model = $this->findModel($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Model not found.', 'model' => null];
        }
        if ($enabled && !$this->allows($model)) {
            return [
                'success' => false,
                'message' => 'This model is blocked by the current model cost mode ('
                    . AIConfig::modelCostModeLabel($this->costMode())
                    . '). Change the mode first.',
                'model' => null,
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE ld_ai_model
                    SET enabled = :enabled,
                        is_default = IF(:enabled_default = 1, is_default, 0),
                        is_fallback = IF(:enabled_fallback = 1, is_fallback, 0)
                  WHERE id = :id AND provider = 'openrouter'"
            );
            $stmt->execute([
                ':enabled' => $enabled ? 1 : 0,
                ':enabled_default' => $enabled ? 1 : 0,
                ':enabled_fallback' => $enabled ? 1 : 0,
                ':id' => $id,
            ]);
            $this->audit->log($employeeId, $enabled ? 'model.enabled' : 'model.disabled', 'model', (string) $id, [
                'slug' => (string) $model['model_slug'],
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The model could not be updated.', 'model' => null];
        }

        return [
            'success' => true,
            'message' => $enabled ? 'Model enabled.' : 'Model disabled.',
            'model' => $this->findModel($id),
        ];
    }

    /**
     * @return array{success: bool, message: string, model: array<string, mixed>|null}
     */
    public function setDefault(int $id, int $employeeId): array
    {
        $model = $this->findModel($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Model not found.', 'model' => null];
        }
        if ((int) $model['enabled'] !== 1) {
            return ['success' => false, 'message' => 'Enable the model before making it the default.', 'model' => null];
        }
        if (!$this->allows($model)) {
            return [
                'success' => false,
                'message' => 'This model is blocked by the current model cost mode ('
                    . AIConfig::modelCostModeLabel($this->costMode())
                    . '). Change the mode first.',
                'model' => null,
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec(
                "UPDATE ld_ai_model SET is_default = 0 WHERE provider = 'openrouter'"
            );
            $stmt = $this->pdo->prepare(
                "UPDATE ld_ai_model
                    SET is_default = 1,
                        is_fallback = 0,
                        enabled = 1
                  WHERE id = :id AND provider = 'openrouter'"
            );
            $stmt->execute([':id' => $id]);
            $this->audit->log($employeeId, 'model.default', 'model', (string) $id, [
                'slug' => (string) $model['model_slug'],
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The default model could not be changed.', 'model' => null];
        }

        return ['success' => true, 'message' => 'Default model updated.', 'model' => $this->findModel($id)];
    }

    /**
     * @return array{success: bool, message: string, model: array<string, mixed>|null}
     */
    public function setFallback(int $id, int $employeeId): array
    {
        $model = $this->findModel($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Model not found.', 'model' => null];
        }
        if ((int) $model['enabled'] !== 1) {
            return ['success' => false, 'message' => 'Enable the model before making it the fallback.', 'model' => null];
        }
        if ((int) $model['is_default'] === 1) {
            return ['success' => false, 'message' => 'Choose a fallback different from the default model.', 'model' => null];
        }
        if (!$this->allows($model)) {
            return [
                'success' => false,
                'message' => 'This model is blocked by the current model cost mode ('
                    . AIConfig::modelCostModeLabel($this->costMode())
                    . '). Change the mode first.',
                'model' => null,
            ];
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec(
                "UPDATE ld_ai_model SET is_fallback = 0 WHERE provider = 'openrouter'"
            );
            $stmt = $this->pdo->prepare(
                "UPDATE ld_ai_model
                    SET is_fallback = 1, enabled = 1
                  WHERE id = :id AND provider = 'openrouter'"
            );
            $stmt->execute([':id' => $id]);
            $this->audit->log($employeeId, 'model.fallback', 'model', (string) $id, [
                'slug' => (string) $model['model_slug'],
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The fallback model could not be changed.', 'model' => null];
        }

        return ['success' => true, 'message' => 'Fallback model updated.', 'model' => $this->findModel($id)];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findModel(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, provider, model_slug, display_name, context_length,
                    prompt_price, completion_price, enabled, is_default, is_fallback,
                    last_synced_at
               FROM ld_ai_model
              WHERE id = :id AND provider = 'openrouter'
              LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function normalizePrice(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }
        $number = (float) $value;
        if ($number < 0) {
            return 0.0;
        }

        // decimal(18,12): six integer digits and twelve decimal places.
        return min(999999.999999, round($number, 12));
    }

    private function seedDefaultsIfEmpty(): void
    {
        $mode = $this->costMode();
        $where = AIConfig::modelModeWhere($mode);
        $enabledCount = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM ld_ai_model
              WHERE provider = 'openrouter' AND enabled = 1 AND {$where}"
        )->fetchColumn();
        if ($enabledCount > 0) {
            return;
        }

        $preferredDefault = $mode === 'paid_only' ? AIConfig::DEFAULT_PAID_MODEL : AIConfig::DEFAULT_MODEL;
        $preferredFallback = $mode === 'paid_only'
            ? AIConfig::DEFAULT_PAID_FALLBACK_MODEL
            : AIConfig::DEFAULT_FALLBACK_MODEL;

        $defaultId = $this->findAllowedModelId($preferredDefault, $mode);
        if ($defaultId <= 0) {
            $defaultId = $this->firstAllowedModelId($mode);
        }
        if ($defaultId <= 0) {
            return;
        }

        $fallbackId = $this->findAllowedModelId($preferredFallback, $mode, $defaultId);
        if ($fallbackId <= 0) {
            $fallbackId = $this->firstAllowedModelId($mode, $defaultId);
        }

        $this->pdo->exec(
            "UPDATE ld_ai_model SET is_default = 0, is_fallback = 0 WHERE provider = 'openrouter'"
        );

        $stmt = $this->pdo->prepare(
            "UPDATE ld_ai_model SET enabled = 1, is_default = 1, is_fallback = 0 WHERE id = :id"
        );
        $stmt->execute([':id' => $defaultId]);

        if ($fallbackId > 0 && $fallbackId !== $defaultId) {
            $stmt = $this->pdo->prepare(
                "UPDATE ld_ai_model SET enabled = 1, is_fallback = 1 WHERE id = :id"
            );
            $stmt->execute([':id' => $fallbackId]);
        }
    }

    private function findAllowedModelId(string $slug, string $mode, int $excludeId = 0): int
    {
        if ($slug === '') {
            return 0;
        }

        $where = AIConfig::modelModeWhere($mode);
        $sql = "SELECT id FROM ld_ai_model
                 WHERE provider = 'openrouter' AND model_slug = :slug AND {$where}";
        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $params = [':slug' => $slug];
        if ($excludeId > 0) {
            $params[':exclude_id'] = $excludeId;
        }
        $stmt->execute($params);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function firstAllowedModelId(string $mode, int $excludeId = 0): int
    {
        $where = AIConfig::modelModeWhere($mode);
        $sql = "SELECT id FROM ld_ai_model
                 WHERE provider = 'openrouter' AND {$where}";
        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' ORDER BY context_length DESC, id ASC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        if ($excludeId > 0) {
            $stmt->execute([':exclude_id' => $excludeId]);
        } else {
            $stmt->execute();
        }

        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
