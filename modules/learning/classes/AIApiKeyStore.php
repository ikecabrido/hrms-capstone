<?php
/**
 * AIApiKeyStore — encrypted provider key lifecycle and provider test.
 *
 * Public methods never return plaintext keys. The only method that decrypts is
 * activePlaintext(), which is reserved for the server-side provider client.
 */
class AIApiKeyStore
{
    private PDO $pdo;
    private ?AISecretBox $box;
    private AIAudit $audit;

    public function __construct(PDO $pdo, ?AISecretBox $box = null, ?AIAudit $audit = null)
    {
        $this->pdo = $pdo;
        $this->box = $box;
        $this->audit = $audit ?? new AIAudit($pdo);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listKeys(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, provider, label, key_fingerprint, key_last4, status,
                    last_used_at, last_tested_at, last_test_status, created_at
               FROM ld_ai_api_key
              WHERE provider = 'openrouter'
              ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeMetadata(): ?array
    {
        $stmt = $this->pdo->query(
            "SELECT id, label, key_fingerprint, key_last4, status,
                    last_used_at, last_tested_at, last_test_status, created_at
               FROM ld_ai_api_key
              WHERE provider = 'openrouter' AND status = 'active'
              ORDER BY id DESC
              LIMIT 1"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Add a key. The first key becomes active; a later key is stored disabled
     * until the admin tests it and explicitly activates it.
     *
     * @return array{success: bool, message: string, key: array<string, mixed>|null}
     */
    public function create(string $apiKey, string $label, int $employeeId): array
    {
        $apiKey = trim($apiKey);
        $label = trim($label);
        if ($label === '') {
            $label = 'OpenRouter key';
        }
        $label = mb_substr($label, 0, 100);

        if (strlen($apiKey) < 20 || strlen($apiKey) > 512) {
            return ['success' => false, 'message' => 'The API key length is not valid.', 'key' => null];
        }
        if (preg_match('/\s/', $apiKey) === 1) {
            return ['success' => false, 'message' => 'The API key must not contain spaces or line breaks.', 'key' => null];
        }
        if (preg_match('/^sk-or-[A-Za-z0-9._-]{10,}$/', $apiKey) !== 1) {
            return ['success' => false, 'message' => 'That does not look like an OpenRouter key (expected sk-or-...).', 'key' => null];
        }

        try {
            $sealed = $this->box()->encrypt($apiKey);
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'key' => null];
        }

        $duplicate = $this->pdo->prepare(
            "SELECT id, label, status FROM ld_ai_api_key
              WHERE provider = 'openrouter' AND key_fingerprint = :fingerprint
              LIMIT 1"
        );
        $duplicate->execute([':fingerprint' => $sealed['fingerprint']]);
        $existing = $duplicate->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return [
                'success' => false,
                'message' => 'This key is already stored as "' . (string) $existing['label'] . '".',
                'key' => null,
            ];
        }

        $hasActive = (bool) $this->pdo->query(
            "SELECT 1 FROM ld_ai_api_key WHERE provider = 'openrouter' AND status = 'active' LIMIT 1"
        )->fetchColumn();
        $status = $hasActive ? 'disabled' : 'active';

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO ld_ai_api_key
                    (provider, label, key_ciphertext, key_nonce, key_algorithm,
                     key_fingerprint, key_last4, status, created_by)
                 VALUES
                    ('openrouter', :label, :ciphertext, :nonce, :algorithm,
                     :fingerprint, :last4, :status, :created_by)"
            );
            $stmt->bindValue(':label', $label, PDO::PARAM_STR);
            $stmt->bindValue(':ciphertext', $sealed['ciphertext'], PDO::PARAM_LOB);
            $stmt->bindValue(':nonce', $sealed['nonce'], PDO::PARAM_LOB);
            $stmt->bindValue(':algorithm', $sealed['algorithm'], PDO::PARAM_STR);
            $stmt->bindValue(':fingerprint', $sealed['fingerprint'], PDO::PARAM_STR);
            $stmt->bindValue(':last4', $sealed['last4'], PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':created_by', $employeeId > 0 ? $employeeId : null, PDO::PARAM_INT);
            $stmt->execute();

            $id = (int) $this->pdo->lastInsertId();
            $this->audit->log($employeeId, 'key.created', 'api_key', (string) $id, [
                'label' => $label,
                'last4' => $sealed['last4'],
                'fingerprint' => substr($sealed['fingerprint'], 0, 12),
                'status' => $status,
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The key could not be saved.', 'key' => null];
        }

        return [
            'success' => true,
            'message' => $status === 'active'
                ? 'Key saved and active. Test it before generating.'
                : 'Key saved as disabled. Test it, then activate it.',
            'key' => $this->findMetadata($id),
        ];
    }

    /**
     * @return array{success: bool, message: string, key: array<string, mixed>|null}
     */
    public function activate(int $id, int $employeeId): array
    {
        $row = $this->findRow($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Key not found.', 'key' => null];
        }
        if ($row['status'] === 'revoked') {
            return ['success' => false, 'message' => 'A revoked key cannot be activated again.', 'key' => null];
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec(
                "UPDATE ld_ai_api_key
                    SET status = 'disabled'
                  WHERE provider = 'openrouter' AND status = 'active'"
            );
            $stmt = $this->pdo->prepare(
                "UPDATE ld_ai_api_key SET status = 'active' WHERE id = :id AND provider = 'openrouter'"
            );
            $stmt->execute([':id' => $id]);
            $this->audit->log($employeeId, 'key.activated', 'api_key', (string) $id, [
                'label' => (string) $row['label'],
                'last4' => (string) $row['key_last4'],
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'The key could not be activated.', 'key' => null];
        }

        return ['success' => true, 'message' => 'Key activated.', 'key' => $this->findMetadata($id)];
    }

    /**
     * @return array{success: bool, message: string, key: array<string, mixed>|null}
     */
    public function disable(int $id, int $employeeId): array
    {
        $row = $this->findRow($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Key not found.', 'key' => null];
        }
        if ($row['status'] === 'revoked') {
            return ['success' => false, 'message' => 'The key is already revoked.', 'key' => null];
        }

        $stmt = $this->pdo->prepare(
            "UPDATE ld_ai_api_key SET status = 'disabled' WHERE id = :id AND provider = 'openrouter'"
        );
        $stmt->execute([':id' => $id]);
        $this->audit->log($employeeId, 'key.disabled', 'api_key', (string) $id, [
            'label' => (string) $row['label'],
            'last4' => (string) $row['key_last4'],
        ]);

        return ['success' => true, 'message' => 'Key disabled.', 'key' => $this->findMetadata($id)];
    }

    /**
     * @return array{success: bool, message: string, key: array<string, mixed>|null}
     */
    public function revoke(int $id, int $employeeId): array
    {
        $row = $this->findRow($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Key not found.', 'key' => null];
        }

        $stmt = $this->pdo->prepare(
            "UPDATE ld_ai_api_key SET status = 'revoked' WHERE id = :id AND provider = 'openrouter'"
        );
        $stmt->execute([':id' => $id]);
        $this->audit->log($employeeId, 'key.revoked', 'api_key', (string) $id, [
            'label' => (string) $row['label'],
            'last4' => (string) $row['key_last4'],
        ]);

        return ['success' => true, 'message' => 'Key revoked permanently.', 'key' => $this->findMetadata($id)];
    }

    /**
     * Test a key with a minimal authenticated OpenRouter request.
     *
     * @return array{success: bool, status: string, message: string, http_status: int|null, key: array<string, mixed>|null}
     */
    public function test(int $id, int $employeeId): array
    {
        $row = $this->findRow($id);
        if (!$row) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Key not found.',
                'http_status' => null,
                'key' => null,
            ];
        }
        if ($row['status'] === 'revoked') {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'A revoked key cannot be tested.',
                'http_status' => null,
                'key' => null,
            ];
        }

        try {
            $plaintext = $this->box()->decrypt(
                (string) $row['key_ciphertext'],
                (string) $row['key_nonce']
            );
        } catch (Throwable $e) {
            $this->recordTest($id, 'failed');
            $this->audit->log($employeeId, 'key.tested', 'api_key', (string) $id, [
                'status' => 'failed',
                'reason' => 'decrypt_failed',
            ]);
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'The key could not be decrypted. Check the master key.',
                'http_status' => null,
                'key' => $this->findMetadata($id),
            ];
        }

        $result = $this->requestKeyStatus($plaintext);
        unset($plaintext);

        $status = $result['success'] ? 'success' : 'failed';
        $this->recordTest($id, $status);
        $this->audit->log($employeeId, 'key.tested', 'api_key', (string) $id, [
            'status' => $status,
            'http_status' => $result['http_status'],
        ]);

        return [
            'success' => $result['success'],
            'status' => $status,
            'message' => $result['message'],
            'http_status' => $result['http_status'],
            'key' => $this->findMetadata($id),
        ];
    }

    /**
     * Decrypt the active key for a server-side provider call. Never expose the
     * return value to the browser, logs, or an error message.
     */
    public function activePlaintext(): string
    {
        $plaintext = $this->activePlaintextOrNull();
        if ($plaintext === null) {
            throw new RuntimeException('No active OpenRouter key is configured.');
        }

        return $plaintext;
    }

    /**
     * Return null when no active key exists, but still throw if an existing
     * active key cannot be decrypted. This lets callers distinguish a missing
     * key from a master-key failure without inspecting exception text.
     */
    public function activePlaintextOrNull(): ?string
    {
        $stmt = $this->pdo->query(
            "SELECT key_ciphertext, key_nonce
               FROM ld_ai_api_key
              WHERE provider = 'openrouter' AND status = 'active'
              ORDER BY id DESC
              LIMIT 1"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->box()->decrypt((string) $row['key_ciphertext'], (string) $row['key_nonce']);
    }

    private function box(): AISecretBox
    {
        if ($this->box === null) {
            $this->box = new AISecretBox();
        }

        return $this->box;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findMetadata(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, provider, label, key_fingerprint, key_last4, status,
                    last_used_at, last_tested_at, last_test_status, created_at
               FROM ld_ai_api_key
              WHERE id = :id AND provider = 'openrouter'
              LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Internal use only: includes ciphertext/nonce, so never return this row.
     *
     * @return array<string, mixed>|null
     */
    private function findRow(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ld_ai_api_key WHERE id = :id AND provider = 'openrouter' LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function recordTest(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE ld_ai_api_key
                SET last_tested_at = NOW(),
                    last_test_status = :status
              WHERE id = :id AND provider = 'openrouter'"
        );
        $stmt->execute([':id' => $id, ':status' => $status]);
    }

    /**
     * @return array{success: bool, message: string, http_status: int|null}
     */
    private function requestKeyStatus(string $apiKey): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL is not available on this server.', 'http_status' => null];
        }

        $url = AIConfig::baseUrl($this->pdo) . '/key';
        $timeout = max(5, min(30, (int) AIConfig::runtimeValue($this->pdo, 'request_timeout', 90)));
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Learning-AI-Assistant/1.0',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $httpStatus = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $curlError !== '') {
            return [
                'success' => false,
                'message' => 'The provider could not be reached. Check the network and try again.',
                'http_status' => $httpStatus > 0 ? $httpStatus : null,
            ];
        }

        if ($httpStatus === 200) {
            $decoded = json_decode((string) $body, true);
            if (is_array($decoded)) {
                return ['success' => true, 'message' => 'The provider accepted the key.', 'http_status' => 200];
            }
            return ['success' => false, 'message' => 'The provider returned an unexpected response.', 'http_status' => 200];
        }

        if (in_array($httpStatus, [401, 403], true)) {
            return ['success' => false, 'message' => 'The provider rejected this key.', 'http_status' => $httpStatus];
        }
        if ($httpStatus === 429) {
            return ['success' => false, 'message' => 'The provider rate limit was reached. Try again later.', 'http_status' => 429];
        }

        return [
            'success' => false,
            'message' => 'The provider returned HTTP ' . $httpStatus . '.',
            'http_status' => $httpStatus > 0 ? $httpStatus : null,
        ];
    }
}
