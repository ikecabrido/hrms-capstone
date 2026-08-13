<?php
/**
 * API: Unexpected Holiday Management
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/UnexpectedHoliday.php';

Session::start();

// Check authentication
if (!AuthController::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check HR permission
if (!AuthController::hasRole('time') && !AuthController::hasRole('hr')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    // Initialize table if not exists
    $db = Database::getInstance();
    $conn = $db->getConnection();
    UnexpectedHoliday::createTable($conn);

    $holidayModel = new UnexpectedHoliday();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $response = [];

    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'POST method required'];
                break;
            }

            $data = [
                'holiday_date' => $_POST['holiday_date'] ?? '',
                'holiday_name' => $_POST['holiday_name'] ?? '',
                'reason' => $_POST['reason'] ?? '',
                'description' => $_POST['description'] ?? '',
                'holiday_type' => $_POST['holiday_type'] ?? 'OTHER',
                'created_by' => $_SESSION['user_id'] ?? null
            ];

            if (empty($data['holiday_date']) || empty($data['holiday_name'])) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Date and name are required'];
                break;
            }

            $response = $holidayModel->addHoliday($data);
            break;

        case 'get_all':
            $status = $_GET['status'] ?? 'ACTIVE';
            $from_date = $_GET['from_date'] ?? null;
            $to_date = $_GET['to_date'] ?? null;

            $holidays = $holidayModel->getHolidays($status, $from_date, $to_date);

            $response = [
                'success' => true,
                'message' => 'Holidays retrieved successfully',
                'data' => $holidays,
                'total' => count($holidays)
            ];
            break;

        case 'get_by_date':
            $date = $_GET['date'] ?? date('Y-m-d');
            $holiday = $holidayModel->getHolidayByDate($date);

            $response = [
                'success' => true,
                'message' => 'Holiday retrieved',
                'data' => $holiday
            ];
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'POST method required'];
                break;
            }

            $id = $_POST['id'] ?? 0;
            if (!$id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Holiday ID required'];
                break;
            }

            $data = [
                'holiday_date' => $_POST['holiday_date'] ?? '',
                'holiday_name' => $_POST['holiday_name'] ?? '',
                'reason' => $_POST['reason'] ?? '',
                'description' => $_POST['description'] ?? '',
                'holiday_type' => $_POST['holiday_type'] ?? 'OTHER'
            ];

            $response = $holidayModel->updateHoliday($id, $data);
            break;

        case 'cancel':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'POST method required'];
                break;
            }

            $id = $_POST['id'] ?? 0;
            if (!$id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Holiday ID required'];
                break;
            }

            $response = $holidayModel->cancelHoliday($id);
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'POST method required'];
                break;
            }

            $id = $_POST['id'] ?? 0;
            if (!$id) {
                http_response_code(400);
                $response = ['success' => false, 'message' => 'Holiday ID required'];
                break;
            }

            $response = $holidayModel->deleteHoliday($id);
            break;

        default:
            http_response_code(400);
            $response = [
                'success' => false,
                'message' => 'Invalid action. Use: add, get_all, get_by_date, update, cancel, or delete'
            ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
