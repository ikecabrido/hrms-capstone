<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 7) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $userId = (int)($_GET["user_id"] ?? 0);
    if ($userId <= 0) { http_response_code(400); echo json_encode(["success" => false, "message" => "Missing user_id"]); exit; }
    $stmt = $pdo->prepare("SELECT c.id, c.title, c.category, e.status, e.enrolled_at, CONCAT(i.first_name, ' ', i.last_name) AS instructor_name FROM ld_enrollment e JOIN ld_course c ON c.id = e.course_id LEFT JOIN em_employees i ON i.employee_id = c.instructor_id WHERE e.learner_id = :uid ORDER BY e.enrolled_at DESC");
    $stmt->execute([":uid" => $userId]);
    $enrollments = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT ce.* FROM ld_calendar_event ce WHERE ce.user_id = :uid OR ce.user_id IS NULL ORDER BY ce.event_date ASC");
    $stmt->execute([":uid" => $userId]);
    $events = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT vc.*, c.title AS course_title FROM ld_video_conference vc JOIN ld_course c ON c.id = vc.course_id JOIN ld_enrollment e ON e.course_id = vc.course_id AND e.learner_id = :uid WHERE vc.scheduled_at >= NOW() ORDER BY vc.scheduled_at ASC");
    $stmt->execute([":uid" => $userId]);
    $conferences = $stmt->fetchAll();
    echo json_encode(["success" => true, "enrollments" => $enrollments, "events" => $events, "conferences" => $conferences]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }