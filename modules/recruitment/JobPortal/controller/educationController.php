<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../classes/Education.php';

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
    $education = new Education();
    $method = $_SERVER['REQUEST_METHOD'];
    $applicationId = (int) ($_GET['application_id'] ?? 0);

    if ($method === 'GET') {
        if ($applicationId < 1) {
            respond(['error' => 'Missing or invalid application_id'], 422);
        }

        respond($education->findByApplicationId($applicationId));
    }

    if ($method === 'POST') {
        $data = requestData();
        $applicationId = (int) ($data['application_id'] ?? $data['applicationId'] ?? 0);
        $school = trim((string) ($data['school_name'] ?? ''));
        $degree = trim((string) ($data['degree'] ?? ''));
        $yearGraduated = (int) ($data['year_graduated'] ?? $data['yearGraduated'] ?? 0);

        if ($applicationId < 1 || $school === '' || $degree === '' || $yearGraduated < 1900) {
            respond(['error' => 'application_id, school_name, degree, and a valid year graduated are required'], 422);
        }

        $id = $education->create($applicationId, $school, $degree, $yearGraduated);
        respond(['success' => true, 'id' => (string) $id], 201);
    }

    if ($method === 'DELETE') {
        if ($applicationId < 1) {
            respond(['error' => 'Missing or invalid application_id'], 422);
        }

        $education->deleteByApplicationId($applicationId);
        respond(['success' => true]);
    }

    respond(['error' => 'Unsupported HTTP method'], 405);
} catch (Throwable $e) {
    respond(['error' => $e->getMessage()], 500);
}
