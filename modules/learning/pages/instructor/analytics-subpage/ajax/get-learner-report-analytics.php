<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 7) . '/database/db.php';
try {

$uid = (int)($_SESSION['employee_id']??0);
$stmt = $pdo->prepare('SELECT e.learner_id, CONCAT(em.first_name,chr(32),em.last_name) AS name, COUNT(DISTINCT e.course_id) AS courses, AVG(g.final_score) AS avg_score FROM ld_enrollment e JOIN ld_course c ON c.id=e.course_id JOIN em_employees em ON em.employee_id=e.learner_id LEFT JOIN ld_grade g ON g.learner_id=e.learner_id AND g.course_id=e.course_id WHERE c.instructor_id=:uid GROUP BY e.learner_id, name ORDER BY courses DESC LIMIT 50');
$stmt->execute(['uid'=>$uid]);
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
