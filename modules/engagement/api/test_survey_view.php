<?php
// Test the API directly
require_once __DIR__ . '/../../auth/database.php';
require_once __DIR__ . '/../autoload.php';

use App\Controllers\SurveyController;

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION['user'] = ['id' => 1];
    
    $ctrl = new SurveyController();
    $survey = $ctrl->show(1);
    
    echo json_encode($survey, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

