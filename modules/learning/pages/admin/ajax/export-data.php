<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$type = $_GET['type'] ?? 'courses';
$map = ['courses'=>'ld_course','enrollments'=>'ld_enrollment','grades'=>'ld_grade'];
$tbl = $map[$type] ?? 'ld_course';
$data = $pdo->query('SELECT * FROM ' . $tbl . ' LIMIT 1000')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success'=>true,'data'=>$data,'count'=>count($data)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
