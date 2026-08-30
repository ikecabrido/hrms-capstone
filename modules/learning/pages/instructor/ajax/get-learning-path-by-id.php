<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/learningpath.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $learningPath = new LearningPath($pdo);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Learning path ID required']);
        exit;
    }

    $pathData = $learningPath->getById($id);

    if (!$pathData) {
        http_response_code(404);
        echo json_encode(['error' => 'Learning path not found']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $pathData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

