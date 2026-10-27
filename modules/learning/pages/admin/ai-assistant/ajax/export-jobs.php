<?php
/**
 * Admin-only CSV export for AI generation jobs.
 *
 * This is a read-only endpoint. It requires an authenticated admin session and
 * a ready AI schema, but no CSRF token because it does not change state. The
 * export contains job metadata and usage only; it never contains provider keys,
 * prompts, trainee context, or full provider responses.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['employee_id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

require_once dirname(__DIR__, 6) . '/database/db.php';
require_once dirname(__DIR__, 4) . '/classes/LearningRole.php';
require_once dirname(__DIR__, 4) . '/classes/AIConfig.php';
require_once dirname(__DIR__, 4) . '/classes/AIAnalytics.php';

try {
    $pdo = (new Database())->getConnection();
    if (LearningRole::forEmployee($pdo, (int) $_SESSION['employee_id']) !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    $schema = AIConfig::schemaStatus($pdo);
    if (!$schema['ready']) {
        http_response_code(503);
        echo 'The AI database schema is incomplete.';
        exit;
    }

    $range = isset($_GET['range']) ? (int) $_GET['range'] : 30;
    if (!in_array($range, [7, 30, 90], true)) {
        $range = 30;
    }
    $rows = (new AIAnalytics($pdo))->exportRows($range);
} catch (Throwable $e) {
    http_response_code(503);
    echo 'The export is unavailable.';
    exit;
}

/**
 * Prevent spreadsheet formula injection while keeping ordinary text readable.
 */
function aiCsvCell(mixed $value): string
{
    $text = str_replace(["\0", "\r\n", "\r"], ['', "\n", "\n"], (string) $value);
    $trimmed = ltrim($text);
    if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t"], true)) {
        return "'" . $text;
    }
    return $text;
}

$statusLabels = [
    'draft' => 'Draft',
    'generating' => 'Generating',
    'ready' => 'Ready for review',
    'applied' => 'Applied',
    'failed' => 'Failed',
    'cancelled' => 'Cancelled',
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ai-jobs-' . date('Ymd-His') . '.csv"');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'wb');
if ($out === false) {
    exit;
}

// UTF-8 BOM keeps Excel from mis-decoding non-ASCII names.
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, [
    'Job ID',
    'Status',
    'Model',
    'Trainee',
    'Course owner',
    'Prompt tokens',
    'Completion tokens',
    'Estimated cost USD',
    'Created at',
    'Applied at',
    'Safe error',
]);

foreach ($rows as $row) {
    $status = (string) ($row['status'] ?? '');
    fputcsv($out, [
        aiCsvCell((int) ($row['id'] ?? 0)),
        aiCsvCell($statusLabels[$status] ?? $status),
        aiCsvCell((string) ($row['model'] ?? '')),
        aiCsvCell((string) ($row['learner_name'] ?? '')),
        aiCsvCell((string) ($row['owner_name'] ?? '')),
        aiCsvCell((int) ($row['prompt_tokens'] ?? 0)),
        aiCsvCell((int) ($row['completion_tokens'] ?? 0)),
        aiCsvCell(number_format((float) ($row['estimated_cost_usd'] ?? 0), 6, '.', '')),
        aiCsvCell((string) ($row['created_at'] ?? '')),
        aiCsvCell((string) ($row['applied_at'] ?? '')),
        aiCsvCell((string) ($row['error_message'] ?? '')),
    ]);
}

fclose($out);
exit;
