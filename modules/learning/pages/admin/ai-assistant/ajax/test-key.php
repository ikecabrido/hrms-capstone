<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    aiEndpointError(422, 'A valid key id is required.');
}

$now = time();
$window = 600;
$limit = 5;
$attempts = $_SESSION['ld_ai_key_test_times'] ?? [];
if (!is_array($attempts)) {
    $attempts = [];
}
$attempts = array_values(array_filter($attempts, static function ($timestamp) use ($now, $window): bool {
    return is_int($timestamp) && $timestamp > ($now - $window);
}));
if (count($attempts) >= $limit) {
    aiEndpointError(429, 'Too many key tests. Try again in a few minutes.');
}
$attempts[] = $now;
$_SESSION['ld_ai_key_test_times'] = $attempts;

try {
    $store = new AIApiKeyStore($ctx['pdo'], null, $ctx['audit']);
    $result = $store->test($id, $ctx['employeeId']);
    aiEndpointJson(200, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'The key test could not be completed.');
}
