<?php
/**
 * Update Employee Status Script
 * Changes Emily Davis (ID 6) to Inactive status
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Employee.php';

try {
    $employee = new Employee();
    
    // Get Emily Davis's current data
    $employeeData = $employee->getEmployeeById(6);
    
    if (!$employeeData) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Employee with ID 6 not found'
        ]);
        exit;
    }
    
    // Prepare update data with Terminated status (marking as Inactive)
    $updateData = [
        'name' => $employeeData['name'],
        'gender' => $employeeData['gender'],
        'department' => $employeeData['department'],
        'position' => $employeeData['position'],
        'hire_date' => $employeeData['hire_date'],
        'employment_status' => 'Terminated',  // Changed to Terminated (=Inactive)
        'salary' => $employeeData['salary'],
        'performance_score' => $employeeData['performance_score'],
        'absence_days' => $employeeData['absence_days'],
        'age' => $employeeData['age']
    ];
    
    // Update the employee
    $result = $employee->updateEmployee(6, $updateData);
    
    if ($result > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Employee status updated successfully to Inactive (Terminated)',
            'employee_id' => 6,
            'employee_name' => $employeeData['name'],
            'new_status' => 'Terminated'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update employee status'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
