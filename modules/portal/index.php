<?php

use App\Middleware\SessionTimeout;
use App\Controllers\AuthController;
use App\Controllers\LeaveController;
use App\Controllers\PortalController;
use App\Controllers\PayrollController;
use App\Controllers\ProfileController;
use App\Controllers\AttendanceController;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

SessionTimeout::check();

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

$routes = [
    // Authentication
    'auth-index'  => [AuthController::class, 'index'],
    'admin'       => [AuthController::class, 'adminIndex'],
    'auth-login'  => [AuthController::class, 'login'],
    'admin-login' => [AuthController::class, 'adminLogin'],
    'auth-logout' => [AuthController::class, 'logout'],

    // Dashboard
    'employee-dashboard' => [PortalController::class, 'dashboard'],
    'admin-dashboard'    => [PortalController::class, 'adminDashboard'],

    // Profile
    'user-profile'         => [ProfileController::class, 'index'],
    'update-password'      => [ProfileController::class, 'updatePassword'],
    'update-user-profile'  => [ProfileController::class, 'updateProfile'],
    'update-profile-image' => [ProfileController::class, 'updateProfileImage'],

    // Attendance
    'attendance' => [AttendanceController::class, 'index'],

    // Leave Request
    'leave-request' => [LeaveController::class, 'index'],
    'leave-store' => [LeaveController::class, 'store'],

    // Payroll
    'payroll' => [PayrollController::class, 'index'],
    'payroll-request-store' => [PayrollController::class, 'store'],





];

/*
|--------------------------------------------------------------------------
| Get Requested Route
|--------------------------------------------------------------------------
*/

$url = trim($_GET['url'] ?? '');

/*
|--------------------------------------------------------------------------
| Default Route
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_GET)) {
    $url = 'auth-index';
}

/*
|--------------------------------------------------------------------------
| 404 - Invalid Route
|--------------------------------------------------------------------------
*/

if (!array_key_exists($url, $routes)) {

    http_response_code(404);

    require __DIR__ . '/app/views/errors/error-404.php';

    exit;
}

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

$protectedRoutes = [
    'employee-dashboard',
    'admin-dashboard',
    'user-profile',
    'attendance',
];

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/

if (in_array($url, $protectedRoutes, true)) {

    if (empty($_SESSION['user_id'])) {

        header(
            'Location: /hrms-capstone/modules/portal/index.php?url=auth-index'
        );

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Execute Controller
|--------------------------------------------------------------------------
*/

[$controller, $method] = $routes[$url];

$controllerInstance = new $controller();

$controllerInstance->$method();