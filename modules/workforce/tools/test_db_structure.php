<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');

$conn = Database::getInstance()->getConnection();

echo "<h2>Database Structure Check</h2><hr>";

// Check all tables
echo "<h3>All Tables in hr-management:</h3>";
$tables = $conn->query("SHOW TABLES");
$table_list = [];
while ($row = $tables->fetch_assoc()) {
    $table_name = array_values($row)[0];
    $table_list[] = $table_name;
    echo "- $table_name<br>";
}

echo "<hr><h3>Performance-Related Columns:</h3>";

// Check performance_reviews if it exists
if (in_array('performance_reviews', $table_list)) {
    echo "<b>performance_reviews columns:</b><br>";
    $cols = $conn->query("DESCRIBE performance_reviews");
    while ($col = $cols->fetch_assoc()) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
} else {
    echo "performance_reviews table NOT FOUND<br>";
}

echo "<hr><h3>WFA Tables Status:</h3>";
$wfa_tables = ['wfa_actions', 'wfa_performance_improvement_plans', 'wfa_action_recommendations', 'wfa_performance_issues'];
foreach ($wfa_tables as $table) {
    $exists = in_array($table, $table_list) ? "✓ EXISTS" : "✗ MISSING";
    echo "$table: $exists<br>";
}

echo "<hr><h3>Sample Performance Data:</h3>";
$perf = $conn->query("SELECT * FROM performance_reviews LIMIT 3");
if ($perf) {
    echo "Found: " . $perf->num_rows . " records<br>";
    while ($row = $perf->fetch_assoc()) {
        echo "<pre>" . json_encode($row, JSON_PRETTY_PRINT) . "</pre>";
    }
}
?>
