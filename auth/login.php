<?php
// auth/login.php
include "../database/db.php";

function resolveLearningRole(array $user): string {
    $roleId = (int) ($user['role_id'] ?? 0);
    $roleName = strtolower($user['role_name'] ?? '');
    $deptName = strtolower($user['department_name'] ?? '');
    $empId = (int) ($user['employee_id'] ?? 0);

    if ($roleId === 1 || stripos($roleName, 'system admin') !== false) {
        return 'admin';
    }

    // L&D admins - specific employee IDs that should have admin access
    $ldAdminEmployeeIds = [35, 1018];
    if (in_array($empId, $ldAdminEmployeeIds, true)) {
        return 'admin';
    }

    if ($roleId === 7 || stripos($roleName, 'learning') !== false || stripos($deptName, 'instructor') !== false) {
        return 'instructor';
    }

    return 'learner';
}

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
            e.department_id,
            r.role_name,
            d.department_name
        FROM user_account u
        INNER JOIN em_employees e ON e.employee_id = u.employee_id
        INNER JOIN em_roles r ON r.role_id = u.role_id
        LEFT  JOIN em_departments d ON d.department_id = e.department_id
        WHERE (e.employee_code = :employeeid OR u.employee_id = :employeeid_num)
        AND e.employment_status = 'Active'
        LIMIT 1
    ");
    $stmt->bindParam(':employeeid', $employeeid, PDO::PARAM_STR);
    $stmt->bindValue(':employeeid_num', (int) $employeeid, PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ── Success: clear attempt tracking & build session ───────────────────
        unset($_SESSION[$key]);

        $_SESSION['employee_id']     = $user['employee_id'];
        $_SESSION['role']            = $user['role_id'];
        $_SESSION['role_name']       = $user['role_name'];
        $_SESSION['department_id']   = $user['department_id'];
        $_SESSION['department_name'] = $user['department_name'];

        $redirectMap = [
            1 => 'modules/recruitment/index.php',
            2 => 'modules/employee/index.php',
            3 => 'modules/payroll/index.php',
            4 => 'modules/time/index.php',
            5 => 'modules/performance/index.php',
            6 => 'modules/learning/index.php',
            7 => 'modules/compliance/index.php',
            8 => 'modules/workforce/index.php',
            9 => 'modules/exit/index.php',
            10 => 'modules/clinic/index.php',
            11 => 'modules/engagement/index.php',

        ];

        $empId = (int) $user['employee_id'];

        // ── Resolve L&D role from role/department ────────────────────────────
        $ldRole = resolveLearningRole($user);
        $_SESSION['learning_role'] = $ldRole;

        if ($ldRole !== 'learner') {
            echo json_encode([
                'success'  => true,
                'redirect' => 'modules/learning/index.php',
            ]);
            exit();
        }

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
