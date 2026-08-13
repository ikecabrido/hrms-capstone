<?php
/**
 * QR Scan Handler - Time & Attendance System
 * Validates QR token and redirects to login or processes attendance
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/helpers/QRHelper.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// Get token from query parameter
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $_SESSION['qr_error'] = 'No token provided';
    header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    exit;
}

$qrHelper = new QRHelper();

// Validate token exists and is not expired
$tokenData = $qrHelper->validateToken($token);
if (!$tokenData) {
    $_SESSION['qr_error'] = 'Invalid or expired token';
    
    // Check if user is authenticated
    if (!AuthController::isAuthenticated()) {
        // Redirect to root login with error
        header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
        exit;
    } else {
        // User is authenticated, send to dashboard with error
        if (AuthController::hasRole('time')) {
            header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
        } else {
            header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
        }
        exit;
    }
}

// Check if user is authenticated
if (!AuthController::isAuthenticated()) {
    // Redirect to root login with the QR token
    header('Location: ' . dirname(__DIR__) . '/../../login_form.php?qr_token=' . urlencode($token));
    exit;
}

// User is authenticated - process attendance immediately
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/../app/models/Attendance.php';

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Get current logged-in user's employee ID
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        $_SESSION['qr_error'] = 'User session invalid';
        header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
        exit;
    }

    // Get employee ID from user ID
    $query = "SELECT employee_id, full_name FROM employees WHERE user_id = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $userId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $_SESSION['qr_error'] = 'Employee record not found';
        
        if (AuthController::hasRole('time')) {
            header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
        } else {
            header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
        }
        exit;
    }

    // Record attendance
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // Check if there's already a record for today
    $checkQuery = "SELECT attendance_id, time_in, time_out FROM ta_attendance 
                   WHERE employee_id = :emp_id AND attendance_date = :date";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([':emp_id' => $employee['employee_id'], ':date' => $today]);
    $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

    $result = false;
    $message = '';

    if ($existingRecord) {
        if (empty($existingRecord['time_in']) && isset($existingRecord['status']) && $existingRecord['status'] === 'ABSENT') {
            $_SESSION['qr_error'] = 'Your attendance has already been marked ABSENT for today. Please contact HR for assistance.';
            if (AuthController::hasRole('time')) {
                header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
            } else {
                header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
            }
            exit;
        }

        if (!$existingRecord['time_in']) {
            // Record time_in
            $updateQuery = "UPDATE ta_attendance SET time_in = :time_in, status = 'PRESENT', recorded_by = 'QR' WHERE attendance_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $result = $updateStmt->execute([':time_in' => $now, ':id' => $existingRecord['attendance_id']]);
            $message = 'Time In recorded successfully!';
        } elseif (!$existingRecord['time_out']) {
            // Record time_out
            $updateQuery = "UPDATE ta_attendance SET time_out = :time_out, recorded_by = 'QR' WHERE attendance_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $result = $updateStmt->execute([':time_out' => $now, ':id' => $existingRecord['attendance_id']]);
            $message = 'Time Out recorded successfully!';
        } else {
            // Attendance already complete for today
            $_SESSION['qr_error'] = 'Attendance already recorded for today';
            
            if (AuthController::hasRole('time')) {
                header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
            } else {
                header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
            }
            exit;
        }
    } else {
        // Create new attendance record
        $insertQuery = "INSERT INTO ta_attendance (employee_id, attendance_date, time_in, status) 
                       VALUES (:emp_id, :date, :time_in, 'PRESENT')";
        $insertStmt = $conn->prepare($insertQuery);
        $result = $insertStmt->execute([
            ':emp_id' => $employee['employee_id'],
            ':date' => $today,
            ':time_in' => $now
        ]);
        $message = 'Time In recorded successfully!';
    }

    // Mark token as used
    if ($result) {
            $markUsedQuery = "UPDATE ta_attendance_tokens SET used = 1, used_by = :emp_id, used_at = NOW() WHERE token = :token";

        // Store success message in session and redirect to dashboard
        $_SESSION['qr_success'] = $message . ' for ' . $employee['full_name'];
        
        if (AuthController::hasRole('time')) {
            header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
        } else {
            header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
        }
        exit;
    } else {
        http_response_code(500);
        $_SESSION['qr_error'] = 'Failed to record attendance';
        
        if (AuthController::hasRole('time')) {
            header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
        } else {
            header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
        }
        exit;
    }

} catch (Exception $e) {
    $_SESSION['qr_error'] = 'Error: ' . $e->getMessage();
    
    if (AuthController::isAuthenticated()) {
        if (AuthController::hasRole('time')) {
            header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
        } else {
            header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
        }
    } else {
        header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
    }
    exit;
}
?>
<?php
$current_page = 'qr_scan.php';
$current_role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? 'guest';
$page_title = 'QR Scan';
$page_head_extra = "<link rel=\"icon\" href=\"../Bestlink College of the Philippines.jpeg\" type=\"image/jpeg\">\n<link rel=\"stylesheet\" href=\"../assets/css/qr-scan.css\">";
?>
<?php require_once __DIR__ . '/../layout/page_start.php'; ?>
<div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__wobble" src="../../assets/pics/bcpLogo.png" alt="AdminLTELogo" height="60" width="60" />
</div>
<div class="container">
    <div class="header">
        <h1>Time &amp; Attendance</h1>
        <p>QR Scan Check-in</p>
    </div>
    <div class="info-box">
        <p>✓ QR code scanned successfully. Please enter your Employee ID or Number to proceed.</p>
    </div>
    <form id="attendanceForm" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="employee_id">Employee ID / Number</label>
            <input type="text" id="employee_id" name="employee_id" placeholder="Enter your employee ID or scan your badge" autocomplete="off" autofocus required>
        </div>
        <div class="buttons">
            <button type="submit" class="btn btn-submit">Confirm Attendance</button>
            <button type="button" class="btn btn-cancel" onclick="window.location.href='qr_display_kiosk.php'">Cancel</button>
        </div>
    </form>
    <div id="loading" class="loading">
        <div class="spinner"></div>
        <p>Recording your attendance...</p>
    </div>
    <div id="message"></div>
</div>
<script>
    window.__TA_CONFIG = { requestUri: <?php echo json_encode($_SERVER['REQUEST_URI']); ?> };
</script>
<script src="../assets/js/qr-scan.js"></script>
<?php require_once __DIR__ . '/../layout/content_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/page_end.php'; ?>
<?php /*
    Legacy duplicate QR form removed; the active form above is the single rendered page.
*/ ?>
<?php /*
    <div class="preloader flex-column justify-content-center align-items-center">
        <img class="animation__wobble" src="../../assets/pics/bcpLogo.png" alt="AdminLTELogo" height="60" width="60" />
    </div>
    <div class="container">
        <h1>✓ QR Code Valid</h1>
        <p class="subtitle">Your QR code has been scanned successfully</p>

        <div class="info">
            This attendance will be recorded to your employee profile. A notification will be sent to your registered email.
        </div>

        <div id="message"></div>

        <form id="attendanceForm" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="employee_id">Employee ID / Number</label>
                <input 
                    type="text" 
                    id="employee_id" 
                    name="employee_id" 
                    placeholder="Enter your employee ID or scan your badge" 
                    autocomplete="off"
                    autofocus
                    required
                >
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-submit">Confirm Attendance</button>
                <button type="button" class="btn btn-cancel" onclick="window.location.href='qr_display_kiosk.php'">Cancel</button>
            </div>
        </form>

        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Recording your attendance...</p>
        </div>
    </div>

<script src="../assets/js/qr-scan.js"></script>
<!-- Preloader Management Script -->

*/ ?>

