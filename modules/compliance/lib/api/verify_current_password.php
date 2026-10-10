<?php
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

$employeeId = $_SESSION['employee_id'] ?? null;

if (!$employeeId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$password = (string) ($_POST['password'] ?? '');

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

if (!($db instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT password FROM user_account WHERE employee_id = :id LIMIT 1");
    $stmt->execute([':id' => $employeeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['password']) && password_verify($password, $row['password'])) {
        echo json_encode(['success' => true, 'message' => 'Password verified']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Verification failed']);
}
