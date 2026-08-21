<?php
// auth/guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role'])) {
    $isAjaxRequest = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    $isFetchRequest = (!empty($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'document')
        || (!empty($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors');

    if ($isAjaxRequest || $isFetchRequest) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['redirect' => '/auth/login.php']);
        exit();
    }

    header("Location:/auth/login.php");
    exit();
}