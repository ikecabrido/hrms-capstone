<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

require_once dirname(__FILE__, 5) . '/classes/course.php';
require_once dirname(__FILE__, 7) . '/database/db.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $course = new Course($pdo);

    // Accept JSON body or form-encoded POST data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data || !is_array($data)) {
        $data = $_POST;
    }

    // Handle skill_ids from FormData (sent as array)
    if (isset($_POST['skill_ids']) && is_array($_POST['skill_ids'])) {
        $data['skill_ids'] = array_map('intval', $_POST['skill_ids']);
    }
    if (!isset($data['skill_ids']) && isset($_POST['skill_ids[]'])) {
        $data['skill_ids'] = array_map('intval', $_POST['skill_ids[]']);
    }

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    $result = $course->update($data);

    if (!empty($result['success'])) {
        http_response_code(200);
        echo json_encode($result);
        exit;
    }

    $statusCode = 401;
    if (strpos($result['message'] ?? '', 'required') !== false) {
        $statusCode = 400;
    }

    http_response_code($statusCode);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
