<?php
require 'C:/xampp/htdocs/hrms-capstone/vendor/autoload.php';
use App\Database\Connection;

$conn = Connection::get();
$tables = [
    'lc_compliance_records',
    'lc_employee_documents', 
    'lc_audits',
    'lc_audit_findings',
    'lc_audit_corrective_actions',
    'lc_compliance_tasks',
    'lc_compliance_items',
    'lc_policy_assignments',
    'lc_risks',
    'lc_notifications',
    'em_employees',
    'em_departments',
    'lc_incident_report'
];

foreach ($tables as $t) {
    $stmt = $conn->query("SHOW TABLES LIKE '" . $t . "'");
    $exists = $stmt->fetchColumn();
    echo $t . ': ' . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}
