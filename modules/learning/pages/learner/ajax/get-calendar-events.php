<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) { http_response_code(401); die(json_encode(['error' => 'Not authenticated'])); }

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("SELECT id, title, description, event_date, event_time, event_type, color FROM ld_user_event WHERE event_date BETWEEN :start AND :end AND created_by = :uid ORDER BY event_date ASC, event_time ASC");
    $stmt->execute([':start' => $start, ':end' => $end, ':uid' => $employeeId]);
    echo json_encode(['success' => true, 'events' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }
