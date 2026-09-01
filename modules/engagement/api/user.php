<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\UserController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$ctrl = new UserController();
$action = $_GET['action'] ?? 'list';
$data = inputData();

try {
    switch ($action) {
        case 'list':
            jsonResponse($ctrl->index());
            break;
        case 'view':
            if (empty($data['id'])) jsonResponse(['error' => 'id is required'], 400);
            jsonResponse($ctrl->show((int)$data['id']));
            break;
        case 'create':
            foreach (['employee_id','username','email','password','full_name','role','status','theme'] as $f) {
                if (!isset($data[$f])) jsonResponse(['error' => "$f is required"], 400);
            }
            $id = $ctrl->store($data);
            jsonResponse(['id' => $id], 201);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

