<?php
// auth/login.php
include "../database/db.php";

$db   = new Database();
$conn = $db->getConnection();

session_start();

header('Content-Type: application/json');

define('MAX_ATTEMPTS', 3);
define('LOCKOUT_TIME', 60); // 60 seconds lockout

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeid = trim($_POST['employee_id']);
    $password   = $_POST['password'];

    $ip  = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . $ip . '_' . $employeeid;

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
    }

    $attempts = &$_SESSION[$key];
    $elapsed  = time() - $attempts['last_attempt'];

    // Reset if lockout period has passed
    if ($elapsed > LOCKOUT_TIME) {
        $attempts = ['count' => 0, 'last_attempt' => time()];
        $elapsed  = 0;
    }

    // Block if already locked
    if ($attempts['count'] >= MAX_ATTEMPTS) {
        $remaining_seconds = max(0, (int)(LOCKOUT_TIME - $elapsed));
        echo json_encode([
            'success'           => false,
            'locked'            => true,
            'remaining_seconds' => $remaining_seconds,
            'message'           => 'Too many failed attempts.',
        ]);
        exit();
    }

    // ── Authentication ────────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.employee_id, 
            u.role_id,
            u.password,
            u.account_status,
            e.employee_code,
            e.first_name,
            e.middle_name,
            e.last_name,
            e.position_id,
            e.department_id,
            e.employment_status,
            p.position_name,
            r.role_name,
            d.department_name
        FROM user_account u
        INNER JOIN em_employees e
            ON e.employee_id = u.employee_id
        INNER JOIN em_roles r
            ON r.role_id = u.role_id
        INNER JOIN em_positions p
            ON p.position_id = e.position_id
        LEFT JOIN em_departments d
            ON d.department_id = e.department_id
        WHERE e.employee_code = :employeeid
        AND e.employment_status = 'ACTIVE'
        AND p.position_name IN ('HR Staff', 'HR Officer')
        LIMIT 1
    ");
    $stmt->bindParam(':employeeid', $employeeid);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['account_status'] === 'Active' && password_verify($password, $user['password'])) {
        // ── Success: clear attempt tracking & build session ───────────────────
        unset($_SESSION[$key]);

        $_SESSION['user_id']        = $user['user_id'];
        $_SESSION['employee_id']    = $user['employee_id'];
        $_SESSION['employee_code']  = $user['employee_code'];
        $_SESSION['employee_name']  = trim(
            $user['first_name'] . ' ' . $user['last_name']
        );
        $_SESSION['role_id']        = $user['role_id'];
        $_SESSION['role_name']      = $user['role_name'];
        $_SESSION['position_id']    = $user['position_id'];
        $_SESSION['position_name']  = $user['position_name'];
        $_SESSION['department_id']   = $user['department_id'];
        $_SESSION['department_name'] = $user['department_name'];
        $_SESSION['last_activity'] = time();
        $_SESSION['freshly_logged_in'] = true;  // Flag to indicate fresh login
            $_SESSION['reset_engagement_tabs'] = true; // Set flag to reset engagement tabs

        $updateLogin = $conn->prepare("
            UPDATE user_account
            SET last_login = CURRENT_TIMESTAMP,
                failed_login_attempts = 0
            WHERE user_id = :user_id
        ");
        $updateLogin->execute([
            ':user_id' => $user['user_id']
        ]);

        $redirectMap = [
            2 => 'modules/recruitment/index.php',
            3 => 'modules/employee/index.php',
            4 => 'modules/payroll/index.php',
            5 => 'modules/time/index.php',
            6 => 'modules/performance/index.php',
            7 => 'modules/learning/index.php',
            8 => 'modules/compliance/index.php',
            9 => 'modules/workforce/index.php',
            10 => 'modules/exit/index.php',
            11 => 'modules/clinic/index.php',
            12 => 'modules/engagement/index.php?page=dashboard-overview',
            13 => 'modules/portal/index.php'

        ];

        $role = (int) $user['role_id'];

        if (!isset($redirectMap[$role])) {
            echo json_encode(['success' => false, 'locked' => false, 'message' => 'Invalid role.']);
            exit();
        }

        echo json_encode([
            'success'  => true,
            'redirect' => $redirectMap[$role],
        ]);
        exit();
    } else {
        // ── Failed: increment attempt counter ─────────────────────────────────
        $attempts['count']++;
        $attempts['last_attempt'] = time();

        $remaining_attempts = MAX_ATTEMPTS - $attempts['count'];

        if ($remaining_attempts <= 0) {
            echo json_encode([
                'success'           => false,
                'locked'            => true,
                'remaining_seconds' => (int) LOCKOUT_TIME,
                'message'           => 'Too many failed attempts. Account locked for 15 minutes.',
            ]);
        } else {
            echo json_encode([
                'success'            => false,
                'locked'             => false,
                'remaining_attempts' => $remaining_attempts,
                'message'            => 'Invalid Employee ID or Password.',
            ]);
        }
        exit();
    }
}
