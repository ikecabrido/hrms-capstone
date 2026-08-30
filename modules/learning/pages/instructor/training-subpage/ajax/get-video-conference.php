<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 5) . '/classes/videoconference.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $conference = new VideoConference($pdo);

    $items = $conference->getList();

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
        'message' => 'Failed to load video conferences.',
        'error' => $e->getMessage()
    ]);
}
