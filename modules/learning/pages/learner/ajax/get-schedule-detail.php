<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 6) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $learnerId = isset($_SESSION["employee_id"]) ? (int)$_SESSION["employee_id"] : 0;
    if ($learnerId <= 0) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $date = $_GET["date"] ?? date("Y-m-d");
    $results = [];
    $stmt = $pdo->prepare("SELECT ce.* FROM ld_calendar_event ce WHERE (ce.user_id = :uid OR ce.user_id IS NULL) AND DATE(ce.event_date) = :date ORDER BY ce.event_date ASC");
    $stmt->execute([":uid" => $learnerId, ":date" => $date]);
    $results["events"] = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT vc.*, c.title AS course_title FROM ld_video_conference vc JOIN ld_course c ON c.id = vc.course_id JOIN ld_enrollment e ON e.course_id = vc.course_id AND e.learner_id = :uid WHERE DATE(vc.scheduled_at) = :date ORDER BY vc.scheduled_at ASC");
    $stmt->execute([":uid" => $learnerId, ":date" => $date]);
    $results["conferences"] = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT c.title AS course_title, c.enrollment_deadline AS deadline FROM ld_course c JOIN ld_enrollment en ON en.course_id = c.id AND en.learner_id = :uid WHERE DATE(c.enrollment_deadline) = :date AND en.status != 'completed'");
    $stmt->execute([":uid" => $learnerId, ":date" => $date]);
    $results["deadlines"] = $stmt->fetchAll();
    echo json_encode(["success" => true, "date" => $date, "details" => $results]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }