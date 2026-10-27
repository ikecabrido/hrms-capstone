<?php
$ctx = require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    aiEndpointError(405, 'Method not allowed.');
}

try {
    $audit = new AIAudit($ctx['pdo']);
    aiEndpointJson(200, [
        'success' => true,
        'entries' => $audit->recent(25),
    ]);
} catch (Throwable $e) {
    aiEndpointError(500, 'The audit log could not be loaded.');
}
