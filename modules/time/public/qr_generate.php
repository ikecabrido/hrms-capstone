<?php
/**
 * QR Code Generation Page for HR Admin
 * Generates temporary attendance tokens and displays QR codes
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';
require_once __DIR__ . '/../app/helpers/Helper.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Check if user is authenticated
if (!AuthController::isAuthenticated()) {
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    exit;
}

// Only HR can access this page
if (!AuthController::hasRole('time')) {
    header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    exit;
}

$user_id = AuthController::getCurrentUserId();
$qrHelper = new QRHelper();

// Initialize variables
$generated_tokens = [];
$message = "";
$messageType = "";
$server_ip = "";

// Function to detect server IP
function getServerIP() {
    // Try multiple methods to get the actual IP
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }
    
    // Try hostname resolution
    $hostname = gethostname();
    $ip = gethostbyname($hostname);
    if ($ip !== $hostname && $ip !== '127.0.0.1') {
        return $ip;
    }
    
    // If everything fails, return empty so user must input manually
    return "";
}

$server_ip = getServerIP();
$custom_ip = trim($_POST['custom_ip'] ?? '');

// Handle token generation
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = trim($_POST['action'] ?? '');
    $count = (int)($_POST['token_count'] ?? 1);
    $custom_ip = trim($_POST['custom_ip'] ?? '');

    if ($action === 'generate' && $count > 0 && $count <= 50) {
        try {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            // If accessing via localhost, use custom IP or auto-detected IP
            if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
                if (!empty($custom_ip)) {
                    // Validate IP format
                    if (!filter_var($custom_ip, FILTER_VALIDATE_IP)) {
                        throw new Exception("Invalid IP address format. Please enter a valid IP (e.g., 192.168.1.100)");
                    }
                    $host = $custom_ip;
                } else if (!empty($server_ip)) {
                    $host = $server_ip;
                } else {
                    throw new Exception("Please enter your server IP address to generate QR codes");
                }
            }
            
            // Validate and build URL - Point to employee portal login with qr_token parameter
            $qr_url = $protocol . "://" . $host . "/capstone_hr_management_system/employee_portal/index.php?url=auth-index";
            
            // Verify URL is valid
            if (!filter_var($qr_url, FILTER_VALIDATE_URL)) {
                throw new Exception("Generated URL is invalid: " . htmlspecialchars($qr_url));
            }
            
            for ($i = 0; $i < $count; $i++) {
                $token = $qrHelper->generateToken($user_id, Helper::getCurrentDate());
                if ($token) {
                    $qr_data_url = $qr_url . "?qr_token=" . $token;
                    $generated_tokens[] = [
                        'token' => $token,
                        'qr_data' => $qr_data_url,
                        'full_url' => $qr_data_url, // For debugging
                        'expires_at' => date("Y-m-d H:i:s", strtotime("+1 minute"))
                    ];
                }
            }

            if (!empty($generated_tokens)) {
                $message = "Generated " . count($generated_tokens) . " QR code(s) successfully!";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Error generating tokens: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "Please enter a valid number of tokens (1-50)";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Codes - Time & Attendance System</title>
    <link rel="icon" href="../Bestlink College of the Philippines.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/mobile-responsive.js" defer></script>
</head>
<body>
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble" src="../../assets/pics/bcpLogo.png" alt="AdminLTELogo" height="60" width="60" />
    </div>
    <!-- Header Navigation -->
    <header>
        <div class="header-container">
            <a href="index.php" class="header-brand">Time & Attendance</a>
            <nav class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="qr_generate.php">Generate QR</a>
                <div class="header-user">
                    <div class="user-info">
                        <p><strong>HR Administrator</strong></p>
                    </div>
                    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <h2 class="page-title">Generate Attendance QR Codes</h2>
        <p class="page-subtitle">Create temporary QR codes for employee attendance recording</p>

        <!-- Server Info -->
        <div class="container" style="background-color: #e7f3ff; border-left: 4px solid #2196F3; margin-bottom: 20px; padding: 15px;">
            <p style="margin: 0; font-size: 13px; color: #1976D2;">
                <strong>📡 Server IP Detection:</strong> 
                <?php 
                    if (!empty($server_ip)) {
                        echo "Detected: <code>" . htmlspecialchars($server_ip) . "</code>";
                    } else {
                        echo "Could not auto-detect. Please enter your IP address below.";
                    }
                ?>
            </p>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- QR Generation Form -->
        <div class="container">
            <form method="POST" class="form-section">
                <div class="form-section-title">
                    Generate QR Tokens
                </div>

                <?php if (empty($server_ip)): ?>
                    <!-- Manual IP Input (if auto-detect failed) -->
                    <div class="form-row">
                        <div class="form-control">
                            <label for="custom_ip">Server IP Address *</label>
                            <input 
                                type="text" 
                                id="custom_ip" 
                                name="custom_ip" 
                                placeholder="e.g., 192.168.1.100"
                                value="<?php echo htmlspecialchars($custom_ip); ?>"
                                required
                            >
                            <small style="color: #999; margin-top: 5px; display: block;">
                                Enter your computer's IP address (find it in Command Prompt: ipconfig /all)
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-control">
                        <label for="token_count">Number of QR Codes</label>
                        <input 
                            type="number" 
                            id="token_count" 
                            name="token_count" 
                            min="1" 
                            max="50" 
                            value="1"
                            required
                        >
                        <small style="color: #999; margin-top: 5px; display: block;">
                            Generate 1 to 50 QR codes at once
                        </small>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="action" value="generate" class="btn btn-primary btn-lg">
                        Generate QR Codes
                    </button>
                </div>
            </form>
        </div>

        <!-- Generated QR Codes Display -->
        <?php if (!empty($generated_tokens)): ?>
            <div class="container" style="margin-top: 30px;">
                <!-- Debug Info -->
                <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 12px;">
                        <strong>Debug URL:</strong> <code style="word-break: break-all;"><?php echo htmlspecialchars($generated_tokens[0]['full_url'] ?? 'N/A'); ?></code>
                    </p>
                </div>

                <h3 style="margin-bottom: 20px;">
                    Generated Codes (<?php echo count($generated_tokens); ?>)
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($generated_tokens as $index => $token_data): ?>
                        <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; text-align: center;">
                            <p style="font-weight: 600; margin-bottom: 10px;">QR Code #<?php echo ($index + 1); ?></p>
                            
                            <!-- QR Code -->
                            <div style="margin: 15px 0;">
                                <img 
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($token_data['qr_data']); ?>" 
                                    alt="QR Code <?php echo ($index + 1); ?>"
                                    style="max-width: 200px; height: auto;"
                                >
                            </div>

                            <!-- Token Display -->
                            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;">
                                <small style="color: #666;">Token:</small><br>
                                <code style="font-size: 11px;"><?php echo htmlspecialchars(substr($token_data['token'], 0, 16)); ?>...</code>
                            </div>

                            <!-- Expiry Time -->
                            <p style="font-size: 12px; color: #999;">
                                Expires: <?php echo Helper::formatTime($token_data['expires_at']); ?>
                            </p>

                            <!-- Copy Token Button -->
                            <button 
                                type="button" 
                                class="btn btn-secondary btn-sm" 
                                onclick="copyToClipboard('<?php echo htmlspecialchars($token_data['token']); ?>')"
                                style="margin-top: 10px;"
                            >
                                Copy Token
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Print Button -->
                <div style="margin-top: 30px; text-align: center;">
                    <button type="button" class="btn btn-secondary" onclick="window.print()">
                        Print QR Codes
                    </button>
                    <a href="debug_tokens.php" class="btn btn-secondary" style="margin-left: 10px;">
                        Debug Token Status
                    </a>
                    <a href="diagnostics.php" class="btn btn-secondary" style="margin-left: 10px;">
                        System Diagnostics
                    </a>
                    <p style="margin-top: 10px; font-size: 12px; color: #999;">
                        Print this page to display QR codes or share with employees
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Information Section -->
        <div class="container" style="margin-top: 30px;">
            <h3 style="margin-bottom: 20px;">How QR Token Security Works</h3>
            <ol style="line-height: 2.2;">
                <li><strong>Token Generation:</strong> Each token is cryptographically secure (32 bytes)</li>
                <li><strong>Time Expiry:</strong> Tokens automatically expire after 1 minute</li>
                <li><strong>Single-Use:</strong> Each token can only be used once</li>
                <li><strong>Server Validation:</strong> All tokens are validated server-side</li>
                <li><strong>Screenshot Prevention:</strong> Tokens expire quickly, making screenshots useless</li>
                <li><strong>Audit Trail:</strong> All token usage is logged with employee/IP/timestamp</li>
            </ol>
        </div>

        <!-- Features Section -->
        <div class="container" style="margin-top: 20px;">
            <h3 style="margin-bottom: 20px;">Key Features</h3>
            <ul style="line-height: 2;">
                <li>Generate multiple QR codes at once</li>
                <li>Each code is unique and secure</li>
                <li>Codes are valid for 1 minute only</li>
                <li>Display/Print QR codes directly</li>
                <li>Copy token to clipboard for manual entry</li>
                <li>Real-time expiry tracking</li>
                <li>Complete audit logging of all scans</li>
            </ul>
        </div>
    </main>

    <script src="../assets/js/qr-generate.js"></script>
</html>

