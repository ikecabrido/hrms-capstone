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
<<<<<<< Updated upstream
        SELECT
            user_account.user_id,
            user_account.employee_id,
            user_account.password,
            hrms_employee.role,
            hrms_employee.department,
            hrms_roles.role_name,
            hrms_department.department_name
        FROM user_account
        INNER JOIN hrms_employee  ON hrms_employee.employee_id  = user_account.employee_id
        INNER JOIN hrms_roles      ON hrms_roles.role_id          = hrms_employee.role
        LEFT  JOIN hrms_department ON hrms_department.department_id = hrms_employee.department
        WHERE user_account.employee_id = :employeeid
        AND   hrms_employee.status      = 'active'
=======
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
        WHERE (e.employee_code = :employeeid OR e.employee_id = :employeeid_num)
        AND e.employment_status = 'Active'
>>>>>>> Stashed changes
        LIMIT 1
    ");
    $stmt->bindParam(':employeeid', $employeeid);
    $stmt->bindValue(':employeeid_num', (int) $employeeid, PDO::PARAM_INT);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // ── Success: clear attempt tracking & build session ───────────────────
        unset($_SESSION[$key]);

        $_SESSION['employee_id']     = $user['employee_id'];
        $_SESSION['role']            = $user['role'];
        $_SESSION['role_name']       = $user['role_name'];
        $_SESSION['department_id']   = $user['department'];
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

        $role = (int) $user['role'];

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
