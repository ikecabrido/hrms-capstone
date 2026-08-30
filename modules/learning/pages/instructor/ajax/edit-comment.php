<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include dirname(__FILE__, 6) . '/database/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid request data']));
    }

    require_once dirname(__FILE__, 4) . '/classes/comment.php';

    $database = new Database();
    $pdo = $database->getConnection();
    $comment = new Comment($pdo);

    $result = $comment->update($data);

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
?>
