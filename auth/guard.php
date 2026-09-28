<?php
// auth/guard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role'])) {

    if (
        !empty($_SERVER['HTTP_SEC_FETCH_DEST']) &&
        $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'document'
    ) {
        http_response_code(401);
        header('Content-Type: application/json');

        echo json_encode([
            'redirect' => '/auth/login.php'
        ]);

        exit();
    }

    header('Location: /auth/login.php');
    exit();
}
