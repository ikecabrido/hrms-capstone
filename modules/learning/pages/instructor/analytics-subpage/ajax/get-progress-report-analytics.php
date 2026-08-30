<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$uid = (int)($_SESSION['employee_id']??0);
$stmt = $pdo->prepare('SELECT c.title, SUM(CASE WHEN p.status=' . ' . 'completed' . ' . ' THEN 1 ELSE 0 END) AS completed, COUNT(p.id) AS total FROM ld_progress p JOIN ld_enrollment e ON e.id=p.enrollment_id JOIN ld_course c ON c.id=e.course_id WHERE c.instructor_id=:uid GROUP BY c.id, c.title');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
