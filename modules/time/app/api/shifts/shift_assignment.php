<?php
/**
 * Shift Assignment API
 * Handles assignment of shifts to employees
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/models/ShiftValidator.php';
require_once __DIR__ . '/../app/models/EmployeeShift.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check role for assignment operations
$isHR = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'HR';

if (!$isHR && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$shiftValidator = new ShiftValidator();
$database = TimeDatabase::getInstance();
$db = $database->getConnection();
$employeeShiftModel = new EmployeeShift($db);

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'assign':
        handleAssignShift();
        break;
    
    case 'get_unassigned_count':
        handleGetUnassignedCount();
        break;
    
    case 'get_unassigned_employees':
        handleGetUnassignedEmployees();
        break;
    
    case 'check_employee_shift':
        handleCheckEmployeeShift();
        break;
    
    case 'get_available_shifts':
        handleGetAvailableShifts();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleAssignShift()
{
    global $shiftValidator;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        return;
    }
    
    $employee_id = $data['employee_id'] ?? null;
    $shift_id = $data['shift_id'] ?? null;
    $effective_from = $data['effective_from'] ?? null;
    $effective_to = $data['effective_to'] ?? null;
    
    if (!$employee_id || !$shift_id || !$effective_from) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $result = $shiftValidator->assignShift($employee_id, $shift_id, $effective_from, $effective_to);
    echo json_encode($result);
}

function handleGetUnassignedCount()
{
    global $shiftValidator;
    
    $count = $shiftValidator->getUnassignedShiftCount();
    echo json_encode([
        'success' => true,
        'count' => $count,
        'message' => $count === 0 ? 'All employees have shifts assigned' : "$count employee(s) without shift"
    ]);
}

function handleGetUnassignedEmployees()
{
    global $employeeShiftModel;
    
    $employees = $employeeShiftModel->getEmployeesWithoutShift();
    echo json_encode([
        'success' => true,
        'data' => $employees,
        'count' => count($employees)
    ]);
}

function handleCheckEmployeeShift()
{
    global $shiftValidator;
    
    $employee_id = isset($_GET['employee_id']) ? $_GET['employee_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
        return;
    }
    
    $shift = $shiftValidator->hasShiftAssignedToday($employee_id, $date);
    
    if ($shift) {
        echo json_encode([
            'success' => true,
            'has_shift' => true,
            'shift' => $shift,
            'message' => 'Shift found'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'has_shift' => false,
            'message' => 'No shift assigned for this employee on the specified date'
        ]);
    }
}

function handleGetAvailableShifts()
{
    global $shiftValidator;
    
    $shifts = $shiftValidator->getAvailableShifts();
    echo json_encode([
        'success' => true,
        'data' => $shifts,
        'count' => count($shifts)
    ]);
}
?>
