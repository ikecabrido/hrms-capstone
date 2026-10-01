<?php
/**
 * API: Absence & Late Management
 * Handles CRUD operations for absence/late records
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../models/AbsenceLateMgmt.php';
require_once __DIR__ . '/../../core/Session.php';

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$absenceLateMgmt = new AbsenceLateMgmt();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_records':
            handleGetRecords($absenceLateMgmt);
            break;

        case 'get_record':
            handleGetRecord($absenceLateMgmt);
            break;

        case 'submit_excuse':
            handleSubmitExcuse($absenceLateMgmt);
            break;

        case 'review_excuse':
            handleReviewExcuse($absenceLateMgmt);
            break;

        case 'add_notes':
            handleAddNotes($absenceLateMgmt);
            break;

        case 'get_employee_summary':
            handleGetEmployeeSummary($absenceLateMgmt);
            break;

        case 'get_report':
            handleGetReport($absenceLateMgmt);
            break;

        case 'get_summary_stats':
            handleGetSummaryStats($absenceLateMgmt);
            break;

        case 'get_pending':
            handleGetPending($absenceLateMgmt);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGetRecords($absenceLateMgmt)
{
    // Check permission
    if (!AuthController::hasRole('time')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $filters = [
        'employee_id' => $_GET['employee_id'] ?? null,
        'type' => $_GET['type'] ?? null,
        'excuse_status' => $_GET['excuse_status'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'limit' => (int)($_GET['limit'] ?? 50),
        'offset' => (int)($_GET['offset'] ?? 0)
    ];

    $records = $absenceLateMgmt->getRecords($filters);
    echo json_encode(['success' => true, 'data' => $records]);
}

function handleGetRecord($absenceLateMgmt)
{
    $record_id = $_GET['record_id'] ?? null;
    if (!$record_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'record_id required']);
        exit;
    }

    $record = $absenceLateMgmt->getRecord($record_id);
    if (!$record) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $record]);
}

function handleSubmitExcuse($absenceLateMgmt)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $record_id = $data['record_id'] ?? null;
    $reason = $data['reason'] ?? null;
    $document = $data['document'] ?? null;
    $employee_id = $_SESSION['user']['employee_id'] ?? null;

    if (!$record_id || !$reason) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'record_id and reason required']);
        exit;
    }

    $result = $absenceLateMgmt->submitExcuse($record_id, $reason, $document, $employee_id);
    echo json_encode(['success' => $result, 'message' => $result ? 'Excuse submitted' : 'Failed to submit excuse']);
}

function handleReviewExcuse($absenceLateMgmt)
{
    // Check HR permission
    if (!AuthController::hasRole('hr') && !AuthController::hasRole('time')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $record_id = $data['record_id'] ?? null;
    $status = $data['status'] ?? null; // APPROVED, REJECTED
    $notes = $data['notes'] ?? '';
    $reviewed_by = $_SESSION['user']['user_id'] ?? null;

    if (!$record_id || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'record_id and status required']);
        exit;
    }

    if (!in_array($status, ['APPROVED', 'REJECTED'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    $result = $absenceLateMgmt->reviewExcuse($record_id, $status, $notes, $reviewed_by);
    echo json_encode(['success' => $result, 'message' => $result ? "Excuse {$status}" : 'Failed to review excuse']);
}

function handleAddNotes($absenceLateMgmt)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST required']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $record_id = $data['record_id'] ?? null;
    $notes = $data['notes'] ?? null;

    if (!$record_id || !$notes) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'record_id and notes required']);
        exit;
    }

    $result = $absenceLateMgmt->addNotes($record_id, $notes);
    echo json_encode(['success' => $result, 'message' => $result ? 'Notes added' : 'Failed to add notes']);
}

function handleGetEmployeeSummary($absenceLateMgmt)
{
    $employee_id = $_GET['employee_id'] ?? null;
    $month_year = $_GET['month_year'] ?? null;

    if (!$employee_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'employee_id required']);
        exit;
    }

    $summary = $absenceLateMgmt->getEmployeeSummary($employee_id, $month_year);
    echo json_encode(['success' => true, 'data' => $summary]);
}

function handleGetReport($absenceLateMgmt)
{
    if (!AuthController::hasRole('time') && !AuthController::hasRole('hr')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $filters = [
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'department' => $_GET['department'] ?? null,
        'type' => $_GET['type'] ?? null
    ];

    $report = $absenceLateMgmt->getReport($filters);
    echo json_encode(['success' => true, 'data' => $report]);
}

function handleGetSummaryStats($absenceLateMgmt)
{
    if (!AuthController::hasRole('time') && !AuthController::hasRole('hr')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $filters = [
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null
    ];

    $stats = $absenceLateMgmt->getSummaryStats($filters);
    echo json_encode(['success' => true, 'data' => $stats]);
}

function handleGetPending($absenceLateMgmt)
{
    if (!AuthController::hasRole('time') && !AuthController::hasRole('hr')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $limit = (int)($_GET['limit'] ?? 20);
    $pending = $absenceLateMgmt->getPendingApprovals($limit);
    echo json_encode(['success' => true, 'data' => $pending]);
}
?>
