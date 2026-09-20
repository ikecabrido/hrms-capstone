<?php
require 'C:/xampp/htdocs/hrms-capstone/vendor/autoload.php';
use App\Database\Connection;

try {
    $conn = Connection::get();
    
    // Check compliance_summary
    $stmt = $conn->query("SELECT COUNT(*) FROM lc_compliance_summary");
    $rows = $stmt->fetchColumn();
    echo "Rows in lc_compliance_summary: " . $rows . "\n";
    
    $stmt = $conn->query("SELECT AVG(overall_score) FROM lc_compliance_summary WHERE overall_score IS NOT NULL");
    $avg = $stmt->fetchColumn();
    echo "Average overall_score: " . $avg . "\n";
    
    // Check if lc_incident_report exists
    $stmt = $conn->query("SHOW TABLES LIKE 'lc_incident_report'");
    $exists = $stmt->fetchColumn();
    echo "lc_incident_report exists: " . ($exists ? 'Yes' : 'No') . "\n";
    
    // Check incident-related tables
    $stmt = $conn->query("SHOW TABLES LIKE 'lc_incident%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Incident-related tables:\n";
    foreach ($tables as $t) {
        echo " - " . $t . "\n";
    }
    
    // Check risks table
    $stmt = $conn->query("SELECT COUNT(*) FROM lc_risks WHERE archived = 0");
    $riskCount = $stmt->fetchColumn();
    echo "Open risks (archived=0): " . $riskCount . "\n";
    
    // Check lc_incident_report structure
    $stmt = $conn->query("DESCRIBE lc_incident_report");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "lc_incident_report columns: " . implode(', ', $cols) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
