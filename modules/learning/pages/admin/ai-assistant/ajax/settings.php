<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

try {
    $settings = new AIAdminSettings($ctx['pdo'], $ctx['audit']);
    $result = $settings->save($_POST, $ctx['employeeId']);
    aiEndpointJson($result['success'] ? 200 : 422, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'Settings could not be saved.');
}
