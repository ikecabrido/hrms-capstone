<?php
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/utils.php';

use App\Api\ApiResponse;
use App\Api\ApiRouter;

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

$resource = $_GET['resource'] ?? '';
$resource = is_string($resource) ? $resource : '';

require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/ApiRouter.php';

try {
    $router = new ApiRouter($resource);
    $router->dispatch();
} catch (\Throwable $e) {
    ApiResponse::error($e->getMessage(), 500);
}

