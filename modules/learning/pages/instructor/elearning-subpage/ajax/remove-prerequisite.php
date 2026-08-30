<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once dirname(__DIR__, 6) . '/database/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$prereqId = (int) ($input['id'] ?? $_POST['id'] ?? 0);

if ($prereqId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Prerequisite id is required']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("DELETE FROM ld_prerequisite WHERE id = :id");
    $stmt->execute([':id' => $prereqId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Prerequisite not found']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
