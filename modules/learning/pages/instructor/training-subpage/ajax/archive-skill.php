<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 5) . '/classes/skill.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $skill = new Skill($pdo);

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $result = $skill->archive((int) $data['id']);

    if (!empty($result['success'])) {
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    http_response_code(400);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
