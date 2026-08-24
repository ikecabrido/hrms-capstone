<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');

$db = Database::getInstance()->getConnection();

// Read SQL file
$sql_file = __DIR__ . '/../sql/wfa_action_system.sql';

if (!file_exists($sql_file)) {
    die("SQL file not found: $sql_file");
}

$sql = file_get_contents($sql_file);

// Split by semicolons and execute each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($db->query($statement)) {
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } else {
            echo "✗ Error: " . $db->error . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
}

// Verify tables
echo "\n=== Verifying Tables ===\n";
$tables = ['wfa_performance_improvement_plans', 'wfa_actions', 'wfa_action_recommendations', 'wfa_performance_issues'];

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✓ $table exists\n";
    } else {
        echo "✗ $table missing\n";
    }
}

echo "\nDone!\n";
