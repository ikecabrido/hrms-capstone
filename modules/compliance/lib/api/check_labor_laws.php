<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$pdo = (new Database())->getConnection();
$stmt = $pdo->query("SELECT id, title, short_title, reference_number, status, keywords FROM lc_labor_law_references ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
