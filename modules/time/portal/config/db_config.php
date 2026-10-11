<?php
/**
 * Database Configuration for Time & Attendance Portal
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'localhost');
define('DB_PASS', 'admin123');
define('DB_NAME', 'hr-management');

// Timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
