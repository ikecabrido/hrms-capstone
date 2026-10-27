<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 8) . "/database/db.php";
require_once dirname(__FILE__, 8) . "/includes/app-base.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $learnerId = isset($_SESSION["employee_id"]) ? (int)$_SESSION["employee_id"] : 0;
    if ($learnerId <= 0) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $contentType = $_POST["type"] ?? "course";
    $contentId = (int)($_POST["content_id"] ?? 0);
    if ($contentId <= 0) { http_response_code(400); echo json_encode(["success" => false, "message" => "Missing content_id"]); exit; }
    $shareToken = bin2hex(random_bytes(16));
    // Share links point at the live study pages. The type is whitelisted rather than
    // interpolated, so a posted value can never be pasted into the URL.
    $shareTargets = [
        "course" => "learner/study-subpage/course&course_id=",
        "module" => "learner/study-subpage/module&module_id=",
        "lesson" => "learner/study-subpage/lesson&lesson_id=",
    ];
    $shareTarget = $shareTargets[$contentType] ?? $shareTargets["course"];
    $shareUrl = AppBase::urlFor("modules/learning/index.php")
        . "?page=" . $shareTarget . $contentId . "&share=" . $shareToken;
    echo json_encode(["success" => true, "url" => $shareUrl, "token" => $shareToken, "message" => "Share link generated"]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }