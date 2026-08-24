<?php
/**
 * Generate PDF Report API
 * Generates and downloads PDF reports
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/Analytics.php';

try {
    $type = isset($_GET['type']) ? $_GET['type'] : 'dashboard';
    
    // For now, return as CSV with PDF header
    // In production, you'd use a library like FPDF or mPDF
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="HR_Report_' . date('Y-m-d') . '.pdf"');
    
    $analytics = new Analytics();
    
    // Generate PDF content based on type
    $content = "HR Management System Report\n";
    $content .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    if ($type === 'dashboard') {
        $metrics = $analytics->getDashboardMetrics();
        $content .= "DASHBOARD METRICS\n";
        $content .= "==================\n";
        foreach ($metrics as $key => $value) {
            $content .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
        }
    }
    
    // Note: This is a simplified version. For actual PDF generation, use FPDF library
    echo $content;
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
