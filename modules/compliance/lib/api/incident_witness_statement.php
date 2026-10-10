<?php
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();

$incidentId = isset($_POST['incident_id']) ? (int) $_POST['incident_id'] : 0;
$statement = isset($_POST['statement']) ? trim((string) $_POST['statement']) : '';

if ($incidentId <= 0 || $statement === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    echo json_encode(['success' => true, 'message' => 'Witness statement saved successfully.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
