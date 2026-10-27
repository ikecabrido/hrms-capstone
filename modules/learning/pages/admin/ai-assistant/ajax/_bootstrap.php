<?php
/**
 * Shared bootstrap for AI admin AJAX endpoints.
 *
 * Returns [pdo, employeeId, audit]. Every state-changing endpoint must also
 * call CSRF::requireValid() before reading or writing input.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * @param array<string, mixed> $payload
 */
function aiEndpointJson(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function aiEndpointError(int $status, string $message, array $extra = []): never
{
    aiEndpointJson($status, array_merge(['success' => false, 'error' => $message], $extra));
}

function aiEndpointPostOnly(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        aiEndpointError(405, 'Method not allowed.');
    }
}

if (empty($_SESSION['employee_id'])) {
    aiEndpointError(401, 'Unauthorized.');
}

require_once dirname(__DIR__, 6) . '/database/db.php';
require_once dirname(__DIR__, 4) . '/classes/LearningRole.php';
require_once dirname(__DIR__, 4) . '/classes/CSRF.php';
require_once dirname(__DIR__, 4) . '/classes/AIConfig.php';
require_once dirname(__DIR__, 4) . '/classes/AISecretBox.php';
require_once dirname(__DIR__, 4) . '/classes/AIAudit.php';
require_once dirname(__DIR__, 4) . '/classes/AIAdminSettings.php';
require_once dirname(__DIR__, 4) . '/classes/AIApiKeyStore.php';
require_once dirname(__DIR__, 4) . '/classes/AIModelCatalog.php';
require_once dirname(__DIR__, 4) . '/classes/OpenRouterClient.php';

try {
    $pdo = (new Database())->getConnection();
    $employeeId = (int) $_SESSION['employee_id'];
    if (LearningRole::forEmployee($pdo, $employeeId) !== 'admin') {
        aiEndpointError(403, 'Admin access is required.');
    }

    $schema = AIConfig::schemaStatus($pdo);
    if (!$schema['ready']) {
        aiEndpointError(503, 'The AI database schema is incomplete.', ['missing' => $schema['missing']]);
    }
} catch (Throwable $e) {
    aiEndpointError(503, 'The AI assistant is unavailable.');
}

return [
    'pdo' => $pdo,
    'employeeId' => $employeeId,
    'audit' => new AIAudit($pdo),
];
