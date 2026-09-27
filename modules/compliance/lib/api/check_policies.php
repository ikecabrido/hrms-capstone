<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$pdo = (new Database())->getConnection();
$stmt = $pdo->query("SELECT id, policy_code, title, status, version, effective_date FROM lc_policies ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
