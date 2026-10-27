<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    aiEndpointError(422, 'A valid model id is required.');
}

$stmt = $ctx['pdo']->prepare(
    "SELECT id, model_slug, display_name, prompt_price, completion_price
       FROM ld_ai_model
      WHERE id = :id AND provider = 'openrouter' AND enabled = 1
      LIMIT 1"
);
$stmt->execute([':id' => $id]);
$model = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$model) {
    aiEndpointError(422, 'Enable the model before testing it.');
}

$costMode = AIConfig::modelCostMode($ctx['pdo']);
if (!AIConfig::modelAllowedForMode($model, $costMode)) {
    aiEndpointError(
        422,
        'This model is blocked by the current model cost mode ('
            . AIConfig::modelCostModeLabel($costMode)
            . '). Change the mode in Settings before testing it.'
    );
}

$now = time();
$window = 600;
$limit = 10;
$attempts = $_SESSION['ld_ai_model_test_times'] ?? [];
if (!is_array($attempts)) {
    $attempts = [];
}
$attempts = array_values(array_filter($attempts, static function ($timestamp) use ($now, $window): bool {
    return is_int($timestamp) && $timestamp > ($now - $window);
}));
if (count($attempts) >= $limit) {
    aiEndpointError(429, 'Too many model tests. Try again in a few minutes.');
}
$attempts[] = $now;
$_SESSION['ld_ai_model_test_times'] = $attempts;

try {
    $client = new OpenRouterClient($ctx['pdo']);
    $result = $client->testConnection((string) $model['model_slug'], $ctx['employeeId']);
} catch (Throwable $e) {
    aiEndpointError(500, 'The model test could not be completed.');
}

try {
    $ctx['audit']->log($ctx['employeeId'], 'model.tested', 'model', (string) $id, [
        'model' => (string) $model['model_slug'],
        'success' => $result['success'] ? 1 : 0,
        'http_status' => $result['http_status'],
        'attempts' => $result['attempts'],
        'retries' => $result['retries'],
        'latency_ms' => $result['latency_ms'],
        'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
        'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
    ]);
} catch (Throwable $e) {
    aiEndpointError(500, 'The model test result could not be recorded.');
}

$result['model_display'] = (string) ($model['display_name'] ?: $model['model_slug']);
if ($result['success']) {
    $result['message'] = 'Model test passed for ' . $result['model_display']
        . ' (' . (int) $result['latency_ms'] . ' ms).';
}
// A completed test is a valid response even when the provider rejects the
// request. Returning 200 keeps expected provider failures out of the browser
// console while the JSON success flag still drives the error toast.
aiEndpointJson(200, $result);
