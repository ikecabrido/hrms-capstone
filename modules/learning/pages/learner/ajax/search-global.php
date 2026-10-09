<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 6) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $learnerId = isset($_SESSION["employee_id"]) ? (int)$_SESSION["employee_id"] : 0;
    if ($learnerId <= 0) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $q = trim($_GET["q"] ?? "");
    if (strlen($q) < 2) { echo json_encode(["success" => true, "results" => []]); exit; }
    $pattern = "%$q%";
    $results = [];
    $stmt = $pdo->prepare("SELECT c.id, c.title, c.category FROM ld_course c JOIN ld_enrollment en ON en.course_id = c.id WHERE en.learner_id = :lid AND c.title LIKE :q LIMIT 5");
    $stmt->execute([":lid" => $learnerId, ":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "course"; $results[] = $r; }
    $stmt = $pdo->prepare("SELECT id, title, category FROM ld_course WHERE status = 'active' AND (title LIKE :q OR category LIKE :q) LIMIT 5");
    $stmt->execute([":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "catalog"; $results[] = $r; }
    echo json_encode(["success" => true, "results" => $results]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }