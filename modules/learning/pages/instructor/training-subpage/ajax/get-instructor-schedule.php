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
    $month = (int)($_GET["month"] ?? date("m"));
    $year = (int)($_GET["year"] ?? date("Y"));
    $stmt = $pdo->prepare("SELECT ce.* FROM ld_calendar_event ce WHERE ce.user_id = :uid AND MONTH(ce.event_date) = :m AND YEAR(ce.event_date) = :y ORDER BY ce.event_date ASC");
    $stmt->execute([":uid" => $instructorId, ":m" => $month, ":y" => $year]);
    $events = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT vc.*, c.title AS course_title FROM ld_video_conference vc JOIN ld_course c ON c.id = vc.course_id WHERE c.instructor_id = :iid AND MONTH(vc.scheduled_at) = :m AND YEAR(vc.scheduled_at) = :y ORDER BY vc.scheduled_at ASC");
    $stmt->execute([":iid" => $instructorId, ":m" => $month, ":y" => $year]);
    $conferences = $stmt->fetchAll();
    echo json_encode(["success" => true, "events" => $events, "conferences" => $conferences]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }