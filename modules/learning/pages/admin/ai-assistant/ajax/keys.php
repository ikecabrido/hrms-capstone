<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

$action = (string) ($_POST['action'] ?? '');
$store = new AIApiKeyStore($ctx['pdo'], null, $ctx['audit']);

try {
    if ($action === 'create') {
        $result = $store->create(
            (string) ($_POST['api_key'] ?? ''),
            (string) ($_POST['label'] ?? 'OpenRouter key'),
            $ctx['employeeId']
        );
        aiEndpointJson($result['success'] ? 201 : 422, $result);
    }

    if (in_array($action, ['activate', 'disable', 'revoke'], true)) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            aiEndpointError(422, 'A valid key id is required.');
        }

        $result = match ($action) {
            'activate' => $store->activate($id, $ctx['employeeId']),
            'disable' => $store->disable($id, $ctx['employeeId']),
            'revoke' => $store->revoke($id, $ctx['employeeId']),
        };
        aiEndpointJson($result['success'] ? 200 : 422, $result);
    }

    aiEndpointError(422, 'Unknown key action.');
} catch (Throwable $e) {
    aiEndpointError(500, 'The key action could not be completed.');
}
