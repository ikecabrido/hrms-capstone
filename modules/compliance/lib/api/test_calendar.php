<?php
require_once __DIR__ . '/../../../../auth/session.php';
require_once __DIR__ . '/../../../../database/db.php';
header('Content-Type: application/json');

$db = (new Database())->getConnection();
if (!$db instanceof PDO) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection unavailable',
    ]);
    exit;
}

$start = '2027-01-01';
$end = '2027-01-31';

$stmt = $db->prepare('SELECT id, title, description, date AS start_time, date AS end_time, 1 AS all_day, location, event_type, status, priority, color FROM lc_calendar WHERE date >= :start AND date <= :end ORDER BY date ASC');
$stmt->execute([':start' => $start, ':end' => $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $rows,
    'count' => count($rows),
]);
