<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/module.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $module = new Module($pdo);

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
    $items = $module->getList($courseId);

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'name' => trim((string) $item['title']),
                'course_id' => (int) $item['course_id']
            ];
        }, $items)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load modules.',
        'error' => $e->getMessage()
    ]);
}
