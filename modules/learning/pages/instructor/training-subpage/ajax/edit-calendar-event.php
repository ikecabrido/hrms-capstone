<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id=(int)($input['id']??0);
$pdo->prepare('UPDATE ld_calendar_event SET title=:t,description=:d,event_date=:ed,event_type=:et WHERE id=:id')->execute(['t'=>$input['title']??'','d'=>$input['description']??'','ed'=>$input['event_date']??'','et'=>$input['event_type']??'event','id'=>$id]);
echo json_encode(['success'=>true]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
