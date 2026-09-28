<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load AI config (GEMINI_API_KEY) if present. Wrapped in file_exists() so a
// missing config file doesn't fatal the whole app — AI summarization just
// returns its "not configured" message instead.
// $aiConfig = _DIR_ . '/../config/ai_config.php';
// if (file_exists($aiConfig)) {
//     require_once $aiConfig;
// }

$timeout = 1800;

// Helper to detect API/fetch requests
$isApiRequest = $_SERVER['REQUEST_METHOD'] === 'POST' ||
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

// Redirect to login if not authenticated
if (!isset($_SESSION['employee_id'])) {
    if ($isApiRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit();
    }
    header('Location: hrms-capstone/index.php');
    exit();
}

// Check for session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    if ($isApiRequest) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit();
    }
    header('Location: hrms-capstone/index.php?reason=timeout');
    exit();
}

$_SESSION['last_activity'] = time();

$current_employee_name = $_SESSION['employee_name'] ?? 'Unknown';
$current_employee_id   = $_SESSION['employee_id'];
$current_department_id   = $_SESSION['department_id'];
$current_department_name = $_SESSION['department_name'] ?? 'Unknown';
$current_role          = (int) $_SESSION['role'];
$current_role_name     = $_SESSION['role_name'];
