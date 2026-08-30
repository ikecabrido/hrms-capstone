<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die(json_encode(['error' => 'Method not allowed'])); }
if (session_status() === PHP_SESSION_NONE) session_start();
$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) { http_response_code(401); die(json_encode(['error' => 'Not authenticated'])); }

$eventId = (int)($_POST['event_id'] ?? 0);
if ($eventId <= 0) { http_response_code(400); die(json_encode(['error' => 'Invalid event ID'])); }

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("DELETE FROM ld_user_event WHERE id = :id AND created_by = :uid");
    $stmt->execute([':id' => $eventId, ':uid' => $employeeId]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }
