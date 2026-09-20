<?php
require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../compliance/lib/ajax/document_template_helper.php';

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT package_id, file_path, file_name FROM lc_onboarding_packages WHERE employee_id = :eid ORDER BY created_at DESC LIMIT 1");
$stmt->execute([':eid' => 38]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo 'Package ID: ' . $row['package_id'] . "\n";
    echo 'File path: ' . $row['file_path'] . "\n";
    echo 'File name: ' . $row['file_name'] . "\n";
} else {
    echo 'No package found.' . "\n";
}
