<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/database.php');

$conn = Database::getInstance()->getConnection();

echo "<h2>Available Performance Data</h2><hr>";

// Check pm_appraisals
echo "<h3>pm_appraisals structure:</h3>";
$cols = $conn->query("DESCRIBE pm_appraisals");
if ($cols) {
    while ($col = $cols->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    echo "<br><b>Sample data:</b><br>";
    $data = $conn->query("SELECT * FROM pm_appraisals LIMIT 2");
    while ($row = $data->fetch_assoc()) {
        echo "<pre>" . json_encode($row, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "Table not found<br>";
}

// Check ta_attendance
echo "<hr><h3>ta_attendance structure:</h3>";
$cols = $conn->query("DESCRIBE ta_attendance");
if ($cols) {
    while ($col = $cols->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    echo "<br><b>Sample data:</b><br>";
    $data = $conn->query("SELECT * FROM ta_attendance LIMIT 2");
    while ($row = $data->fetch_assoc()) {
        echo "<pre>" . json_encode($row, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "Table not found<br>";
}

// Check employees
echo "<hr><h3>employees structure:</h3>";
$cols = $conn->query("DESCRIBE employees");
if ($cols) {
    while ($col = $cols->fetch_assoc()) {
        echo $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
}

echo "<hr><h3>Employee count:</h3>";
$emp = $conn->query("SELECT COUNT(*) as count FROM employees");
$row = $emp->fetch_assoc();
echo "Total: " . $row['count'] . "<br>";
?>
