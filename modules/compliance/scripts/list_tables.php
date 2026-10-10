<?php
require 'C:/xampp/htdocs/hrms-capstone/vendor/autoload.php';
use App\Database\Connection;

try {
    $conn = Connection::get();
    $stmt = $conn->query("SHOW TABLES LIKE 'lc_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Compliance tables found: " . count($tables) . "\n";
    foreach ($tables as $t) {
        echo " - " . $t . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
