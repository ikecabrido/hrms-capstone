<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$pdo = (new Database())->getConnection();
$tables = ['lc_policies', 'lc_policy_categories', 'lc_lala_question_patterns', 'lc_labor_law_references'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$t`");
        $count = $stmt->fetchColumn();
        echo "$t: $count records\n";
    } catch (Exception $e) {
        echo "$t: MISSING\n";
    }
}
