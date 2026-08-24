<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');

$db = Database::getInstance()->getConnection();

// Check for performance-related tables
echo "=== Available Tables ===\n";
$result = $db->query("SHOW TABLES");

while ($row = $result->fetch_assoc()) {
    $table = $row['Tables_in_' . DB_NAME] ?? $row[array_key_first($row)];
    if (stripos($table, 'performance') !== false || stripos($table, 'appraisal') !== false || stripos($table, 'rating') !== false || stripos($table, 'pm_') !== false) {
        echo "✓ $table\n";
        
        // Get columns
        $cols = $db->query("DESCRIBE $table");
        while ($col = $cols->fetch_assoc()) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }
}
