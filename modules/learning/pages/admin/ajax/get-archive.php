<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$type = $_GET['type'] ?? 'all';
$tables = ['course'=>['ld_course','title'],'module'=>['ld_module','title'],'lesson'=>['ld_lesson','title'],'quiz'=>['ld_quiz','title'],'program'=>['ld_program','title'],'skill'=>['ld_skill','name']];
$items = [];
foreach (($type==='all' ? array_keys($tables) : [$type]) as $t) {
    if (!isset($tables[$t])) continue;
    $stmt = $pdo->prepare('SELECT id, ' . $tables[$t][1] . ' AS title, updated_at AS archived_at FROM ' . $tables[$t][0] . ' WHERE status=' . chr(39) . 'archived' . chr(39) . ' ORDER BY updated_at DESC LIMIT 50');
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $r['type']=$t; $items[]=$r; }
}
echo json_encode(['success'=>true,'items'=>$items]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
