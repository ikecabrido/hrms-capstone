<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$m = (int)($_GET['month']??date('n')); $y = (int)($_GET['year']??date('Y'));
$stmt = $pdo->prepare('SELECT * FROM ld_calendar_event WHERE MONTH(event_date)=:m AND YEAR(event_date)=:y ORDER BY event_date ASC');
$stmt->execute(['m'=>$m,'y'=>$y]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
