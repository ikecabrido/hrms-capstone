<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 4) . "/classes/employee.php";
require_once dirname(__FILE__, 6) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $emp = new Employee();
    $instructorId = $emp->getSessionEmployeeId();
    if (!$instructorId) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $q = trim($_GET["q"] ?? "");
    if (strlen($q) < 2) { echo json_encode(["success" => true, "results" => []]); exit; }
    $pattern = "%$q%";
    $results = [];
    $stmt = $pdo->prepare("SELECT id, title, category FROM ld_course WHERE instructor_id = :iid AND (title LIKE :q OR category LIKE :q) LIMIT 10");
    $stmt->execute([":iid" => $instructorId, ":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "course"; $results[] = $r; }
    $stmt = $pdo->prepare("SELECT e2.employee_id AS id, e2.first_name, e2.last_name, e2.email FROM em_employees e2 JOIN ld_enrollment en ON en.learner_id = e2.employee_id JOIN ld_course c ON c.id = en.course_id WHERE c.instructor_id = :iid AND (e2.first_name LIKE :q OR e2.last_name LIKE :q) LIMIT 10");
    $stmt->execute([":iid" => $instructorId, ":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "learner"; $r["name"] = $r["first_name"] . " " . $r["last_name"]; $results[] = $r; }
    echo json_encode(["success" => true, "results" => $results]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }