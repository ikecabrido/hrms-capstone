<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 5) . "/classes/employee.php";
require_once dirname(__FILE__, 7) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $emp = new Employee();
    $instructorId = $emp->getSessionEmployeeId();
    if (!$instructorId) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $stmt = $pdo->prepare("SELECT s.id, s.name, COUNT(DISTINCT cs.course_id) AS course_count, COUNT(DISTINCT e2.learner_id) AS learner_count, ROUND(AVG(CASE WHEN e2.status = 'completed' THEN 100 ELSE 0 END), 1) AS avg_completion FROM ld_skill s JOIN ld_course_skill cs ON cs.skill_id = s.id JOIN ld_course c ON c.id = cs.course_id LEFT JOIN ld_enrollment e2 ON e2.course_id = c.id WHERE c.instructor_id = :iid GROUP BY s.id, s.name ORDER BY avg_completion ASC");
    $stmt->execute([":iid" => $instructorId]);
    $gaps = $stmt->fetchAll();
    $gapSkills = array_filter($gaps, fn($g) => $g["avg_completion"] < 70);
    $strongSkills = array_filter($gaps, fn($g) => $g["avg_completion"] >= 70);
    echo json_encode(["success" => true, "gaps" => array_values($gapSkills), "strong" => array_values($strongSkills), "all" => $gaps]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }