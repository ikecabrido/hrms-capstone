<?php
/**
 * Find Emily Davis and update her status
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

try {
    $db = Database::getInstance();
    
    // First, find Emily Davis
    $query = "SELECT * FROM employees WHERE full_name LIKE '%Emily Davis%' OR full_name LIKE '%Emily%' AND department LIKE '%HR%'";
    $result = $db->getConnection()->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "=== Found Employees ===\n\n";
        
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row, JSON_PRETTY_PRINT) . "\n\n";
        }
        
        // Try more specific search
        $specificQuery = "SELECT * FROM employees WHERE employee_id = 6";
        $specificResult = $db->getConnection()->query($specificQuery);
        
        if ($specificResult && $specificResult->num_rows > 0) {
            echo "=== Employee ID 6 ===\n\n";
            $emp = $specificResult->fetch_assoc();
            echo json_encode($emp, JSON_PRETTY_PRINT) . "\n\n";
            
            // Now update the status
            echo "=== Updating Status ===\n\n";
            
            $updateQuery = "UPDATE employees SET employment_status = 'Inactive' WHERE employee_id = 6";
            if ($db->getConnection()->query($updateQuery)) {
                echo "Success! Updated employee_id 6 to Inactive status\n";
                
                // Get updated record
                $updatedResult = $db->getConnection()->query("SELECT * FROM employees WHERE employee_id = 6");
                if ($updatedResult && $updatedResult->num_rows > 0) {
                    $updated = $updatedResult->fetch_assoc();
                    echo "\nUpdated record:\n";
                    echo json_encode($updated, JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "Error updating: " . $db->getConnection()->error . "\n";
            }
        } else {
            echo "No employee found with ID 6\n";
        }
    } else {
        echo "No employees named Emily Davis found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
