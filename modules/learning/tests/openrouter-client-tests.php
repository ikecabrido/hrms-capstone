<?php
/**
 * Standalone OpenRouterClient tests. No database or network access is needed;
 * a transport closure returns deterministic provider responses.
 *
 *     php modules/learning/tests/openrouter-client-tests.php
 */

require_once dirname(__DIR__) . '/classes/AIConfig.php';
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
    echo "FAIL  {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
}

/**
 * @param array<int, array<string, mixed>> $responses
 * @param array<int, array<string, mixed>> $requests
 * @param array<string, mixed> $settings
 */
function makeClient(array $responses, array &$requests, array $settings = []): OpenRouterClient
{
    $index = 0;
    $transport = static function (string $url, array $headers, string $body, int $timeout) use (&$responses, &$index, &$requests): array {
        $requests[] = [
            'url' => $url,
            'headers' => $headers,
            'body' => json_decode($body, true),
            'timeout' => $timeout,
        ];
        $response = $responses[$index] ?? [
            'status' => 0,
            'body' => '',
            'error' => 'no fake response configured',
            'headers' => [],
        ];
        $index++;

        return $response;
    };

    return new OpenRouterClient(
        null,
        null,
        $transport,
        static fn(): string => 'test-key-fixture',
        static function (float $seconds): void {
            // Retry tests do not sleep.
        },
        $settings
    );
}

function successBody(string $content = 'OK', array $usage = []): string
{
    return json_encode([
        'model' => 'openai/gpt-4.1-mini',
        'choices' => [
            ['message' => ['content' => $content]],
        ],
        'usage' => $usage,
    ], JSON_UNESCAPED_SLASHES);
}

$messages = [
    ['role' => 'user', 'content' => 'Reply with OK.'],
];

// 1. Successful response parsing and safe metadata capture.
$requests = [];
$client = makeClient([
    ['status' => 200, 'body' => successBody('OK', [
        'prompt_tokens' => 8,
        'completion_tokens' => 2,
        'total_tokens' => 10,
    ]), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 2]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini', 'max_tokens' => 16]);
check('success response is parsed', $result['success'] === true && $result['content'] === 'OK');
check('usage is captured', $result['usage'] === ['prompt_tokens' => 8, 'completion_tokens' => 2, 'total_tokens' => 10]);
check('model metadata is captured', $result['model'] === 'openai/gpt-4.1-mini');
check('one attempt is reported', $result['attempts'] === 1 && $result['retries'] === 0);
check('request uses the pinned chat endpoint', ($requests[0]['url'] ?? '') === 'https://openrouter.ai/api/v1/chat/completions');
check('request sends provider attribution headers', in_array('X-Title: Learning AI Course Assistant', $requests[0]['headers'] ?? [], true));
check('request does not leak the key in the result', !str_contains(json_encode($result), 'test-key-fixture'));

// 2. Retryable 429 then success.
$requests = [];
$client = makeClient([
    ['status' => 429, 'body' => '', 'error' => '', 'headers' => ['retry-after' => '0']],
    ['status' => 200, 'body' => successBody(), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 2]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('429 is retried', $result['success'] === true && $result['attempts'] === 2 && $result['retries'] === 1);

// 3. Authentication errors are not retried.
$requests = [];
$client = makeClient([
    ['status' => 401, 'body' => '', 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 3]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('401 is not retried', $result['success'] === false && $result['attempts'] === 1 && count($requests) === 1);
check('401 maps to a safe key message', str_contains($result['message'], 'rejected the API key'));

$requests = [];
$client = makeClient([
    ['status' => 403, 'body' => '', 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 3]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('403 is not retried', $result['success'] === false && $result['attempts'] === 1 && count($requests) === 1);
check('403 explains inference permission and model access', str_contains($result['message'], 'inference permission') && str_contains($result['message'], 'selected model'));

// 4. JSON Schema rejection falls back to JSON object once.
$requests = [];
$client = makeClient([
    ['status' => 400, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 200, 'body' => successBody('{"ok":true}'), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 0]);
$result = $client->chat($messages, [
    'model' => 'openai/gpt-4.1-mini',
    'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'test', 'schema' => ['type' => 'object']]],
]);
check('schema mode falls back after a 400', $result['success'] === true && $result['attempts'] === 2);
check('fallback is reported', $result['response_format_fallback'] === true);
check('fallback uses json_object', (($requests[1]['body']['response_format']['type'] ?? '') === 'json_object'));

// 5. Network failures retry within the cap.
$requests = [];
$client = makeClient([
    ['status' => 0, 'body' => '', 'error' => 'timeout', 'headers' => []],
    ['status' => 200, 'body' => successBody(), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 1]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('network timeout is retried', $result['success'] === true && $result['attempts'] === 2);

// 6. Missing key never reaches the transport.
$requests = [];
$client = new OpenRouterClient(
    null,
    null,
    static function (string $url, array $headers, string $body, int $timeout) use (&$requests): array {
        $requests[] = $url;
        return ['status' => 200, 'body' => successBody(), 'error' => '', 'headers' => []];
    },
    static fn(): string => '',
    null,
    ['max_retries' => 2]
);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('missing key is rejected without a request', $result['success'] === false && count($requests) === 0);
check('missing key message is clear', str_contains($result['message'], 'No active OpenRouter key'));

// 7. Empty content is an error.
$requests = [];
$client = makeClient([
    ['status' => 200, 'body' => json_encode(['choices' => []]), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 1]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('empty provider content is rejected', $result['success'] === false && str_contains($result['message'], 'empty response'));

// 8. Retries stop at the configured cap.
$requests = [];
$client = makeClient([
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 2]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('retry cap is enforced', $result['success'] === false && $result['attempts'] === 3 && $result['retries'] === 2);
check('server error maps to a safe message', str_contains($result['message'], 'temporarily unavailable'));

// 9. Invalid messages are rejected before the transport.
$requests = [];
$client = makeClient([], $requests);
$result = $client->chat([['role' => 'user', 'content' => '']], ['model' => 'openai/gpt-4.1-mini']);
check('invalid messages are rejected', $result['success'] === false && count($requests) === 0);

// 10. A second schema rejection is not retried forever.
$requests = [];
$client = makeClient([
    ['status' => 400, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 400, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 400, 'body' => '', 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 3]);
$result = $client->chat($messages, [
    'model' => 'openai/gpt-4.1-mini',
    'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'test', 'schema' => ['type' => 'object']]],
]);
check('schema fallback happens only once', $result['success'] === false && $result['attempts'] === 2 && count($requests) === 2);

// 11. Missing usage is normalized to zero.
$requests = [];
$client = makeClient([
    ['status' => 200, 'body' => successBody('OK'), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 0]);
$result = $client->chat($messages, ['model' => 'openai/gpt-4.1-mini']);
check('missing usage is normalized', $result['usage'] === ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0]);

// 12. The connection test uses a short, bounded request.
$requests = [];
$client = makeClient([
    ['status' => 200, 'body' => successBody('OK'), 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 0, 'request_timeout' => 300]);
$result = $client->testConnection('openai/gpt-4.1-mini');
check('connection test succeeds', $result['success'] === true);
check('connection test timeout is bounded', ($requests[0]['timeout'] ?? 0) === 20);
check('connection test uses a small output budget', (($requests[0]['body']['max_tokens'] ?? 0) === 64));

// 13. Callers cannot extend the admin-configured retry/timeout budget.
$requests = [];
$client = makeClient([
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
    ['status' => 500, 'body' => '', 'error' => '', 'headers' => []],
], $requests, ['max_retries' => 0, 'request_timeout' => 5]);
$result = $client->chat($messages, [
    'model' => 'openai/gpt-4.1-mini',
    'max_retries' => 5,
    'request_timeout' => 300,
]);
check('caller cannot extend retry budget', $result['attempts'] === 1 && count($requests) === 1);
check('caller cannot extend timeout', ($requests[0]['timeout'] ?? 0) === 5);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
