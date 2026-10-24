<?php
require_once dirname(__DIR__, 2) . '/database/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pdo = (new Database())->getConnection();
$stmt = $pdo->prepare("SELECT ua.user_id, e.*, r.role_name, p.position_name, d.department_name
    FROM user_account ua
    JOIN em_employees e ON e.employee_id = ua.employee_id
    LEFT JOIN em_roles r ON r.role_id = e.role_id
    LEFT JOIN em_positions p ON p.position_id = e.position_id
    LEFT JOIN em_departments d ON d.department_id = e.department_id
    WHERE ua.employee_id = 1007 LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    echo 'User not found';
    exit;
}
$_SESSION['user_id'] = (int) $user['user_id'];
$_SESSION['employee_id'] = (int) $user['employee_id'];
$_SESSION['employee_code'] = $user['employee_code'];
$_SESSION['employee_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
$_SESSION['role_id'] = (int) $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];
$_SESSION['position_id'] = (int) $user['position_id'];
$_SESSION['position_name'] = $user['position_name'];
$_SESSION['department_id'] = (int) $user['department_id'];
$_SESSION['department_name'] = $user['department_name'];
$_SESSION['last_activity'] = time();
$_SESSION['learning_role'] = 'admin';
header('Location: index.php?page=admin/user-subpage/instructor&id=1007');
exit;
