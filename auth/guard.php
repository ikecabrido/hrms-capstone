<?php
// auth/guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role'])) {
    $isFetch = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') && !empty($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors';

    if (!empty($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'document') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['redirect' => '/auth/login.php']);
        exit();
    }

    header("Location:/auth/login.php");
    exit();
}