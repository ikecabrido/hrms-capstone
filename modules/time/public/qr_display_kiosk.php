<?php
/**
 * QR Code Display Kiosk
 * Public display for attendance QR codes
 * Continuously generates new codes every 30 seconds
 */

// Set timezone to Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check authentication (must be HR Admin to access this)
if (!AuthController::isAuthenticated() || !AuthController::hasRole('time')) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

$user_id = AuthController::getCurrentUserId();
$qrHelper = new QRHelper();

// Generate a single QR code for this view
$token = $qrHelper->generateToken($user_id, Helper::getCurrentDate());

if (!$token) {
    die("Failed to generate QR token");
}

// Get token IP from database for QR generation
$query = "SELECT ip_address FROM ta_attendance_tokens WHERE token = :token LIMIT 1";
$stmt = $GLOBALS['conn'] ?? null;

// Fallback to getting IP directly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// Use the detected IP from the token or detect it now
$host = $qrHelper->getServerIP() ?? '172.20.10.6';

// Add port if specified and not default
$port = $_SERVER['SERVER_PORT'] ?? 80;
if ($port != 80 && $port != 443) {
    $host .= ':' . $port;
}

// Point to employee portal login with qr_token parameter
$qr_url = $protocol . "://" . $host . "/capstone_hr_management_system/employee_portal/index.php?url=auth-index&qr_token=" . $token;
$qr_image = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=" . urlencode($qr_url);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance QR - Scan to Record Time</title>
    <link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
    <script src="../assets/js/mobile-responsive.js" defer></script>
    <link rel="stylesheet" href="../assets/css/qr-display-kiosk.css">
</head>
<body>
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble" src="../../assets/pics/bcpLogo.png" alt="AdminLTELogo" height="60" width="60" />
    </div>
    <div class="kiosk-container">
        <!-- Header -->
        <div class="header">
            <h1>Attendance Check-in</h1>
            <p>Scan QR Code to Record Your Time in & Time out</p>
        </div>

        <!-- Current Time -->
        <div class="time-info">
            <strong>Current Time:</strong> <span id="current-time"><?php echo date("H:i:s"); ?></span> | 
            <strong>Date:</strong> <span id="current-date"><?php echo date("D, M d, Y"); ?></span>
        </div>

        <!-- QR Code Display -->
        <div class="qr-container new-code">
            <img src="<?php echo htmlspecialchars($qr_image); ?>" alt="Attendance QR Code" class="qr-image" id="qr-image">
            <small style="display: block; margin-top: 10px; color: #999; font-size: 10px;">URL: <?php echo htmlspecialchars($qr_url); ?></small>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h3>How to Use:</h3>
            <ol>
                <li><strong>Open your phone camera</strong></li>
                <li><strong>Point at the QR code</strong> displayed above</li>
                <li><strong>Tap the notification</strong> that appears</li>
                <li><strong>First scan:</strong> Records Time In</li>
                <li><strong>Second scan (same day):</strong> Records Time Out</li>
            </ol>
        </div>

        <!-- Status -->
        <div class="refresh-status">
            <p>QR Code auto-refreshes for security</p>
            <div class="refresh-timer">
                New code in: <span id="countdown">30</span> seconds
            </div>
        </div>

        <!-- Back Button -->
        <div class="back-button-container">
            <a href="javascript:history.back()" class="back-button">
                <span class="back-icon">←</span> Back
            </a>
        </div>
    </div>

    <script src="../assets/js/qr-display-kiosk.js"></script>
    <!-- Preloader Management Script -->

</body>
</html>

