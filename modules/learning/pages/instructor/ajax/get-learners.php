<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 5) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->query("SELECT employee_id, CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) AS full_name, email FROM em_employees ORDER BY first_name ASC");
    $learners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'learners' => $learners]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
