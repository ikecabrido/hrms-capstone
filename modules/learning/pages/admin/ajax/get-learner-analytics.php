<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$stmt = $pdo->query('SELECT en.learner_id AS id, CONCAT(e.first_name,chr(32),e.last_name) AS name, COUNT(DISTINCT en.course_id) AS enrolled, SUM(CASE WHEN en.status=' . chr(39) . 'completed' . chr(39) . ' THEN 1 ELSE 0 END) AS completed, AVG(g.final_score) AS avg_score FROM ld_enrollment en JOIN em_employees e ON e.employee_id=en.learner_id LEFT JOIN ld_grade g ON g.learner_id=en.learner_id AND g.course_id=en.course_id GROUP BY en.learner_id, name ORDER BY enrolled DESC LIMIT 50');
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
