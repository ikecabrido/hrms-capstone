<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

$incidentId = isset($_GET['incident_id']) ? (int) $_GET['incident_id'] : 0;
if ($incidentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident ID.', 'evidence' => []]);
    exit;
}

try {
    $evidence = [];

    echo json_encode(['success' => true, 'evidence' => $evidence]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'evidence' => []]);
}
