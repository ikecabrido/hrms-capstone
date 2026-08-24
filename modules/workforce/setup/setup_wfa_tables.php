<?php
/**
 * Setup WFA (Workforce Analytics) Tables
 * Creates required tables for action tracking system
 */

require_once __DIR__ . '/../../auth/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/setup_wfa_tables.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 60) . "...\n";
        }
    }
    
    echo "\n✅ All tables created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
