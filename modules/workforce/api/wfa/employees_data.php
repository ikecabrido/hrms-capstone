<?php
/**
 * Employees Data API
 * Returns all employees data for dashboard
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Employee.php';

try {
    $employee = new Employee();
    $employees = $employee->getAllEmployees();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employees' => $employees,
            'total' => count($employees)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
