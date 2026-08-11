<?php

use App\Controllers\AuthController;
use App\Controllers\PortalController;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$routes = [
    // Authentication
    'auth-index'  => [AuthController::class, 'index'],
    'auth-login'  => [AuthController::class, 'login'],
    'auth-logout' => [AuthController::class, 'logout'],

    // Dashboard
    'employee-dashboard' => [PortalController::class, 'dashboard'],
];

$url = trim($_GET['url'] ?? '');

if ($url === '') {
    $url = 'auth-index';
}

if (!array_key_exists($url, $routes)) {
    http_response_code(404);
    require __DIR__ . '/app/views/errors/error-404.php';
    exit;
}

$protectedRoutes = [
    'employee-dashboard',
];

if (in_array($url, $protectedRoutes, true)) {

    if (empty($_SESSION['user_id'])) {

        header(
            'Location: /hrms-capstone/modules/portal/index.php?url=auth-index'
        );

        exit;
    }
}

[$controller, $method] = $routes[$url];

$controllerInstance = new $controller();
$controllerInstance->$method();