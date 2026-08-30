<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/program.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $program = new Program($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Program ID required']);
        exit;
    }

    $programData = $program->getById($id);

    if (!$programData) {
        http_response_code(404);
        echo json_encode(['error' => 'Program not found']);
        exit;
    }

    // Get real counts from database
    $enrollmentCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE program_id = {$id}")->fetchColumn();
    $completedCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_enrollment WHERE program_id = {$id} AND status = 'completed'")->fetchColumn();
    $programData['enrollment_count'] = $enrollmentCount;
    $programData['completion_count'] = $completedCount;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $programData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

