<?php

use App\Middleware\ProtectedRoutes;
use App\Middleware\SessionTimeout;

use App\Controllers\AuthController;
use App\Controllers\LeaveController;
use App\Controllers\PortalController;
use App\Controllers\PayrollController;
use App\Controllers\ProfileController;
use App\Controllers\ComplaintController;
use App\Controllers\GrievanceController;
use App\Controllers\AttendanceController;
use App\Controllers\ResignationController;
use App\Controllers\PerformanceController;
use App\Controllers\AnnouncementController;
use App\Controllers\NotificationController;
use App\Controllers\BenefitsAndGovernmentContributionController;


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
    'auth-index' => [AuthController::class, 'index'],
    'admin' => [AuthController::class, 'adminIndex'],
    'auth-login' => [AuthController::class, 'login'],
    'admin-login' => [AuthController::class, 'adminLogin'],
    'auth-logout' => [AuthController::class, 'logout'],

    // Dashboard
    'employee-dashboard' => [PortalController::class, 'dashboard'],
    'admin-dashboard' => [PortalController::class, 'adminDashboard'],

    // Profile
    'user-profile' => [ProfileController::class, 'index'],
    'update-password' => [ProfileController::class, 'updatePassword'],
    'update-user-profile' => [ProfileController::class, 'updateProfile'],
    'update-profile-image' => [ProfileController::class, 'updateProfileImage'],

    // Attendance
    'attendance' => [AttendanceController::class, 'index'],

    // Leave Request
    'leave-request' => [LeaveController::class, 'index'],
    'leave-store' => [LeaveController::class, 'store'],

    // Payroll
    'payroll' => [PayrollController::class, 'index'],
    'payroll-request-store' => [PayrollController::class, 'store'],

    // Benefits and Government Contributions
    'benefits-and-government-contribution' => [BenefitsAndGovernmentContributionController::class, 'index'],
    'employee-benefits-store' => [BenefitsAndGovernmentContributionController::class, 'store'],

    // Announcement
    'announcement' => [AnnouncementController::class, 'index'],
    'announcement-view' => [AnnouncementController::class, 'view'],

    // Notification
    'notification'  => [NotificationController::class, 'index'],
    'notification-mark-read'  => [NotificationController::class, 'markRead'],
    'notification-mark-all-read'  => [NotificationController::class, 'markAllRead'],

    // Performance
    'performance' => [PerformanceController::class, 'index'],

    // Complaint
    'complaint' => [ComplaintController::class, 'index'],
    'employee-complaints-store' => [ComplaintController::class, 'store'],

    // Grievance
    'grievance' => [GrievanceController::class, 'index'],
    'grievance-store' => [GrievanceController::class, 'store'],

    // Resignation
    'resignation' => [ResignationController::class, 'index'],
    'employee-resignation-store' => [ResignationController::class, 'store'],
    


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
ProtectedRoutes::check($url);

/*
|--------------------------------------------------------------------------
| Execute Controller
|--------------------------------------------------------------------------
*/

[$controller, $method] = $routes[$url];

$controllerInstance = new $controller();

$controllerInstance->$method();