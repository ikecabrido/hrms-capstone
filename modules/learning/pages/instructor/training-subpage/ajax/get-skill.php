<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/skill.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $skill = new Skill($pdo);

    $items = $skill->getList();

    echo json_encode([
        'success' => true,
        'items' => array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'name' => trim((string) $item['name'])
            ];
        }, $items)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load skills.',
        'error' => $e->getMessage()
    ]);
}
