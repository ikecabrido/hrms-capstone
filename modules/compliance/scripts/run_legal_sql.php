<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$db = (new Database())->getConnection();

$sql = file_get_contents('C:/xampp/htdocs/modules/compliance/sql/legal_cases.sql');

$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt === '') continue;
    try {
        $db->exec($stmt);
    } catch (Throwable $e) {
        echo 'Warning: ' . $e->getMessage() . PHP_EOL;
    }
}
echo 'Legal case tables verified/updated.' . PHP_EOL;

