<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$db = (new Database())->getConnection();
$cols = $db->query("SHOW COLUMNS FROM lc_legal_cases")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols) . PHP_EOL;
