<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$emp = new Employee();
$userId = (int) ($emp->getEmployeeId() ?? 0);
if (!$userId) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'error' => 'Method not allowed']); exit; }

$eventId = (int) ($_POST['event_id'] ?? 0);
if (!$eventId) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Missing event_id']); exit; }

try {
    $pdo = (new Database())->getConnection();

    $stmt = $pdo->prepare("DELETE FROM ld_user_event WHERE id = :eid AND created_by = :uid");
    $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
    if ($stmt->rowCount() === 0) { http_response_code(403); echo json_encode(['success' => false, 'error' => 'Not your event or already deleted']); exit; }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}