<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__FILE__, 6) . '/database/db.php';
try {

$stmt = $pdo->query('SELECT e.employee_id AS id, CONCAT(e.first_name,chr(32),e.last_name) AS name, COUNT(DISTINCT c.id) AS course_count, COUNT(DISTINCT en.id) AS enrollment_count FROM em_employees e LEFT JOIN ld_course c ON c.instructor_id=e.employee_id LEFT JOIN ld_enrollment en ON en.course_id=c.id WHERE e.employee_id IN (SELECT DISTINCT instructor_id FROM ld_course) GROUP BY e.employee_id, name ORDER BY course_count DESC');
echo json_encode(['success'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) { echo json_encode(['success'=>false,'message'=>$e->getMessage()]); }
