<?php
require_once __DIR__ . '/../../../database/db.php';

$db = new Database();
$conn = $db->getConnection();

echo "Checking exit_knowledge_transfer_plans for ids 26 and 27\n";
$ids = [26,27];
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("SELECT id, employee_id, successor_id, start_date, end_date, status FROM exit_knowledge_transfer_plans WHERE id IN ($placeholders)");
$stmt->execute($ids);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rows) === 0) {
    echo "No rows found for ids 26,27 in exit_knowledge_transfer_plans\n";
} else {
    foreach ($rows as $r) {
        echo "KT Plan id={$r['id']} employee_id={$r['employee_id']} successor_id={$r['successor_id']} start_date={$r['start_date']} end_date={$r['end_date']} status={$r['status']}\n";
    }
}

echo "\nChecking ld_learning_path rows referencing kt_plan_id 26 or 27\n";
$stmt2 = $conn->prepare("SELECT id, title, assigned_to, is_public, kt_plan_id FROM ld_learning_path WHERE kt_plan_id IN ($placeholders)");
try {
    $stmt2->execute($ids);
    $lp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (!$lp) {
        echo "No ld_learning_path rows found for kt_plan_id 26 or 27\n";
    } else {
        foreach ($lp as $p) {
            echo "LD Path id={$p['id']} title={$p['title']} assigned_to={$p['assigned_to']} is_public={$p['is_public']} kt_plan_id={$p['kt_plan_id']}\n";
        }
    }
} catch (Throwable $e) {
    echo "Query failed or table missing: " . $e->getMessage() . "\n";
}

echo "\nSearching modules/exit for references to ld_learning_path/ld_enrollment/ld_course/kt_plan_id\n";
// This script cannot search files; please refer to codebase search results in the editor.

echo "Done.\n";
