<?php
// modules/learning/ajax/login.php
require_once dirname(__DIR__, 3) . '/database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$identifier = trim((string) ($_POST['employee_id'] ?? ''));
$password   = (string) ($_POST['password'] ?? '');

if ($identifier === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Enter your employee ID and password.']);
    exit();
}

try {
    $database = new Database();
    $connection = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

if (ctype_digit($identifier)) {
    $query = $connection->prepare(
        "SELECT u.user_id, u.employee_id, u.role_id, u.password, u.account_status,
                e.employee_code, e.first_name, e.last_name, e.position_id,
                e.department_id, e.employment_status, p.position_name,
                r.role_name, d.department_name
         FROM user_account u
         INNER JOIN em_employees e ON e.employee_id = u.employee_id
         INNER JOIN em_roles r ON r.role_id = u.role_id
         INNER JOIN em_positions p ON p.position_id = e.position_id
         LEFT JOIN em_departments d ON d.department_id = e.department_id
         WHERE e.employee_id = :employee_id
         LIMIT 1"
    );
    $query->execute([':employee_id' => (int) $identifier]);
} else {
    $query = $connection->prepare(
        "SELECT u.user_id, u.employee_id, u.role_id, u.password, u.account_status,
                e.employee_code, e.first_name, e.last_name, e.position_id,
                e.department_id, e.employment_status, p.position_name,
                r.role_name, d.department_name
         FROM user_account u
         INNER JOIN em_employees e ON e.employee_id = u.employee_id
         INNER JOIN em_roles r ON r.role_id = u.role_id
         INNER JOIN em_positions p ON p.position_id = e.position_id
         LEFT JOIN em_departments d ON d.department_id = e.department_id
         WHERE e.employee_code = :employee_code
         LIMIT 1"
    );
    $query->execute([':employee_code' => $identifier]);
}

$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user && $user['account_status'] === 'Active'
    && $user['employment_status'] === 'Active'
    && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['employee_code'] = $user['employee_code'];
    $_SESSION['employee_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['position_id'] = $user['position_id'];
    $_SESSION['position_name'] = $user['position_name'];
    $_SESSION['department_id'] = $user['department_id'];
    $_SESSION['department_name'] = $user['department_name'];
    $_SESSION['last_activity'] = time();

    echo json_encode([
        'success'  => true,
        'redirect' => 'index.php',
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid employee ID or password.']);
exit();
