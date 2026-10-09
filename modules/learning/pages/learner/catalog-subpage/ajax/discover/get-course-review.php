<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 8) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $courseId = (int)($_GET["course_id"] ?? 0);
    if ($courseId <= 0) { http_response_code(400); echo json_encode(["error" => "Missing course_id"]); exit; }
    $stmt = $pdo->prepare("SELECT AVG(qa.score) AS avg_score, COUNT(*) AS total_reviews, SUM(CASE WHEN qa.score >= 80 THEN 1 ELSE 0 END) AS positive, SUM(CASE WHEN qa.score < 50 THEN 1 ELSE 0 END) AS negative FROM ld_quiz_attempt qa JOIN ld_quiz q ON q.id = qa.quiz_id JOIN ld_module m ON m.id = q.module_id WHERE m.course_id = :cid");
    $stmt->execute([":cid" => $courseId]);
    $stats = $stmt->fetch();
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM ld_enrollment WHERE course_id = :cid GROUP BY status");
    $stmt->execute([":cid" => $courseId]);
    $enrollmentStats = $stmt->fetchAll();
    echo json_encode(["success" => true, "stats" => $stats, "enrollment_stats" => $enrollmentStats, "rating" => round(($stats["avg_score"] ?? 0) / 20, 1), "review_count" => $stats["total_reviews"] ?? 0]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }