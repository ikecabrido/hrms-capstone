<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/app/models/Attendance.php';
require_once __DIR__ . '/app/core/TimeDatabase.php';

$date = $_GET['date'] ?? date('Y-m-d');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, intval($_GET['limit'] ?? 50));
$offset = ($page - 1) * $limit;

$attendance = new Attendance();
$records = $attendance->getByDateRange($date, $date, null, $limit, $offset);

// get total count
$db = TimeDatabase::getInstance()->getConnection();
$countStmt = $db->prepare("SELECT COUNT(*) as cnt FROM ta_attendance WHERE attendance_date = :date");
$countStmt->execute([':date' => $date]);
$cntRow = $countStmt->fetch(PDO::FETCH_ASSOC);
$total = $cntRow ? intval($cntRow['cnt']) : 0;

echo json_encode(['records' => $records, 'total' => $total]);

exit;
