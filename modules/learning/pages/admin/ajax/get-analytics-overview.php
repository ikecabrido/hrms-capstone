<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$s = [];
$s['enrollments'] = (int)$pdo->query('SELECT COUNT(*) FROM ld_enrollment')->fetchColumn();
$s['courses'] = (int)$pdo->query('SELECT COUNT(*) FROM ld_course')->fetchColumn();
$s['active_courses'] = (int)$pdo->query('SELECT COUNT(*) FROM ld_course WHERE status=' . chr(39) . 'active' . chr(39))->fetchColumn();
$s['certificates'] = (int)$pdo->query('SELECT COUNT(*) FROM ld_certificate')->fetchColumn();
echo json_encode(['success'=>true,'stats'=>$s]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
