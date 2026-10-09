<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$uid = (int)($_SESSION['employee_id']??0);
$stmt = $pdo->prepare('SELECT COUNT(DISTINCT c.id) AS courses, COUNT(DISTINCT e.id) AS enrollments FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id=c.id WHERE c.instructor_id=:uid');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'stats'=>$stmt->fetch(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
