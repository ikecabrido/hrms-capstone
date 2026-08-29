<?php

use App\Middleware\ProtectedRoutes;
use App\Middleware\SessionTimeout;

use App\Controllers\AuthController;
use App\Controllers\LeaveController;
use App\Controllers\PortalController;
use App\Controllers\CourseController;
use App\Controllers\PayrollController;
use App\Controllers\ProfileController;
use App\Controllers\TrainingController;
use App\Controllers\ComplaintController;
use App\Controllers\GrievanceController;
use App\Controllers\AttendanceController;
use App\Controllers\ResignationController;
use App\Controllers\PerformanceController;
use App\Controllers\EmployeeUserController;
use App\Controllers\ManageCourseController;
use App\Controllers\AnnouncementController;
use App\Controllers\NotificationController;
use App\Controllers\SendEmployeesController;
use App\Controllers\ResetPasswordController;
use App\Controllers\OnlineMeetingController;
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
    // default
    '' => [AuthController::class, 'index'],
    // Authentication
    'auth-index' => [AuthController::class, 'index'],
    'admin' => [AuthController::class, 'adminIndex'],
    'auth-login' => [AuthController::class, 'login'],
    'admin-login' => [AuthController::class, 'adminLogin'],
    'auth-logout' => [AuthController::class, 'logout'],
    'admin-logout' => [AuthController::class, 'adminLogout'],

    // Reset Password
    'auth-forgot-password' => [ResetPasswordController::class, 'send'],
    'auth-reset-password' => [ResetPasswordController::class, 'resetPassword'],
    'auth-update-password' => [ResetPasswordController::class, 'updatePassword'],

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
    'admin-leave-request' => [LeaveController::class, 'adminIndex'],
    'leave-store' => [LeaveController::class, 'store'],
    'leave-cancel' => [LeaveController::class, 'cancel'],
    'leave-reject' => [LeaveController::class, 'reject'],
    'leave-approve' => [LeaveController::class, 'approve'],

    // Payroll
    'payroll' => [PayrollController::class, 'index'],
    'admin-payroll' => [PayrollController::class, 'adminIndex'],
    'payroll-request-store' => [PayrollController::class, 'store'],
    'payroll-approve-upload' => [PayrollController::class, 'upload'],
    'payroll-reject' => [PayrollController::class, 'reject'],

    // Benefits and Government Contributions
    'benefits-and-government-contribution' => [BenefitsAndGovernmentContributionController::class, 'index'],
    'admin-benefits' => [BenefitsAndGovernmentContributionController::class, 'adminIndex'],
    'employee-benefits-store' => [BenefitsAndGovernmentContributionController::class, 'store'],
    'benefits-store' => [BenefitsAndGovernmentContributionController::class, 'adminStore'],
    'benefit-upload' => [BenefitsAndGovernmentContributionController::class, 'upload'],

    // Announcement
    'announcement' => [AnnouncementController::class, 'index'],
    'announcement-view' => [AnnouncementController::class, 'view'],

    // Notification
    'notification' => [NotificationController::class, 'index'],
    'notification-mark-read' => [NotificationController::class, 'markRead'],
    'notification-mark-all-read' => [NotificationController::class, 'markAllRead'],

    // Performance
    'performance' => [PerformanceController::class, 'index'],

    // Training and Course
    'training' => [TrainingController::class, 'index'],
    'admin-learning-index' => [TrainingController::class, 'adminIndex'],
    'admin-store-course' => [CourseController::class, 'store'],
    'admin-update-course' => [CourseController::class, 'update'],
    'admin-course-toggle-status' => [CourseController::class, 'toggleStatus'],
    'admin-delete-course' => [CourseController::class, 'delete'],
    'admin-course-content' => [ManageCourseController::class, 'index'],

    // Complaint
    'complaint' => [ComplaintController::class, 'index'],
    'employee-complaints-store' => [ComplaintController::class, 'store'],

    // Grievance
    'grievance' => [GrievanceController::class, 'index'],
    'grievance-store' => [GrievanceController::class, 'store'],

    // Resignation
    'resignation' => [ResignationController::class, 'index'],
    'admin-resignation' => [ResignationController::class, 'adminIndex'],
    'employee-resignation-store' => [ResignationController::class, 'store'],
    'resignation-approve' => [ResignationController::class, 'approve'],
    'resignation-reject' => [ResignationController::class, 'reject'],

    // User/Employee Management
    'user-account' => [EmployeeUserController::class, 'index'],
    'view-all-employees' => [EmployeeUserController::class, 'viewAllEmployees'],
    'admin-user-store' => [EmployeeUserController::class, 'storeEmployees'],
    'view-all-attendance' => [EmployeeUserController::class, 'viewAllAttendance'],
    'user-set-active' => [EmployeeUserController::class, 'setActive'],

    // Online Meeting
    'online-meeting' => [OnlineMeetingController::class, 'index'],
    'admin-online-meeting' => [OnlineMeetingController::class, 'adminIndex'],
    'online-meeting-store' => [OnlineMeetingController::class, 'store'],
    'online-meeting-update-status' => [OnlineMeetingController::class, 'updateStatus'],
    'online-meeting-delete' => [OnlineMeetingController::class, 'delete'],

    // API
    'send-all-employees' => [SendEmployeesController::class, 'getAll'],
    'employee-api-login' => [SendEmployeesController::class, 'login'],


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