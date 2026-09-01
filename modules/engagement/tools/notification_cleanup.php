<?php
// Tool: List or remove duplicate notifications grouped by employee_id and message
// Usage (CLI): php notification_cleanup.php           -> dry run (list duplicates)
//              php notification_cleanup.php --delete  -> delete duplicates (keep earliest id)
// Usage (Web):  /modules/engagement/tools/notification_cleanup.php?action=delete

require_once __DIR__ . '/../../../database/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$cli = php_sapi_name() === 'cli';
$delete = false;
if ($cli) {
    $delete = in_array('--delete', $argv, true);
} else {
    $delete = (isset($_GET['action']) && $_GET['action'] === 'delete');
}

// Find duplicates
$query = "SELECT employee_id, message, COUNT(*) AS cnt FROM eer_notifications GROUP BY employee_id, message HAVING cnt > 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$dups = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($dups)) {
    echo "No duplicate notifications found.\n";
    exit(0);
}

echo "Found " . count($dups) . " duplicate groups:\n\n";
foreach ($dups as $d) {
    echo "Employee: " . ($d['employee_id'] ?? 'NULL') . " | Count: " . $d['cnt'] . "\n";
    echo "Message: " . substr($d['message'], 0, 200) . "\n";
    echo str_repeat('-', 60) . "\n";
}

if (!$delete) {
    echo "\nDry run only. To delete duplicates, re-run with --delete (CLI) or ?action=delete (web).\n";
    exit(0);
}

// Delete duplicates, keep lowest id per (employee_id, message)
$conn->beginTransaction();
try {
    $delSql = "DELETE e1 FROM eer_notifications e1
                INNER JOIN eer_notifications e2
                  ON e1.employee_id <=> e2.employee_id
                  AND e1.message <=> e2.message
                  AND e1.id > e2.id";
    $delStmt = $conn->prepare($delSql);
    $delStmt->execute();
    $deleted = $delStmt->rowCount();
    $conn->commit();
    echo "Deleted $deleted duplicate notification rows.\n";
} catch (Exception $e) {
    $conn->rollBack();
    echo "Failed to delete duplicates: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
