<?php
/**
 * OpenRouterClient — server-side OpenRouter chat completions client.
 *
 * The client only ever receives a decrypted key from AIApiKeyStore in server
 * memory. It never returns, logs, or persists that key. A transport closure can
 * be injected for deterministic unit tests; production always uses cURL.
 */
class OpenRouterClient
{
    private ?PDO $pdo;
    private ?AIApiKeyStore $keyStore;
    private ?Closure $transport;
    private ?Closure $keyProvider;
    private ?Closure $sleeper;
    /** @var array<string, mixed> */
    private array $settings;
    private string $baseUrl;

    /**
     * @param callable|null $transport function(string $url, string[] $headers, string $body, int $timeout): array
     * @param callable|null $keyProvider function(): ?string
     * @param callable|null $sleeper function(float $seconds): void
     * @param array<string, mixed>|null $settings Test/deployment override for non-secret settings.
     */
    public function __construct(
        ?PDO $pdo = null,
        ?AIApiKeyStore $keyStore = null,
        ?callable $transport = null,
        ?callable $keyProvider = null,
        ?callable $sleeper = null,
        ?array $settings = null
    ) {
        $this->pdo = $pdo;
        $this->keyStore = $keyStore;
        $this->transport = $transport !== null ? Closure::fromCallable($transport) : null;
        $this->keyProvider = $keyProvider !== null ? Closure::fromCallable($keyProvider) : null;
        $this->sleeper = $sleeper !== null ? Closure::fromCallable($sleeper) : null;

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

        $this->baseUrl = AIConfig::baseUrl($pdo);
    }

    /**
     * Run a tiny chat completion to prove the active key and a model work.
     *
     * @return array<string, mixed>
     */
    public function testConnection(string $modelSlug, int $employeeId = 0): array
    {
        $result = $this->chat([
            [
                'role' => 'system',
                'content' => 'You are a connection test. Reply with the single word OK.',
            ],
            [
                'role' => 'user',
                'content' => 'Reply with OK.',
            ],
        ], [
            'model' => $modelSlug,
            // Free reasoning models can spend the first few tokens thinking.
            // Keep the probe bounded but large enough for a short visible answer.
            'max_tokens' => 64,
            'temperature' => 0,
            // Keep an admin connection test bounded well below a typical PHP
            // request limit, even if the saved generation timeout is larger.
            'max_retries' => 1,
            'request_timeout' => 20,
        ], $employeeId);

        if ($result['success']) {
            $result['message'] = 'The provider accepted the key and model.';
        }

        return $result;
    }

    /**
     * Send one chat completion, with bounded retries and a one-time JSON-schema
     * fallback. The returned payload never contains the key or the raw provider
     * response body.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $options = [], int $employeeId = 0): array
    {
        $startedAt = microtime(true);
        $requestedModel = isset($options['model']) ? trim((string) $options['model']) : '';
        $model = $this->resolveModel($requestedModel);
        if ($model === null) {
            return $this->failure('Choose an enabled model before testing the provider.', null, 0, 0, false, $startedAt);
        }
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{1,159}$/', $model) !== 1) {
            return $this->failure('The selected model slug is not valid.', null, 0, 0, false, $startedAt);
        }
        if (!$this->modelIsAllowed($model)) {
            return $this->failure(
                'The selected model is not enabled or is blocked by the current model cost mode.',
                null,
                0,
                0,
                false,
                $startedAt
            );
        }

        $validMessages = $this->normalizeMessages($messages);
        if ($validMessages === null) {
            return $this->failure('The messages are not valid.', null, 0, 0, false, $startedAt);
        }

        $keyResult = $this->resolveKey();
        if ($keyResult['key'] === null) {
            return $this->failure($keyResult['error'], null, 0, 0, false, $startedAt);
        }

        $maxTokens = $this->intSetting('max_output_tokens_per_step', 6000, 500, 20000);
        if (isset($options['max_tokens'])) {
            $maxTokens = max(1, min(20000, (int) $options['max_tokens']));
        }

        $temperature = $this->floatSetting('temperature', 0.3, 0.0, 1.0);
        if (isset($options['temperature']) && is_numeric($options['temperature'])) {
            $temperature = max(0.0, min(1.0, (float) $options['temperature']));
        }

        $responseFormat = null;
        if (isset($options['response_format']) && is_array($options['response_format'])) {
            $responseFormat = $options['response_format'];
        }

        $body = [
            'model' => $model,
            'messages' => $validMessages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];
        if ($responseFormat !== null) {
            $body['response_format'] = $responseFormat;
        }

        $maxRetries = $this->intSetting('max_retries', 2, 0, 5);
        if (isset($options['max_retries'])) {
            // A caller may shorten the admin-configured retry budget, never
            // exceed it.
            $maxRetries = min($maxRetries, max(0, min(5, (int) $options['max_retries'])));
        }
        $timeout = $this->intSetting('request_timeout', 90, 5, 300);
        if (isset($options['request_timeout'])) {
            // Likewise, a connection test may use a shorter timeout but cannot
            // extend the configured generation timeout.
            $timeout = min($timeout, max(5, min(300, (int) $options['request_timeout'])));
        }
        $retries = 0;
        $attempts = 0;
        $schemaFallbackUsed = false;

        while (true) {
            $attempts++;
            $response = $this->send($body, $keyResult['key'], $timeout);
            $status = (int) ($response['status'] ?? 0);
            $error = (string) ($response['error'] ?? '');

            if ($error !== '') {
                if ($retries < $maxRetries) {
                    $retries++;
                    $this->sleepFor($this->retryDelay($response, $retries));
                    continue;
                }

                return $this->failure(
                    'The provider could not be reached. Check the network and try again.',
                    $status > 0 ? $status : null,
                    $attempts,
                    $retries,
                    $schemaFallbackUsed,
                    $startedAt
                );
            }

            if ($status === 200) {
                $parsed = $this->parseResponse((string) ($response['body'] ?? ''), $model);
                if (!$parsed['success']) {
                    $parsed['attempts'] = $attempts;
                    $parsed['retries'] = $retries;
                    $parsed['response_format_fallback'] = $schemaFallbackUsed;
                    $parsed['latency_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                    return $parsed;
                }

                $parsed['attempts'] = $attempts;
                $parsed['retries'] = $retries;
                $parsed['response_format_fallback'] = $schemaFallbackUsed;
                $parsed['latency_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                return $parsed;
            }

            // A provider can reject json_schema while accepting json_object. Try
            // the weaker mode exactly once, independent of the retry budget.
            if (
                $status === 400
                && $responseFormat !== null
                && ($responseFormat['type'] ?? '') === 'json_schema'
                && !$schemaFallbackUsed
            ) {
                $schemaFallbackUsed = true;
                $body['response_format'] = ['type' => 'json_object'];
                continue;
            }

            if (in_array($status, [429, 500, 502, 503, 504], true) && $retries < $maxRetries) {
                $retries++;
                $this->sleepFor($this->retryDelay($response, $retries));
                continue;
            }

            return $this->failure(
                $this->messageForStatus($status),
                $status > 0 ? $status : null,
                $attempts,
                $retries,
                $schemaFallbackUsed,
                $startedAt
            );
        }
    }

    /**
     * @return array{key: ?string, error: string}
     */
    private function resolveKey(): array
    {
        try {
            if ($this->keyProvider !== null) {
                $key = ($this->keyProvider)();
                if (is_string($key) && trim($key) !== '') {
                    return ['key' => trim($key), 'error' => ''];
                }
                return ['key' => null, 'error' => 'No active OpenRouter key is configured.'];
            }

            if ($this->keyStore !== null) {
                $key = $this->keyStore->activePlaintextOrNull();
                return $key === null
                    ? ['key' => null, 'error' => 'No active OpenRouter key is configured.']
                    : ['key' => $key, 'error' => ''];
            }

            if ($this->pdo !== null) {
                $key = (new AIApiKeyStore($this->pdo))->activePlaintextOrNull();
                return $key === null
                    ? ['key' => null, 'error' => 'No active OpenRouter key is configured.']
                    : ['key' => $key, 'error' => ''];
            }
        } catch (Throwable $e) {
            return [
                'key' => null,
                'error' => 'The active API key could not be decrypted. Check the master key.',
            ];
        }

        return ['key' => null, 'error' => 'No active OpenRouter key is configured.'];
    }

    private function resolveModel(string $requestedModel): ?string
    {
        if ($requestedModel !== '') {
            return $requestedModel;
        }

        if ($this->pdo !== null) {
            try {
                $where = AIConfig::modelModeWhere(AIConfig::modelCostMode($this->pdo));
                $stmt = $this->pdo->query(
                    "SELECT model_slug
                       FROM ld_ai_model
                      WHERE provider = 'openrouter' AND enabled = 1 AND {$where}
                      ORDER BY is_default DESC, is_fallback DESC, id ASC
                      LIMIT 1"
                );
                $slug = $stmt->fetchColumn();
                if (is_string($slug) && $slug !== '') {
                    return $slug;
                }
            } catch (Throwable $e) {
                // A schema/config problem is reported by the caller.
            }
        }

        return null;
    }

    /**
     * Defense in depth: even a direct chat call cannot bypass the admin model
     * cost mode. The generator and test endpoint also filter before this point.
     */
    private function modelIsAllowed(string $model): bool
    {
        if ($this->pdo === null) {
            return true;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT model_slug, prompt_price, completion_price
                   FROM ld_ai_model
                  WHERE provider = 'openrouter' AND model_slug = :slug AND enabled = 1
                  LIMIT 1"
            );
            $stmt->execute([':slug' => $model]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
            }

            return AIConfig::modelAllowedForMode($row, AIConfig::modelCostMode($this->pdo));
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<int, mixed> $messages
     * @return array<int, array{role: string, content: string}>|null
     */
    private function normalizeMessages(array $messages): ?array
    {
        if ($messages === []) {
            return null;
        }

        $normalized = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                return null;
            }
            $role = (string) ($message['role'] ?? '');
            $content = $message['content'] ?? null;
            if (!in_array($role, ['system', 'user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                return null;
            }
            $normalized[] = ['role' => $role, 'content' => $content];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status: int, body: string, error: string, headers: array<string, string>, errno: int}
     */
    private function send(array $body, string $key, int $timeout): array
    {
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['status' => 0, 'body' => '', 'error' => 'request_encode_failed', 'headers' => [], 'errno' => 0];
        }

        $headers = [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Accept: application/json',
            'HTTP-Referer: ' . $this->referer(),
            'X-Title: Learning AI Course Assistant',
        ];

        $url = $this->baseUrl . '/chat/completions';
        if ($this->transport !== null) {
            $result = ($this->transport)($url, $headers, $json, $timeout);
            if (!is_array($result)) {
                return ['status' => 0, 'body' => '', 'error' => 'transport_invalid', 'headers' => [], 'errno' => 0];
            }

            return [
                'status' => (int) ($result['status'] ?? 0),
                'body' => (string) ($result['body'] ?? ''),
                'error' => (string) ($result['error'] ?? ''),
                'headers' => is_array($result['headers'] ?? null) ? $result['headers'] : [],
                'errno' => (int) ($result['errno'] ?? 0),
            ];
        }

        return $this->curlTransport($url, $headers, $json, $timeout);
    }

    /**
     * @param string[] $headers
     * @return array{status: int, body: string, error: string, headers: array<string, string>, errno: int}
     */
    private function curlTransport(string $url, array $headers, string $body, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            return ['status' => 0, 'body' => '', 'error' => 'curl_unavailable', 'headers' => [], 'errno' => 0];
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Learning-AI-Assistant/1.0',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseHeaders): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return $length;
            },
        ]);

        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = (string) curl_error($ch);
        $errno = (int) curl_errno($ch);
        curl_close($ch);

        return [
            'status' => $status,
            'body' => $responseBody === false ? '' : (string) $responseBody,
            'error' => $responseBody === false ? ($error !== '' ? $error : 'request_failed') : '',
            'headers' => $responseHeaders,
            'errno' => $errno,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(string $body, string $requestedModel): array
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->failure('The provider returned an unreadable response.', 200, 1, 0, false, microtime(true));
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $part) {
                if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                    $parts[] = $part['text'];
                }
            }
            $content = implode("\n", $parts);
        }
        if (!is_string($content) || trim($content) === '') {
            return $this->failure('The provider returned an empty response.', 200, 1, 0, false, microtime(true));
        }

        $usageRaw = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $promptTokens = (int) ($usageRaw['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usageRaw['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usageRaw['total_tokens'] ?? 0);
        if ($totalTokens === 0 && ($promptTokens > 0 || $completionTokens > 0)) {
            $totalTokens = $promptTokens + $completionTokens;
        }

        $model = (string) ($decoded['model'] ?? $requestedModel);

        return [
            'success' => true,
            'message' => 'Provider response received.',
            'http_status' => 200,
            'content' => $content,
            'model' => $model !== '' ? $model : $requestedModel,
            'usage' => [
                'prompt_tokens' => max(0, $promptTokens),
                'completion_tokens' => max(0, $completionTokens),
                'total_tokens' => max(0, $totalTokens),
            ],
            'attempts' => 1,
            'retries' => 0,
            'response_format_fallback' => false,
            'latency_ms' => 0,
        ];
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'The provider rejected the request. Check the selected model and settings.',
            401 => 'The provider rejected the API key. Add a valid key and test it again.',
            403 => 'The provider rejected the request. Check that the key has inference permission, credits, and access to the selected model.',
            404 => 'The selected model was not found at the provider.',
            422 => 'The provider could not process the request.',
            429 => 'The provider rate limit was reached. Try again later.',
            500, 502, 503, 504 => 'The provider is temporarily unavailable. Try again shortly.',
            default => $status > 0
                ? 'The provider returned HTTP ' . $status . '.'
                : 'The provider request failed.',
        };
    }

    /**
     * @param array<string, mixed> $response
     */
    private function retryDelay(array $response, int $retryNumber): float
    {
        $headers = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'retry-after' && is_numeric($value)) {
                return max(0.0, min(5.0, (float) $value));
            }
        }

        return min(2.0, 0.25 * max(1, $retryNumber));
    }

    private function sleepFor(float $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }
        if ($this->sleeper !== null) {
            ($this->sleeper)($seconds);
            return;
        }
        usleep((int) round(min(5.0, $seconds) * 1000000));
    }

    private function referer(): string
    {
        $configured = AIConfig::value('http_referer', '');
        if (is_string($configured) && trim($configured) !== '') {
            $configured = str_replace(["\r", "\n"], '', trim($configured));
            return substr($configured, 0, 500);
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host) !== 1) {
            $host = 'localhost';
        }
        $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';

        return ($https ? 'https' : 'http') . '://' . $host . '/';
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
     * @return array<string, mixed>
     */
    private function failure(
        string $message,
        ?int $httpStatus,
        int $attempts,
        int $retries,
        bool $schemaFallbackUsed,
        float $startedAt
    ): array {
        return [
            'success' => false,
            'message' => $message,
            'http_status' => $httpStatus,
            'content' => null,
            'model' => null,
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            'attempts' => $attempts,
            'retries' => $retries,
            'response_format_fallback' => $schemaFallbackUsed,
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
