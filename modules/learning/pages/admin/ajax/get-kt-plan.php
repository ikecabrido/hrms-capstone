<?php
/**
 * Knowledge transfer plan detail (read only).
 *
 * The Learning module's KT views used to fetch a plan from
 * ../../modules/exit/pages/ajax/get-knowledge-transfer.php, which does not
 * exist — every "View" button 404'd. This endpoint lives inside the Learning
 * module and returns the same { plan, items } shape those views expect, so
 * they can be wired to it without touching the Exit module.
 *
 * GET ?id=<plan id>
 */
header('Content-Type: application/json');

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['employee_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

require_once dirname(__DIR__, 5) . '/database/db.php';

try {
    $pdo = (new Database())->getConnection();
    $planId = (int) ($_GET['id'] ?? 0);

    if ($planId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Plan ID required.']);
        exit;
    }

    $planStmt = $pdo->prepare("
        SELECT p.*,
               CONCAT(e.first_name, ' ', IFNULL(CONCAT(e.middle_name, ' '), ''), e.last_name) AS employee_name,
               CONCAT(s.first_name, ' ', IFNULL(CONCAT(s.middle_name, ' '), ''), s.last_name) AS successor_name
        FROM exit_knowledge_transfer_plans p
        LEFT JOIN em_employees e ON p.employee_id = e.employee_id
        LEFT JOIN em_employees s ON p.successor_id = s.employee_id
        WHERE p.id = :id
        LIMIT 1
    ");
    $planStmt->execute([':id' => $planId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'error' => 'Plan not found.']);
        exit;
    }

    $itemStmt = $pdo->prepare("
        SELECT *
        FROM exit_knowledge_transfer_items
        WHERE plan_id = :pid
        ORDER BY FIELD(priority, 'high', 'medium', 'low'), created_at ASC
    ");
    $itemStmt->execute([':pid' => $planId]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'items' => $items,
    ]);
} catch (Throwable $e) {
    error_log('get-kt-plan error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
