<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Controllers\FeedbackController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? 'list';
$ctrl = new FeedbackController();
$data = inputData();

try {
    switch ($action) {
        case 'list':
            $feedback = $ctrl->index();
            jsonResponse(['success' => true, 'data' => $feedback]);
            break;
        default:
            jsonResponse(['error' => 'unknown action'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
