<?php
$ctx = require __DIR__ . '/_bootstrap.php';
aiEndpointPostOnly();
CSRF::requireValid();

$action = (string) ($_POST['action'] ?? '');
$catalog = new AIModelCatalog($ctx['pdo'], $ctx['audit']);

try {
    if ($action === 'add') {
        $result = $catalog->addManual(
            (string) ($_POST['model_slug'] ?? ''),
            (string) ($_POST['display_name'] ?? ''),
            (int) ($_POST['context_length'] ?? 0),
            !empty($_POST['enabled']),
            (float) ($_POST['prompt_price_per_million'] ?? 0),
            (float) ($_POST['completion_price_per_million'] ?? 0),
            $ctx['employeeId']
        );
        aiEndpointJson($result['success'] ? 201 : 422, $result);
    }

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        aiEndpointError(422, 'A valid model id is required.');
    }

    $result = match ($action) {
        'enable' => $catalog->setEnabled($id, true, $ctx['employeeId']),
        'disable' => $catalog->setEnabled($id, false, $ctx['employeeId']),
        'default' => $catalog->setDefault($id, $ctx['employeeId']),
        'fallback' => $catalog->setFallback($id, $ctx['employeeId']),
        default => null,
    };

    if ($result === null) {
        aiEndpointError(422, 'Unknown model action.');
    }

    aiEndpointJson($result['success'] ? 200 : 422, $result);
} catch (Throwable $e) {
    aiEndpointError(500, 'The model action could not be completed.');
}
