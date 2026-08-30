<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';

$emp = new Employee();
$userId = (int) ($emp->getEmployeeId() ?? 0);
if (!$userId) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'Not authenticated']); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'error' => 'Method not allowed']); exit; }

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$eventDate = $_POST['event_date'] ?? '';
$eventTime = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
$eventType = $_POST['event_type'] ?? 'personal';
$color = $_POST['color'] ?? '#320082';

if (!$title || !$eventDate) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Title and date are required']); exit; }

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("INSERT INTO ld_user_event (title, description, event_date, event_time, event_type, color, created_by) VALUES (:title, :desc, :date, :time, :type, :color, :uid)");
    $stmt->execute([':title' => $title, ':desc' => $description, ':date' => $eventDate, ':time' => $eventTime, ':type' => $eventType, ':color' => $color, ':uid' => $userId]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
