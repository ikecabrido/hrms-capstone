<?php
/**
 * ApiAuth — Shared API key verification for all cross-module API endpoints.
 * Reads ld_api_key table; rejects if missing or inactive.
 */
class ApiAuth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Verify an API key is valid and active for the given module.
     *
     * @param string $apiKey      The API key from the request header/body
     * @param string $moduleName  The requesting module name (e.g. 'performance-management')
     * @return array ['valid' => bool, 'error' => ?string, 'moduleId' => ?int]
     */
    public function verify(string $apiKey, string $moduleName): array
    {
        if (empty($apiKey)) {
            return ['valid' => false, 'error' => 'API key is required', 'moduleId' => null];
        }

        if (empty($moduleName)) {
            return ['valid' => false, 'error' => 'Module name is required', 'moduleId' => null];
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, module_name, api_key, is_active
                 FROM ld_api_key
                 WHERE module_name = :module AND api_key = :key
                 LIMIT 1"
            );
            $stmt->execute([':module' => $moduleName, ':key' => $apiKey]);
            $row = $stmt->fetch();

            if (!$row) {
                return ['valid' => false, 'error' => 'Invalid API key', 'moduleId' => null];
            }

            if (!$row['is_active']) {
                return ['valid' => false, 'error' => 'API key is inactive', 'moduleId' => (int)$row['id']];
            }

            return ['valid' => true, 'error' => null, 'moduleId' => (int)$row['id']];
        } catch (PDOException $e) {
            return ['valid' => false, 'error' => 'Auth check failed: ' . $e->getMessage(), 'moduleId' => null];
        }
    }

    /**
     * Extract API key from request — checks Authorization header first, then GET/POST.
     */
    public static function extractKey(): string
    {
        // Check Authorization header: "Bearer <key>" or "X-API-Key: <key>"
        if (!empty($_SERVER['HTTP_X_API_KEY'])) {
            return trim($_SERVER['HTTP_X_API_KEY']);
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            return trim($m[1]);
        }

        // Fallback to query/body params
        return trim($_GET['api_key'] ?? $_POST['api_key'] ?? '');
    }

    /**
     * Quick check — returns JSON error and exits if auth fails.
     */
    public static function requireAuth(PDO $pdo, string $moduleName): void
    {
        $apiKey = self::extractKey();
        $auth = new self($pdo);
        $result = $auth->verify($apiKey, $moduleName);

        if (!$result['valid']) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $result['error']]);
            exit;
        }
    }
}
