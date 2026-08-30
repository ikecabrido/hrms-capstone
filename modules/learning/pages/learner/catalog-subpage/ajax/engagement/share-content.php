<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 8) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $learnerId = isset($_SESSION["employee_id"]) ? (int)$_SESSION["employee_id"] : 0;
    if ($learnerId <= 0) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }
    $contentType = $_POST["type"] ?? "course";
    $contentId = (int)($_POST["content_id"] ?? 0);
    if ($contentId <= 0) { http_response_code(400); echo json_encode(["success" => false, "message" => "Missing content_id"]); exit; }
    $shareToken = bin2hex(random_bytes(16));
    $shareUrl = "/itsar/modules/learning/index.php?page=learner/catalog-subpage/{$contentType}&id={$contentId}&share={$shareToken}";
    echo json_encode(["success" => true, "url" => $shareUrl, "token" => $shareToken, "message" => "Share link generated"]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }