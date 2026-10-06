<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../../database/db.php';
require_once __DIR__ . '/../../../../auth/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

require_once __DIR__ . '/../../../classes/LaborLawReference.php';

$model = new LaborLawReference($db);
$method = $_SERVER['REQUEST_METHOD'];

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
}

try {
    $userId = (int) ($_SESSION['employee_id'] ?? 0);

    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';

            if ($action === 'categories') {
                $categories = $model->getAllCategories();
                echo json_encode(['success' => true, 'data' => $categories]);
                exit;
            }

            if ($action === 'types') {
                echo json_encode(['success' => true, 'data' => $model->getReferenceTypes()]);
                exit;
            }

            if ($action === 'statuses') {
                echo json_encode(['success' => true, 'data' => $model->getStatuses()]);
                exit;
            }

            if ($action === 'detail' && !empty($_GET['id'])) {
                $record = $model->getReferenceById((int) $_GET['id']);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => 'Reference not found.']);
                    exit;
                }
                echo json_encode(['success' => true, 'data' => $record]);
                exit;
            }

            $filters = [
                'search'           => trim((string) ($_GET['search'] ?? '')),
                'reference_type'   => trim((string) ($_GET['reference_type'] ?? '')),
                'category_id'      => !empty($_GET['category_id']) ? (int) $_GET['category_id'] : null,
                'status'           => trim((string) ($_GET['status'] ?? '')),
                'issuing_authority'=> trim((string) ($_GET['issuing_authority'] ?? '')),
                'year'             => !empty($_GET['year']) ? (int) $_GET['year'] : null,
            ];

            $references = $model->getReferences(array_filter($filters, fn($v) => $v !== '' && $v !== null));
            echo json_encode(['success' => true, 'data' => $references, 'count' => count($references)]);
            break;

        case 'POST':
            $action = $body['action'] ?? $_POST['action'] ?? '';

            if ($action === 'create' || $action === '') {
                $required = ['title'];
                foreach ($required as $field) {
                    if (empty($body[$field])) {
                        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required.']);
                        exit;
                    }
                }

                $data = $body;
                $data['created_by'] = $userId;
                $data['updated_by'] = $userId;

                $newId = $model->createReference($data);
                echo json_encode(['success' => true, 'message' => 'Reference created successfully.', 'data' => ['id' => $newId]]);
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;

        case 'PUT':
        case 'PATCH':
            if (empty($body['id'])) {
                echo json_encode(['success' => false, 'message' => 'Reference ID is required for update.']);
                exit;
            }

            $updateId = (int) $body['id'];

            if (empty($body['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required.']);
                exit;
            }

            $data = $body;
            $data['updated_by'] = $userId;

            $model->updateReference($updateId, $data);
            echo json_encode(['success' => true, 'message' => 'Reference updated successfully.', 'data' => ['id' => $updateId]]);
            break;

        case 'DELETE':
            if (empty($body['id'])) {
                echo json_encode(['success' => false, 'message' => 'Reference ID is required for deletion.']);
                exit;
            }

            $delId = (int) $body['id'];
            $existing = $model->getReferenceById($delId);
            if (!$existing) {
                echo json_encode(['success' => false, 'message' => 'Reference not found.']);
                exit;
            }

            $model->deleteReference($delId);
            echo json_encode(['success' => true, 'message' => 'Reference deleted successfully.', 'data' => ['id' => $delId]]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data'    => [],
    ]);
}
