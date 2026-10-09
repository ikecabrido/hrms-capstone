<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
require_once dirname(__FILE__, 6) . "/database/db.php";
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $backupDir = dirname(__FILE__, 6) . "/backups/";
    $backups = [];
    if (is_dir($backupDir)) {
        $files = scandir($backupDir);
        foreach ($files as $file) {
            if ($file === "." || $file === "..") continue;
            $path = $backupDir . $file;
            $backups[] = ["name" => $file, "size" => filesize($path), "modified" => date("Y-m-d H:i:s", filemtime($path))];
        }
    }
    usort($backups, fn($a, $b) => strcmp($b["modified"], $a["modified"]));
    echo json_encode(["success" => true, "backups" => $backups]);
} catch (Exception $e) { http_response_code(500); echo json_encode(["success" => false, "error" => $e->getMessage()]); }