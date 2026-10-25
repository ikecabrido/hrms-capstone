<?php
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $fallback = ['error' => 'Unable to encode JSON response.'];
        $json = json_encode($fallback, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    echo $json === false ? '{}' : $json;
    exit;
}

function inputData()
{
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $_POST;
    }
    return array_merge($_GET, (array)$json, $_POST);
}

function engagementApiHasAuthenticatedUser()
{
    return !empty($_SESSION['user'])
        || !empty($_SESSION['employee_id'])
        || !empty($_SESSION['user_id']);
}

function engagementApiIsHrAdmin()
{
    $user = $_SESSION['user'] ?? [];
    $role = strtolower(trim((string)($user['role_name'] ?? $user['role'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? '')));
    $position = strtolower(trim((string)($user['position_name'] ?? $_SESSION['position_name'] ?? '')));
    $roleId = (int)($user['role_id'] ?? $_SESSION['role_id'] ?? 0);

    return in_array($roleId, [1, 12], true)
        || preg_match('/(^|[^a-z])(admin|hr|human resources|human resource|employee relations|engagement)([^a-z]|$)/', $role) === 1
        || preg_match('/(^|[^a-z])(hr staff|hr officer)([^a-z]|$)/', $position) === 1;
}

function engagementApiRequireLogin()
{
    if (!engagementApiHasAuthenticatedUser()) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
}

function engagementApiRequireHrAdmin()
{
    engagementApiRequireLogin();
    if (!engagementApiIsHrAdmin()) {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }
}
