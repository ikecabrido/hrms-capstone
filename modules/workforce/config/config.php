<?php
/**
 * Database Configuration
 * Workforce Analytics Module
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hr_management');

// Application settings
define('APP_NAME', 'Workforce Analytics');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/work_analytics');

// Set timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// JSON header for API responses
header('Content-Type: application/json; charset=utf-8');

?>
