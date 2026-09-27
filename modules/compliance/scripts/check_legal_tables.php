<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$db = (new Database())->getConnection();
$tables = [
    'lc_legal_cases',
    'lc_legal_case_workflow',
    'lc_legal_case_conferences',
    'lc_legal_case_documents',
    'lc_legal_case_external_updates',
];
foreach ($tables as $t) {
    $stmt = $db->query("SHOW TABLES LIKE '". $t ."'");
    echo $t . ': ' . ($stmt->fetchColumn() ? 'EXISTS' : 'MISSING') . PHP_EOL;
}
echo 'Done' . PHP_EOL;
