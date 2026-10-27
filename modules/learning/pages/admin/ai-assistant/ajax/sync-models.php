<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

try {
    $catalog = new AIModelCatalog($ctx['pdo'], $ctx['audit']);
    $result = $catalog->sync($ctx['employeeId']);
    aiEndpointJson($result['success'] ? 200 : 502, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'The model catalog could not be synced.');
}
