<?php
/**
 * Index / Home Page - Time & Attendance System
 * Redirects based on authentication status and role
 */

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/core/Session.php';

Session::start();

// If user is authenticated, redirect to appropriate dashboard
if (AuthController::isAuthenticated()) {
    if (AuthController::hasRole('time')) {
        header('Location: ' . dirname(__DIR__) . '/public/dashboard.php');
    } else {
        header('Location: ' . dirname(__DIR__) . '/../../employee_dashboard.php');
    }
    exit;
}

// Not authenticated, redirect to root login
header('Location: ' . dirname(__DIR__) . '/../../login_form.php');
exit;
