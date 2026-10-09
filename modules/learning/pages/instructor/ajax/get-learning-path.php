<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 4) . '/classes/learningpath.php';
require_once dirname(__FILE__, 6) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $learningPath = new LearningPath($pdo);

    $items = $learningPath->getList();

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'name' => trim((string) $item['title'])
            ];
        }, $items)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load learning paths.',
        'error' => $e->getMessage()
    ]);
}
