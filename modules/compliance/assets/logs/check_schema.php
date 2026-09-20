<?php
require 'C:/xampp/htdocs/hrms-capstone/modules/compliance/../../../database/db.php';
$db = (new Database())->getConnection();

echo "=== em_position_salary_ranges ===\n";
$stmt = $db->query('DESCRIBE em_position_salary_ranges');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . ' ' . $r['Type'] . "\n";
}

echo "\n=== Sample data ===\n";
$stmt = $db->query('SELECT * FROM em_position_salary_ranges LIMIT 5');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r) . "\n";
}

echo "\n=== rao_hired columns for application_id=50 ===\n";
$stmt = $db->prepare('SELECT * FROM rao_hired WHERE application_id = 50 LIMIT 1');
$stmt->execute();
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if ($r) {
    echo json_encode($r);
} else {
    echo "No record found\n";
}
