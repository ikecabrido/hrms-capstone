<?php
session_start();
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$emp = new Employee();
$userId = (int) ($emp->getEmployeeId() ?? 0);
if (!$userId) { http_response_code(401); echo json_encode(['error' => 'Not authenticated']); exit; }

$pdo = (new Database())->getConnection();
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

$stmt = $pdo->prepare("SELECT * FROM ld_user_event WHERE created_by = :uid AND event_date BETWEEN :start AND :end ORDER BY event_date ASC, event_time ASC");
$stmt->execute([':uid' => $userId, ':start' => $start, ':end' => $end]);
echo json_encode(['events' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
