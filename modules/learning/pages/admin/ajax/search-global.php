<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 6) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $q = trim($_GET["q"] ?? "");
    if (strlen($q) < 2) { echo json_encode(["success" => true, "results" => []]); exit; }
    $pattern = "%$q%";
    $results = [];
    $stmt = $pdo->prepare("SELECT id, title, category, status FROM ld_course WHERE title LIKE :q OR category LIKE :q LIMIT 5");
    $stmt->execute([":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "course"; $results[] = $r; }
    $stmt = $pdo->prepare("SELECT employee_id AS id, first_name, last_name, email FROM em_employees WHERE first_name LIKE :q OR last_name LIKE :q OR email LIKE :q LIMIT 5");
    $stmt->execute([":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "user"; $r["name"] = $r["first_name"] . " " . $r["last_name"]; $results[] = $r; }
    $stmt = $pdo->prepare("SELECT id, title FROM ld_program WHERE title LIKE :q LIMIT 5");
    $stmt->execute([":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "program"; $results[] = $r; }
    $stmt = $pdo->prepare("SELECT id, name FROM ld_skill WHERE name LIKE :q LIMIT 5");
    $stmt->execute([":q" => $pattern]);
    foreach ($stmt->fetchAll() as $r) { $r["type"] = "skill"; $results[] = $r; }
    echo json_encode(["success" => true, "results" => $results]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }