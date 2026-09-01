<?php
require_once '../autoload.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use App\Controllers\ProjectController;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$projectController = new ProjectController();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $projects = $projectController->getProjects();
                echo json_encode(['success' => true, 'data' => $projects]);
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $project = $projectController->getProject($_GET['id']);
                if ($project) {
                    echo json_encode(['success' => true, 'data' => $project]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Project not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'POST':
            if ($action === 'create') {
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    $data = $_POST;
                }

                $createdBy = $_SESSION['employee_id'] ?? $_SESSION['user']['employee_id'] ?? null;
                if (empty($createdBy)) {
                    echo json_encode(['success' => false, 'message' => 'Current user is not linked to an employee record']);
                    break;
                }
                $result = $projectController->createProject($data['name'], $data['description'], $data['deadline'], $data['status'], (int)$createdBy);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Project created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create project']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'PUT':
            if ($action === 'update' && isset($_GET['id'])) {
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $projectController->updateProjectStatus($_GET['id'], $data['status']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update project']);
                }
            } elseif ($action === 'update_status' && isset($_GET['id'])) {
                $status = json_decode(file_get_contents('php://input'), true)['status'] ?? null;
                $result = $projectController->updateProjectStatus($_GET['id'], $status);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Project status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update project status']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        case 'DELETE':
            if ($action === 'delete' && isset($_GET['id'])) {
                $result = $projectController->deleteProject($_GET['id']);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
