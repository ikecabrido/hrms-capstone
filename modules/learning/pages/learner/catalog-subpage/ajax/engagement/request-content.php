<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 8) . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }

    $requestedTitle = isset($_POST['requested_title']) ? trim((string) $_POST['requested_title']) : '';
    $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';

    if ($requestedTitle === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Course title is required.']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("INSERT INTO ld_request (learner_id, requested_title, description, status, created_at) VALUES (:lid, :title, :desc, 'pending', NOW())");
    $stmt->execute([
        ':lid'   => $learnerId,
        ':title' => $requestedTitle,
        ':desc'  => $description,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Request submitted successfully.',
        'request_id' => (int) $pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit request.',
        'error' => $e->getMessage(),
    ]);
}
exit;
