<?php
/* JSON-GATEKEEPER-V2-20260823 - Shared prepend for clinic *-data.php endpoints */

while (@ob_end_clean()) {}
@ob_start();
@header('X-JSON-Gatekeeper: v2');

@error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');
@ini_set('html_errors', '0');
@ini_set('log_errors', '1');
@ini_set('ignore_repeated_errors', '1');

define('JSON_GATEKEEPER_ACTIVE', true);

function jsonGatekeeperEmit(array $payload, int $status = 200): never {
    while (@ob_get_level()) @ob_end_clean();
    @http_response_code($status);
    @header('Content-Type: application/json; charset=utf-8');
    @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $envelope = $payload + ['success' => $payload['success'] ?? ($status < 400)];
    $json = @json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || $json === '') {
        $safe = ['success' => $envelope['success'] ?? ($status < 400), 'message' => 'Unable to encode response.'];
        $json = json_encode($safe, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    echo $json;
    exit;
}

function jsonGatekeeperDetectTaint(string $buf): bool {
    if ($buf === '') return false;
    $trimmed = ltrim($buf);
    if ($trimmed !== '' && $trimmed[0] === '<') return true;
    return (bool)preg_match('/<(br|b|div|span|table|p|font|hr|ul|ol|li|h[1-6])(\s|>|\/)/i', $buf);
}

@register_shutdown_function(function (): void {
    $buf = (string)@ob_get_contents();
    $isTainted = jsonGatekeeperDetectTaint($buf);
    $err = @error_get_last();
    $isFatal = $err && in_array($err['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_RECOVERABLE_ERROR,E_COMPILE_WARNING,E_CORE_WARNING,E_USER_ERROR], true);
    if ($isTainted || $isFatal) {
        $endpoint = $GLOBALS['_json_gatekeeper_endpoint'] ?? 'unknown-endpoint.php';
        $msg = 'Unable to process request.';
        if ($isFatal) {
            $clean = @preg_replace('/<[^>]+>/', ' ', (string)($err['message'] ?? 'Fatal PHP error'));
            $clean = @preg_replace('/\s+/', ' ', trim((string)$clean));
            @error_log('[json-gatekeeper][' . $endpoint . '] FATAL in ' . ($err['file'] ?? '?') . ':' . ($err['line'] ?? '?') . ' :: ' . $clean);
        } elseif ($buf !== '') {
            $stripped = @preg_replace('/<[^>]+>/', ' ', $buf);
            @error_log('[json-gatekeeper][' . $endpoint . '] TAINTED OUTPUT (len=' . strlen($buf) . '): ' . substr($stripped, 0, 300));
        }
        jsonGatekeeperEmit(['success' => false, 'message' => $msg], 500);
    }
});

@set_error_handler(function (): bool { return true; });

@set_exception_handler(function (Throwable $e): void {
    $endpoint = $GLOBALS['_json_gatekeeper_endpoint'] ?? 'unknown-endpoint.php';
    @error_log('[json-gatekeeper][' . $endpoint . '] UNCAUGHT: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    jsonGatekeeperEmit(['success' => false, 'message' => 'Unable to process request.'], 500);
});
