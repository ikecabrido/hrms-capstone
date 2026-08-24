<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

$db = Database::getInstance()->getConnection();
$result = $db->query("DESCRIBE employees");

echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td></tr>";
}
echo "</table>";
?>
