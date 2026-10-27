<?php
/**
 * Shared bootstrap for AI AJAX endpoints.
 *
 * Admin access is the default. A single endpoint may explicitly opt into
 * instructor access before including this file. Returns [pdo, employeeId,
 * audit]. State-changing endpoints must call CSRF::requireValid().
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
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    if ($json === false) {
        error_log('Learning AI endpoint could not encode its JSON response: ' . json_last_error_msg());
        http_response_code(500);
        $json = '{"success":false,"error":"The server could not encode the assistant response."}';
    }
    echo $json;
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
    $role = LearningRole::forEmployee($pdo, $employeeId);
    $instructorChatAllowed = defined('AI_ENDPOINT_ALLOW_INSTRUCTOR')
        && AI_ENDPOINT_ALLOW_INSTRUCTOR
        && $role === 'instructor';
    if ($role !== 'admin' && !$instructorChatAllowed) {
        aiEndpointError(403, 'Admin or trainer access is required.');
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
