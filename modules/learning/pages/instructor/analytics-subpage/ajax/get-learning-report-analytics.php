<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$uid = (int)($_SESSION['employee_id']??0);
$stmt = $pdo->prepare('SELECT c.title, COUNT(e.id) AS enrollments, AVG(g.final_score) AS avg_score FROM ld_course c LEFT JOIN ld_enrollment e ON e.course_id=c.id LEFT JOIN ld_grade g ON g.learner_id=e.learner_id AND g.course_id=c.id WHERE c.instructor_id=:uid GROUP BY c.id, c.title ORDER BY enrollments DESC');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
