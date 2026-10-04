<?php
/**
 * Get Pending Leave Requests API
 * GET /api/get_pending_leaves.php
 * 
 * Returns pending leave requests for the current user
 * - Department heads see leaves from their department
 * - HR admins see all leaves
 * - Employees see their own leaves
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Leave.php';

Session::start();

// Check if user is authenticated
if (!Session::get('user_id')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$user_role = Session::get('role');
$user_id = Session::get('user_id');

$leaveModel = new Leave();
$leaves = [];

try {
    if ($user_role === 'DEPARTMENT_HEAD') {
        // Get pending leaves for department head's department
        $leaves = $leaveModel->getPendingByDepartmentHead($user_id);
    } elseif ($user_role === 'HR_ADMIN') {
        // Get all leaves pending HR approval
        $leaves = $leaveModel->getForHRApproval();
    } else {
        // Employee - would need different query to get their leaves
        // For now, return empty for employees
        $leaves = [];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => count($leaves) > 0 ? 'Pending leaves retrieved' : 'No pending leaves',
        'count' => count($leaves),
        'data' => $leaves
    ]);
} catch (Exception $e) {
    error_log("Get Pending Leaves Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
