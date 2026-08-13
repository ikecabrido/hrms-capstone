<?php

// Suppress all output except JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../../../../database/db.php';

// Auto-load classes FIRST
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/..';
    $file = $base . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Require controller directly
require_once "../controllers/HolidayController.php";
require_once "../models/Holiday.php";
require_once "../services/NagerDateService.php";

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $controller = new \App\Controllers\HolidayController($db);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // Route requests
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
            if ($method === 'POST') {
                echo $controller->create();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        case 'update':
            if ($method === 'POST' || $method === 'PUT') {
                echo $controller->update();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        case 'delete':
            if ($method === 'POST' || $method === 'DELETE') {
                echo $controller->delete();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        case 'sync':
            if ($method === 'POST') {
                echo $controller->syncHolidays();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        case 'sync_info':
            echo $controller->getSyncInfo();
            break;

        case 'get_page_data':
            echo $controller->getPageData();
            break;

        case 'debug':
            // Debug endpoint
            echo json_encode([
                'success' => true,
                'message' => 'API is working',
                'db_connected' => $db ? true : false,
                'controller_created' => method_exists($controller, 'getPageData')
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Holiday API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Holiday API Throwable: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
