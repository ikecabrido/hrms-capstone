<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$db = Database::getInstance();

echo "<h3>Attendance Table Sample:</h3>\n";
$att = $db->fetchAll("SELECT * FROM ta_attendance LIMIT 3");
echo "<pre>" . json_encode($att, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h3>Attendance Columns:</h3>\n";
$cols = $db->fetchAll("DESCRIBE ta_attendance");
echo "<pre>" . json_encode($cols, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h3>Attendance Status Values:</h3>\n";
$status = $db->fetchAll("SELECT DISTINCT status FROM ta_attendance LIMIT 10");
echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h3>Attendance Count by Status:</h3>\n";
$count = $db->fetchAll("SELECT status, COUNT(*) as count FROM ta_attendance GROUP BY status");
echo "<pre>" . json_encode($count, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<h3>Exit Management Tables (if any):</h3>\n";
$tables = $db->fetchAll("SHOW TABLES LIKE '%exit%' OR SHOW TABLES LIKE '%separation%' OR SHOW TABLES LIKE '%resign%'");
echo "<pre>" . json_encode($tables, JSON_PRETTY_PRINT) . "</pre>\n";

// Check for turnover-related tables
echo "<h3>Looking for turnover/separation related data:</h3>\n";
$allTables = $db->fetchAll("SHOW TABLES");
foreach ($allTables as $table) {
    $tname = $table['Tables_in_' . DB_NAME] ?? $table[array_key_first($table)];
    if (stripos($tname, 'exit') !== false || stripos($tname, 'separation') !== false || stripos($tname, 'resign') !== false || stripos($tname, 'wfa_') !== false) {
        echo "- $tname\n";
    }
}
?>
