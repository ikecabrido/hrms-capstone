<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');

$db = Database::getInstance()->getConnection();

// Check for attendance-related tables
echo "=== Attendance Tables ===\n";
$result = $db->query("SHOW TABLES");

while ($row = $result->fetch_assoc()) {
    $table = $row['Tables_in_' . DB_NAME] ?? $row[array_key_first($row)];
    if (stripos($table, 'attendance') !== false || stripos($table, 'time') !== false || stripos($table, 'ta_') !== false) {
        echo "✓ $table\n";
    }
}
