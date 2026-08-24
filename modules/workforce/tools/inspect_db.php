<?php
/**
 * Database Schema Inspection Script
 * Check the actual structure of the employees table
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

try {
    $db = Database::getInstance();
    
    // Get table structure
    $query = "DESC employees";
    $result = $db->getConnection()->query($query);
    
    if ($result) {
        echo "=== EMPLOYEES TABLE STRUCTURE ===\n\n";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
        }
        
        // Also try to get a sample record
        echo "\n\n=== SAMPLE RECORDS ===\n\n";
        $sampleQuery = "SELECT * FROM employees LIMIT 5";
        $sampleResult = $db->getConnection()->query($sampleQuery);
        
        if ($sampleResult && $sampleResult->num_rows > 0) {
            while ($row = $sampleResult->fetch_assoc()) {
                echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "No records found or query failed\n";
        }
    } else {
        echo "Query failed: " . $db->getConnection()->error . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
