<?php
/**
 * Model cost mode checks: free-only, paid-only, and mixed.
 *
 *     php modules/learning/tests/ai-model-mode-tests.php
 *
 * The test creates two disposable catalog rows and restores the previous
 * model_cost_mode setting in a finally block. It does not call a provider.
 */

require_once dirname(__DIR__, 3) . '/database/db.php';
require_once dirname(__DIR__) . '/classes/AIConfig.php';
require_once dirname(__DIR__) . '/classes/AIAudit.php';
require_once dirname(__DIR__) . '/classes/AIAdminSettings.php';
require_once dirname(__DIR__) . '/classes/AIModelCatalog.php';
require_once dirname(__DIR__) . '/classes/AICourseJobStore.php';
require_once dirname(__DIR__) . '/classes/OpenRouterClient.php';

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

$freeSlug = 'test/free-' . bin2hex(random_bytes(4)) . ':free';
$paidSlug = 'test/paid-' . bin2hex(random_bytes(4));
$freeId = 0;
$paidId = 0;
$previousMode = null;
$hadPreviousMode = false;

try {
    $previousMode = $pdo->query(
        "SELECT setting_value FROM ld_ai_setting WHERE setting_key = 'model_cost_mode' LIMIT 1"
    )->fetchColumn();
    $hadPreviousMode = $previousMode !== false && $previousMode !== null;

    $insert = $pdo->prepare(
        "INSERT INTO ld_ai_model
            (provider, model_slug, display_name, context_length, prompt_price, completion_price,
             enabled, is_default, is_fallback, last_synced_at)
         VALUES ('openrouter', :slug, :name, 8192, :prompt_price, :completion_price, 1, 0, 0, NULL)"
    );
    $insert->execute([
        ':slug' => $freeSlug,
        ':name' => 'Disposable Free Model',
        ':prompt_price' => 0,
        ':completion_price' => 0,
    ]);
    $freeId = (int) $pdo->lastInsertId();
    $insert->execute([
        ':slug' => $paidSlug,
        ':name' => 'Disposable Paid Model',
        ':prompt_price' => 0.000001,
        ':completion_price' => 0.000002,
    ]);
    $paidId = (int) $pdo->lastInsertId();

    $setMode = $pdo->prepare(
        "INSERT INTO ld_ai_setting (setting_key, setting_value, updated_by)
         VALUES ('model_cost_mode', :mode, NULL)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $setMode->execute([':mode' => 'free_only']);

    check('free-only is the default', AIConfig::settingDefaults()['model_cost_mode'] === 'free_only');
    check('invalid modes normalize to free-only', AIConfig::normalizeModelCostMode('anything') === 'free_only');
    check('free-only label is explicit', AIConfig::modelCostModeLabel('free_only') === 'Free models only');
    check('paid-only label is explicit', AIConfig::modelCostModeLabel('paid_only') === 'Paid models only');
    check('mixed label is explicit', AIConfig::modelCostModeLabel('mixed') === 'Mixed (free and paid)');

    $freeRow = ['model_slug' => $freeSlug, 'prompt_price' => 0, 'completion_price' => 0];
    $paidRow = ['model_slug' => $paidSlug, 'prompt_price' => 0.000001, 'completion_price' => 0.000002];
    check('zero prices with a free marker are free', AIConfig::isFreeModel($freeRow));
    check('one non-zero price is paid', !AIConfig::isFreeModel($paidRow));
    check(
        'zero-priced routers are not treated as free',
        !AIConfig::isFreeModel(['model_slug' => 'openrouter/auto', 'prompt_price' => 0, 'completion_price' => 0])
    );
    check(
        'the explicit free router is allowed',
        AIConfig::isFreeModel(['model_slug' => 'openrouter/free', 'prompt_price' => 0, 'completion_price' => 0])
    );
    check('free-only allows a free model', AIConfig::modelAllowedForMode($freeRow, 'free_only'));
    check('free-only blocks a paid model', !AIConfig::modelAllowedForMode($paidRow, 'free_only'));
    check('paid-only allows a paid model', AIConfig::modelAllowedForMode($paidRow, 'paid_only'));
    check('paid-only blocks a free model', !AIConfig::modelAllowedForMode($freeRow, 'paid_only'));
    check('mixed allows both models', AIConfig::modelAllowedForMode($freeRow, 'mixed') && AIConfig::modelAllowedForMode($paidRow, 'mixed'));
    check('free-only SQL predicate is free', str_contains(AIConfig::modelModeWhere('free_only'), 'prompt_price <= 0'));
    check(
        'paid-only SQL predicate excludes free models',
        str_contains(AIConfig::modelModeWhere('paid_only'), 'NOT')
            && str_contains(AIConfig::modelModeWhere('paid_only'), ':free')
    );

    $definitions = AIAdminSettings::definitions();
    check('settings expose the cost mode', isset($definitions['model_cost_mode']));
    check(
        'settings expose all three modes',
        ($definitions['model_cost_mode']['options'] ?? []) === [
            'free_only' => 'Free models only (default)',
            'paid_only' => 'Paid models only',
            'mixed' => 'Mixed (free and paid)',
        ]
    );

    $settings = new AIAdminSettings($pdo, new AIAudit($pdo));
    $invalid = $settings->save(['model_cost_mode' => 'unlimited'], 0);
    check('invalid cost mode is rejected', ($invalid['success'] ?? true) === false);
    check(
        'invalid cost mode is explained',
        (bool) array_filter(
            $invalid['errors'] ?? [],
            static fn(string $error): bool => str_contains($error, 'must be one of')
        )
    );

    $catalog = new AIModelCatalog($pdo, new AIAudit($pdo));
    $enabledSlugs = array_map(
        static fn(array $row): string => (string) ($row['model_slug'] ?? ''),
        $catalog->listEnabled()
    );
    check('free-only lists the disposable free model', in_array($freeSlug, $enabledSlugs, true));
    check('free-only hides the disposable paid model', !in_array($paidSlug, $enabledSlugs, true));
    check('catalog blocks paid enablement in free-only', ($catalog->setEnabled($paidId, true, 0)['success'] ?? true) === false);
    check('catalog blocks paid default in free-only', ($catalog->setDefault($paidId, 0)['success'] ?? true) === false);
    check('catalog blocks paid fallback in free-only', ($catalog->setFallback($paidId, 0)['success'] ?? true) === false);
    check(
        'manual paid model cannot be enabled in free-only',
        ($catalog->addManual('test/manual-paid-' . bin2hex(random_bytes(3)), 'Manual Paid', 8192, true, 1, 2, 0)['success'] ?? true) === false
    );

    $store = new AICourseJobStore($pdo, 'free_only');
    check('job store finds the free model', $store->modelBySlug($freeSlug) !== null);
    check('job store blocks the paid model', $store->modelBySlug($paidSlug) === null);
    $default = $store->defaultModel();
    check('job store default model is free', is_array($default) && AIConfig::isFreeModel($default));
    $mixedStore = new AICourseJobStore($pdo, 'mixed');
    check('mixed job store finds the paid model', $mixedStore->modelBySlug($paidSlug) !== null);

    $requests = [];
    $transport = static function (string $url, array $headers, string $body, int $timeout) use (&$requests): array {
        $requests[] = ['url' => $url, 'body' => $body];
        return [
            'status' => 200,
            'body' => json_encode([
                'model' => 'test/free-fixture',
                'choices' => [['message' => ['content' => 'OK']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ], JSON_UNESCAPED_SLASHES),
            'error' => '',
            'headers' => [],
            'errno' => 0,
        ];
    };
    $client = new OpenRouterClient(
        $pdo,
        null,
        $transport,
        static fn(): string => 'test-key-fixture',
        static function (float $seconds): void {
        },
        ['model_cost_mode' => 'free_only']
    );
    $blocked = $client->chat([['role' => 'user', 'content' => 'Reply with OK.']], ['model' => $paidSlug]);
    check('client blocks a paid model in free-only', ($blocked['success'] ?? true) === false);
    check('blocked client call makes no provider request', $requests === []);
    $allowed = $client->chat([['role' => 'user', 'content' => 'Reply with OK.']], ['model' => $freeSlug]);
    check('client allows a free model in free-only', ($allowed['success'] ?? false) === true, (string) ($allowed['message'] ?? ''));
    check('allowed client call reaches the provider', count($requests) === 1);

    $setMode->execute([':mode' => 'paid_only']);
    $paidSlugs = array_map(
        static fn(array $row): string => (string) ($row['model_slug'] ?? ''),
        (new AIModelCatalog($pdo, new AIAudit($pdo)))->listEnabled()
    );
    check('paid-only lists the disposable paid model', in_array($paidSlug, $paidSlugs, true));
    check('paid-only hides the disposable free model', !in_array($freeSlug, $paidSlugs, true));

    $setMode->execute([':mode' => 'mixed']);
    $mixedSlugs = array_map(
        static fn(array $row): string => (string) ($row['model_slug'] ?? ''),
        (new AIModelCatalog($pdo, new AIAudit($pdo)))->listEnabled()
    );
    check('mixed lists both disposable models', in_array($freeSlug, $mixedSlugs, true) && in_array($paidSlug, $mixedSlugs, true));
} finally {
    if ($freeId > 0) {
        $pdo->prepare('DELETE FROM ld_ai_model WHERE id = :id')->execute([':id' => $freeId]);
    }
    if ($paidId > 0) {
        $pdo->prepare('DELETE FROM ld_ai_model WHERE id = :id')->execute([':id' => $paidId]);
    }
    $pdo->prepare(
        "DELETE FROM ld_ai_model WHERE provider = 'openrouter' AND model_slug LIKE 'test/manual-paid-%'"
    )->execute();

    if ($hadPreviousMode) {
        $pdo->prepare(
            "UPDATE ld_ai_setting SET setting_value = :mode WHERE setting_key = 'model_cost_mode'"
        )->execute([':mode' => (string) $previousMode]);
    } else {
        $pdo->prepare(
            "DELETE FROM ld_ai_setting WHERE setting_key = 'model_cost_mode'"
        )->execute();
    }
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
