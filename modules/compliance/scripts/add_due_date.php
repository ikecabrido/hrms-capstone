<?php
require 'C:/xampp/htdocs/hrms-capstone/database/db.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE lc_legal_cases ADD COLUMN IF NOT EXISTS due_date DATE DEFAULT NULL");
    echo "due_date column added/verified.\n";
} catch (Throwable $e) {
    echo "due_date check: " . $e->getMessage() . "\n";
}
