<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/evaluation.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $evaluation = new Evaluation($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Evaluation ID required']);
        exit;
    }

    $evaluationData = $evaluation->getById($id);

    if (!$evaluationData) {
        http_response_code(404);
        echo json_encode(['error' => 'Evaluation not found']);
        exit;
    }

    // Get real counts from database
    $attemptCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_grade WHERE evaluation_id = {$id}")->fetchColumn();
    $passCount = (int) $pdo->query("SELECT COUNT(*) FROM ld_grade WHERE evaluation_id = {$id} AND status = 'passed'")->fetchColumn();
    $evaluationData['attempt_count'] = $attemptCount;
    $evaluationData['pass_count'] = $passCount;

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $evaluationData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

