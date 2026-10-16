<?php
// TEMPORARY verification helper — delete after this round's checks.
session_start();

$employeeId = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;

$_SESSION = [];
if ($employeeId > 0) {
    $_SESSION['employee_id'] = $employeeId;
    $_SESSION['last_activity'] = time();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['employee_id' => $_SESSION['employee_id'] ?? null]);
