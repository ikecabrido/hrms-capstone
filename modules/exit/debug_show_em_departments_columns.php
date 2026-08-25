<?php
require_once __DIR__ . '/../../database/db.php';

try {
    $dbObj = new Database();
    $db = $dbObj->getConnection();
    $stmt = $db->query("SHOW COLUMNS FROM em_departments");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo "ERROR: " . $t->getMessage() . "\n";
    echo $t->getTraceAsString();
}
