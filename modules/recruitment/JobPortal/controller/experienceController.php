<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Experience.php';

function respond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestData()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

try {
    $experience = new Experience();
    $method = $_SERVER['REQUEST_METHOD'];
    $applicationId = (int) ($_GET['application_id'] ?? 0);

    if ($method === 'GET') {
        if ($applicationId < 1) {
            respond(['error' => 'Missing or invalid application_id'], 422);
        }

        respond($experience->findByApplicationId($applicationId));
    }

    if ($method === 'POST') {
        $data = requestData();
        $applicationId = (int) ($data['application_id'] ?? $data['applicationId'] ?? 0);

        if ($applicationId < 1) {
            respond(['error' => 'Valid application_id is required'], 422);
        }

        $id = $experience->create(
            $applicationId,
            trim((string) ($data['company_name'] ?? $data['prevEmployer'] ?? '')),
            trim((string) ($data['position'] ?? $data['prevPosition'] ?? '')),
            $data['start_date'] ?? $data['expStart'] ?? null,
            $data['end_date'] ?? $data['expEnd'] ?? null,
            trim((string) ($data['description'] ?? $data['expDesc'] ?? ''))
        );

        respond(['success' => true, 'id' => (string) $id], 201);
    }

    if ($method === 'DELETE') {
        if ($applicationId < 1) {
            respond(['error' => 'Missing or invalid application_id'], 422);
        }

        $experience->deleteByApplicationId($applicationId);
        respond(['success' => true]);
    }

    respond(['error' => 'Unsupported HTTP method'], 405);
} catch (Throwable $e) {
    respond(['error' => $e->getMessage()], 500);
}
