<?php
include_once dirname(__DIR__, 3) . '/classes/Employee.php';
require_once dirname(__DIR__, 5) . '/database/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die(json_encode(['error' => 'Method not allowed'])); }
if (session_status() === PHP_SESSION_NONE) session_start();
$employeeId = $_SESSION['employee_id'] ?? null;
if (!$employeeId) { http_response_code(401); die(json_encode(['error' => 'Not authenticated'])); }

$eventId = (int)($_POST['event_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$eventDate = $_POST['event_date'] ?? '';
$eventTime = !empty($_POST['event_time']) ? $_POST['event_time'] : null;
$eventType = $_POST['event_type'] ?? 'personal';
$color = $_POST['color'] ?? '#320082';

if ($eventId <= 0 || $title === '' || $eventDate === '') { http_response_code(400); die(json_encode(['error' => 'Invalid data'])); }

try {
    $pdo = (new Database())->getConnection();
    $stmt = $pdo->prepare("UPDATE ld_user_event SET title=:title, description=:desc, event_date=:date, event_time=:time, event_type=:type, color=:color, updated_at=NOW() WHERE id=:id AND created_by=:uid");
    $stmt->execute([':title'=>$title, ':desc'=>$description, ':date'=>$eventDate, ':time'=>$eventTime, ':type'=>$eventType, ':color'=>$color, ':id'=>$eventId, ':uid'=>$employeeId]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) { http_response_code(500); echo json_encode(['error' => $e->getMessage()]); }
