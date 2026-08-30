<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/classes/module.php';
require_once dirname(__FILE__, 8) . '/database/db.php';

try {
    $learnerId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    if ($learnerId <= 0) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
        exit;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

    $database = new Database();
    $pdo = $database->getConnection();
    $moduleModel = new Module($pdo);

    if ($id > 0) {
        $module = $moduleModel->getById($id);

        if (!$module || $module['status'] !== 'active') {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Module not found.'
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'item' => $module
        ]);
        exit;
    }

    if ($courseId <= 0) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Course ID is required.'
        ]);
        exit;
    }

    $modules = $moduleModel->getList($courseId);

    $modules = array_values(array_filter(
        $modules,
        static function (array $module): bool {
            return isset($module['status']) && $module['status'] === 'active';
        }
    ));

    echo json_encode([
        'success' => true,
        'items' => $modules,
        'count' => count($modules)
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Failed to load modules.'
    ]);
}