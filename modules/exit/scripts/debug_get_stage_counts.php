<?php
require_once __DIR__ . '/../controllers/ExitManagementController.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
try {
    $ctl = new ExitManagementController();
    $counts = $ctl->getStagePendingCounts();
    header('Content-Type: application/json');
    echo json_encode($counts, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
