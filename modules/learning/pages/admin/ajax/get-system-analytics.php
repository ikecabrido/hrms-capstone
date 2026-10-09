<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$s = ['total_tables'=>count($pdo->query('SHOW TABLES LIKE ' . chr(39) . 'ld_%' . chr(39))->fetchAll()),'total_enrollments'=>(int)$pdo->query('SELECT COUNT(*) FROM ld_enrollment')->fetchColumn()];
echo json_encode(['success'=>true,'stats'=>$s]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
