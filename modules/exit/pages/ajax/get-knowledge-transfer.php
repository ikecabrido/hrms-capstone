<?php
header('Content-Type: application/json');
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

require_once dirname(__DIR__, 4) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid plan ID.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
            CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'error' => 'Plan not found.']);
        exit;
    }

    // Fetch transfer items
    $stmt = $pdo->prepare("
        SELECT * FROM exit_knowledge_transfer_items
        WHERE plan_id = :plan_id
        ORDER BY 
            FIELD(priority, 'high', 'medium', 'low'),
            FIELD(status, 'pending', 'in_progress', 'completed'),
            created_at ASC
    ");
    $stmt->execute([':plan_id' => $id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'items' => $items
    ]);
} catch (Throwable $e) {
    error_log('get-knowledge-transfer.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
