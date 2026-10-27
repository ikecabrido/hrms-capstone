<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

require_once dirname(__DIR__, 4) . '/classes/AICourseContext.php';

$query = trim((string) ($_POST['q'] ?? ''));

try {
    $result = (new AICourseContext($ctx['pdo']))->searchTrainees($query, 10);
    aiEndpointJson(200, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'Trainee search could not be completed.');
}
