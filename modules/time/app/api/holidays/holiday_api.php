<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../../core/TimeDatabase.php';

require_once __DIR__ . '/../../controllers/HolidayController.php';
require_once __DIR__ . '/../../models/Holiday.php';
require_once __DIR__ . '/../../services/NagerDateService.php';


header('Content-Type: application/json; charset=utf-8');

try {
    $db = TimeDatabase::getInstance()->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }
    $controller = new \App\Controllers\HolidayController($db);
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($action) {
        case 'get_all':
            echo $controller->getAllHolidays();
            break;
        case 'get_upcoming':
            echo $controller->getUpcomingHolidays();
            break;
        case 'get_range':
            echo $controller->getHolidaysByRange();
            break;
        case 'is_holiday':
            echo $controller->isHoliday();
            break;
        case 'fix_empty_names':
            echo $controller->fixEmptyNames();
            break;
        case 'get_page_data':
            echo $controller->getPageData();
            break;
        case 'create':

            if ($method === 'POST') {
                echo $controller->create();
            } else {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'message' => 'Method not allowed'
                ]);
            }

            break;
        case 'update':

            if ($method === 'POST' || $method === 'PUT') {
                echo $controller->update();
            } else {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'message' => 'Method not allowed'
                ]);
            }

            break;
        case 'delete':

            if ($method === 'POST' || $method === 'DELETE') {
                echo $controller->delete();
            } else {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'message' => 'Method not allowed'
                ]);
            }

            break;
        case 'sync':

            if ($method === 'POST') {
                echo $controller->syncHolidays();
            } else {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'message' => 'Method not allowed'
                ]);
            }

            break;
        case 'sync_info':
            echo $controller->getSyncInfo();
            break;
        case 'debug':

            echo json_encode([
                'success' => true,
                'message' => 'Holiday API is working',
                'db_connected' => $db ? true : false,
                'controller_created' => method_exists(
                    $controller,
                    'getPageData'
                )
            ]);

            break;
        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'Unknown action'
            ]);

            break;
    }

} catch (Exception $e) {

    http_response_code(500);

    error_log(
        'Holiday API Error: ' . $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    error_log(
        'Holiday API Throwable: ' . $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}