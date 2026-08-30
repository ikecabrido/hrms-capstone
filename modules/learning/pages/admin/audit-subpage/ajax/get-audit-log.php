<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 7) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $page = max(1, (int)($_GET["page"] ?? 1));
    $limit = min(50, max(1, (int)($_GET["limit"] ?? 20)));
    $offset = ($page - 1) * $limit;
    $where = "WHERE 1=1";
    $params = [];
    if (!empty($_GET["user_id"])) { $where .= " AND al.user_id = :uid"; $params[":uid"] = (int)$_GET["user_id"]; }
    if (!empty($_GET["action"])) { $where .= " AND al.action = :action"; $params[":action"] = $_GET["action"]; }
    if (!empty($_GET["date_from"])) { $where .= " AND al.created_at >= :df"; $params[":df"] = $_GET["date_from"]; }
    if (!empty($_GET["date_to"])) { $where .= " AND al.created_at <= :dt"; $params[":dt"] = $_GET["date_to"] . " 23:59:59"; }
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM ld_audit_log al $where");
    $countStmt->execute($params);
    $total = $countStmt->fetch()["total"];
    $stmt = $pdo->prepare("SELECT al.*, e.first_name, e.last_name FROM ld_audit_log al LEFT JOIN em_employees e ON e.employee_id = al.user_id $where ORDER BY al.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    echo json_encode(["success" => true, "data" => $rows, "total" => (int)$total, "page" => $page, "limit" => $limit, "pages" => ceil($total / $limit)]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }