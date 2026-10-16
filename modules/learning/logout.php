<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mark this session as ended so it drops off the profile Security list.
$ldEmployeeId = (int) ($_SESSION['employee_id'] ?? 0);
if ($ldEmployeeId > 0) {
    try {
        require_once __DIR__ . '/classes/UserSession.php';
        (new UserSession())->endCurrent($ldEmployeeId, UserSession::currentToken());
    } catch (Throwable $e) {
        // best-effort
    }
}

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
