<?php
header('Content-Type: application/json');

require_once __DIR__ . '/auth/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Test 1: Basic connection
    $test = $db->query("SELECT 1 as status");
    echo json_encode(['step' => 1, 'message' => 'DB connected', 'success' => true], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'step' => -1,
        'error' => $e->getMessage(),
        'success' => false
    ], JSON_PRETTY_PRINT);
}
?>
