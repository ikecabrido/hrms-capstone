<?php
/**
 * AISecretBox — authenticated encryption for provider API keys.
 *
 * OpenSSL AES-256-GCM is used because the local PHP build does not include
 * libsodium. The 16-byte GCM tag is prepended to the ciphertext; the nonce is
 * stored separately in ld_ai_api_key.key_nonce. Only the ciphertext, nonce,
 * fingerprint, and last four characters are ever persisted.
 *
 * The master key is read from LD_AI_MASTER_KEY, LD_AI_MASTER_KEY_FILE, or the
 * optional local config file handled by AIConfig. It is never stored in the
 * database or returned to the browser.
 */
class AISecretBox
{
    public const ALGORITHM = 'aes-256-gcm';
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_BYTES = 12;
    private const TAG_BYTES = 16;

    private string $masterKey;
    private string $fingerprintPepper;

    public function __construct(?string $masterKey = null, ?string $fingerprintPepper = null)
    {
        $masterKey = $masterKey ?? self::configuredMasterKey();
        if ($masterKey === null || $masterKey === '') {
            throw new RuntimeException(
                'AI encryption is not configured. Set LD_AI_MASTER_KEY or LD_AI_MASTER_KEY_FILE.'
            );
        }

        $this->masterKey = self::normalizeKey($masterKey);
        $this->fingerprintPepper = $fingerprintPepper
            ?? (string) AIConfig::value('fingerprint_pepper', $this->masterKey);
    }

    /**
     * Whether encryption can run without revealing any key material.
     *
     * @return array{configured: bool, message: string}
     */
    public static function status(): array
    {
        try {
            $master = self::configuredMasterKey();
            if ($master === null || $master === '') {
                return [
                    'configured' => false,
                    'message' => 'Set LD_AI_MASTER_KEY or LD_AI_MASTER_KEY_FILE before adding a provider key.',
                ];
            }

            $length = strlen(self::normalizeKey($master));
            if ($length !== 32) {
                return [
                    'configured' => false,
                    'message' => 'The AI master key must decode to exactly 32 bytes.',
                ];
            }

            return [
                'configured' => true,
                'message' => 'Encryption is ready.',
            ];
        } catch (Throwable $e) {
            return [
                'configured' => false,
                'message' => 'The AI master key is not valid: ' . $e->getMessage(),
            ];
        }
    }

    public static function configuredMasterKey(): ?string
    {
        $value = AIConfig::value('master_key', '');
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $file = AIConfig::value('master_key_file', '');
        if (is_string($file) && $file !== '') {
            if (!is_file($file) || !is_readable($file)) {
                throw new RuntimeException('LD_AI_MASTER_KEY_FILE is not readable.');
            }
            $contents = trim((string) file_get_contents($file));
            if ($contents !== '') {
                return $contents;
            }
        }

        return null;
    }

    /**
     * Encrypt one provider key.
     *
     * @return array{ciphertext: string, nonce: string, algorithm: string, fingerprint: string, last4: string}
     */
    public function encrypt(string $plaintext): array
    {
        if ($plaintext === '') {
            throw new InvalidArgumentException('The provider key is empty.');
        }

        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_BYTES) {
            throw new RuntimeException('The provider key could not be encrypted.');
        }

        return [
            'ciphertext' => $tag . $ciphertext,
            'nonce' => $nonce,
            'algorithm' => self::ALGORITHM,
            'fingerprint' => $this->fingerprint($plaintext),
            'last4' => substr($plaintext, -4),
        ];
    }

    public function decrypt(string $ciphertext, string $nonce): string
    {
        if (strlen($ciphertext) <= self::TAG_BYTES || $nonce === '') {
            throw new RuntimeException('The encrypted provider key is malformed.');
        }

        $tag = substr($ciphertext, 0, self::TAG_BYTES);
        $payload = substr($ciphertext, self::TAG_BYTES);
        $plaintext = openssl_decrypt(
            $payload,
            self::CIPHER,
            $this->masterKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('The provider key could not be decrypted. Check the master key.');
        }

        return $plaintext;
    }

    public function fingerprint(string $plaintext): string
    {
        return hash_hmac('sha256', $plaintext, $this->fingerprintPepper);
    }

    private static function normalizeKey(string $value): string
    {
        $value = trim($value);

        if (str_starts_with($value, 'base64:')) {
            $decoded = base64_decode(substr($value, 7), true);
            if ($decoded === false) {
                throw new RuntimeException('The base64 master key is invalid.');
            }
            return $decoded;
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $value) === 1) {
            $decoded = hex2bin($value);
            if ($decoded === false) {
                throw new RuntimeException('The hex master key is invalid.');
            }
            return $decoded;
        }

        // A 44-character base64 value is accepted too; a raw 32-byte value is
        // the final fallback.
        if (strlen($value) === 44 && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $value) === 1) {
            $decoded = base64_decode($value, true);
            if ($decoded !== false && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        return $value;
    }
}
