<?php
require 'c:/xampp/htdocs/hrms/hrms-capstone/database/db.php';
$db = new Database();
$c = $db->getConnection();

echo "---COLUMNS---\n";
$cols = $c->query('SHOW COLUMNS FROM ld_learning_path')->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

echo "---ROWS_WITH_KT_PLAN_ID---\n";
try {
    $rows = $c->query('SELECT id, kt_plan_id FROM ld_learning_path WHERE kt_plan_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Throwable $e) {
    echo "ROWS_ERROR: " . $e->getMessage() . "\n";
}