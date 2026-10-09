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

    $userId = (int)$data['id'];
    $firstName = $data['first_name'] ?? null;
    $lastName = $data['last_name'] ?? null;
    $email = $data['email'] ?? null;
    $role = $data['role'] ?? 'learner';
    $status = $data['status'] ?? 'active';
    $department = $data['department'] ?? null;

    if (!$firstName || !$email) {
        http_response_code(400);
        die(json_encode(['error' => 'First name and email are required']));
    }

    $stmt = $pdo->prepare("
        UPDATE ld_user 
        SET first_name = ?, last_name = ?, email = ?, role = ?, 
            status = ?, department = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $role,
        $status,
        $department,
        $userId
    ]);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'id' => $userId,
        'message' => 'User updated successfully'
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
