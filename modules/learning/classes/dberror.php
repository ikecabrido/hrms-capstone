<?php
/**
 * DbError — central handler for database failures.
 *
 * The learning pages historically caught query errors and fell back to an empty
 * array or a null record, so a broken query looked exactly like "this learner has
 * no data yet" — a blank list or a zero count with no hint that anything failed.
 *
 * Pages now call DbError::capture() from their catch block instead. The error is
 * written to the PHP error log and recorded, and Page::render() prepends a visible
 * banner to the page output, so a failed query surfaces as an error rather than as
 * silently missing data.
 *
 * JSON endpoints (AJAX, which never pass through Page::render()) should call
 * DbError::json() instead, which reports the failure in the response body.
 */
class DbError
{
    /** @var array<int, array{context: string, message: string, file: string, line: int}> */
    private static array $errors = [];

    /**
     * Record and log a caught database error.
     */
    public static function capture(Throwable $e, string $context = ''): void
    {
        self::$errors[] = [
            'context' => $context,
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ];

        error_log(
            '[learning] database error'
            . ($context !== '' ? ' (' . $context . ')' : '')
            . ': ' . $e->getMessage()
            . ' in ' . $e->getFile() . ':' . $e->getLine()
        );
    }

    public static function hasErrors(): bool
    {
        return self::$errors !== [];
    }

    public static function getErrors(): array
    {
        return self::$errors;
    }

    public static function clear(): void
    {
        self::$errors = [];
    }

    /**
     * Visible notice for page output. Rendered by Page::render() when a page
     * captured at least one error while building itself.
     */
    public static function renderBanner(): string
    {
        if (!self::hasErrors()) {
            return '';
        }

        $count = count(self::$errors);
        $rows = '';
        foreach (self::$errors as $error) {
            $where = basename($error['file']) . ':' . $error['line'];
            $label = $error['context'] !== '' ? $error['context'] . ' — ' : '';
            $rows .= '<li style="margin:0.25rem 0;">'
                . '<strong>' . htmlspecialchars($label) . '</strong>'
                . '<span>' . htmlspecialchars($error['message']) . '</span> '
                . '<em style="color:var(--muted,#6b7280);font-style:normal;">'
                . htmlspecialchars($where) . '</em>'
                . '</li>';
        }

        return '<div class="ld-db-error" role="alert" style="'
            . 'margin:0 0 1.25rem;padding:1rem 1.25rem;border:1px solid rgba(220,53,69,0.35);'
            . 'border-left:4px solid var(--danger,#dc3545);border-radius:12px;'
            . 'background:rgba(220,53,69,0.05);color:var(--text,#222);font-size:0.85rem;">'
            . '<div style="font-weight:700;color:var(--danger,#dc3545);margin-bottom:0.5rem;">'
            . '<i class="fas fa-triangle-exclamation" style="margin-right:0.4rem;"></i>'
            . 'Database error' . ($count > 1 ? 's' : '') . ' on this page — the data below is incomplete.'
            . '</div>'
            . '<ul style="margin:0;padding-left:1.1rem;">' . $rows . '</ul>'
            . '</div>';
    }

    /**
     * Report a failure from a JSON endpoint and end the request.
     */
    public static function json(Throwable $e, string $context = ''): void
    {
        self::capture($e, $context);

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'message' => 'Database error.',
            'context' => $context,
            'error'   => $e->getMessage(),
        ]);
    }
}
