<?php
/**
 * AJAX endpoint for CRUD operations on recommendation course mappings.
 * POST with action=add|edit|delete|get|list
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__FILE__, 6) . '/database/db.php';
require_once dirname(__FILE__, 4) . '/classes/coursemap.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $map = new CourseMap($pdo);

    // Ensure admin session
    if (empty($_SESSION['employee_id']) || (($_SESSION['learning_role'] ?? '') !== 'admin' && ($_SESSION['is_admin'] ?? 0) != 1)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            echo json_encode(['success' => true, 'items' => $map->getRecommendationMaps()]);
            break;

        case 'get':
            $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            $item = $map->getRecommendationMapById($id);
            echo json_encode($item ? ['success' => true, 'item' => $item] : ['success' => false, 'message' => 'Not found']);
            break;

        case 'add':
            $developmentArea = trim($_POST['development_area'] ?? '');
            $courseId = (int)($_POST['course_id'] ?? 0);
            $result = $map->addRecommendationMap($developmentArea, $courseId);
            echo json_encode($result);
            break;

        case 'edit':
            $id = (int)($_POST['id'] ?? 0);
            $developmentArea = trim($_POST['development_area'] ?? '');
            $courseId = (int)($_POST['course_id'] ?? 0);
            $result = $map->editRecommendationMap($id, $developmentArea, $courseId);
            echo json_encode($result);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $result = $map->deleteRecommendationMap($id);
            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
